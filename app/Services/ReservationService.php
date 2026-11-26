<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pusat logika bisnis reservasi: cek bentrok, hitung tarif, dan perubahan status.
 */
class ReservationService
{
    /**
     * Dua jadwal bentrok jika: mulai_lama < selesai_baru DAN selesai_lama > mulai_baru.
     * Jadwal yang hanya bersentuhan (10-12 dan 12-13) tidak dianggap bentrok.
     */
    public function hasConflict(int $courtId, string $date, int $startHour, int $endHour, ?int $ignoreId = null): bool
    {
        return Reservation::query()
            ->where('court_id', $courtId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', Reservation::BLOCKING_STATUSES)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_time', '<', $this->toTime($endHour))
            ->where('end_time', '>', $this->toTime($startHour))
            ->exists();
    }

    /** Daftar jam (angka) yang sudah terisi pada satu lapangan di satu tanggal */
    public function bookedHours(int $courtId, string $date): array
    {
        $hours = [];

        Reservation::query()
            ->where('court_id', $courtId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', Reservation::BLOCKING_STATUSES)
            ->get()
            ->each(function (Reservation $r) use (&$hours) {
                for ($h = (int) substr($r->start_time, 0, 2); $h < (int) substr($r->end_time, 0, 2); $h++) {
                    $hours[$h] = $r->type;
                }
            });

        return $hours;
    }

    /** Sisa slot hari ini (hanya jam yang belum lewat) */
    public function remainingSlotsToday(Court $court): int
    {
        $booked = $this->bookedHours($court->id, today()->toDateString());
        $firstHour = max(config('booking.jam_buka'), now()->hour + 1);
        $free = 0;

        for ($h = $firstHour; $h < config('booking.jam_tutup'); $h++) {
            if (! isset($booked[$h])) {
                $free++;
            }
        }

        return $free;
    }

    public function create(User $user, array $data, string $proofPath): Reservation
    {
        return DB::transaction(function () use ($user, $data, $proofPath) {
            $attributes = $this->prepareSchedule($data);

            return Reservation::create($attributes + [
                'code' => Reservation::generateCode(),
                'user_id' => $user->id,
                'type' => 'booking',
                'status' => 'pending',
                'payment_proof' => $proofPath,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function update(Reservation $reservation, array $data, ?string $newProofPath = null): void
    {
        $oldProof = null;

        DB::transaction(function () use ($reservation, $data, $newProofPath, &$oldProof) {
            // Kunci lapangan dulu, baru reservasi (urutan kunci selalu sama)
            $attributes = $this->prepareSchedule($data, $reservation->id);
            $locked = $this->lock($reservation);
            $this->ensureStatus($locked, 'pending', 'Hanya pesanan berstatus Pending yang bisa diubah.');

            if ($newProofPath) {
                $oldProof = $locked->payment_proof;
                $attributes['payment_proof'] = $newProofPath;
            }

            $locked->update($attributes + ['notes' => $data['notes'] ?? null]);
        });

        // File lama dihapus setelah database berhasil disimpan
        if ($oldProof) {
            Storage::disk('public')->delete($oldProof);
        }
    }

    public function cancel(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $locked = $this->lock($reservation);
            $this->ensureStatus($locked, 'pending', 'Hanya pesanan berstatus Pending yang bisa dibatalkan.');
            $locked->update(['status' => 'cancelled']);
        });
    }

    public function approve(Reservation $reservation, User $admin, ?string $note = null): void
    {
        DB::transaction(function () use ($reservation, $admin, $note) {
            Court::whereKey($reservation->court_id)->lockForUpdate()->firstOrFail();
            $locked = $this->lock($reservation);
            $this->ensureStatus($locked, 'pending', 'Hanya pesanan Pending yang bisa disetujui.');

            if (! $locked->payment_proof) {
                $this->fail('status', 'Pesanan belum memiliki bukti transfer.');
            }

            if ($locked->endsAt()->isPast()) {
                $this->fail('status', 'Jadwal sudah lewat. Silakan tolak pesanan ini.');
            }

            if ($this->hasConflict(
                $locked->court_id,
                $locked->booking_date->toDateString(),
                (int) substr($locked->start_time, 0, 2),
                (int) substr($locked->end_time, 0, 2),
                $locked->id
            )) {
                $this->fail('status', 'Jadwal bentrok dengan reservasi lain.');
            }

            $locked->update([
                'status' => 'approved',
                'admin_note' => $note,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);
        });
    }

    public function reject(Reservation $reservation, User $admin, string $note): void
    {
        DB::transaction(function () use ($reservation, $admin, $note) {
            $locked = $this->lock($reservation);
            $this->ensureStatus($locked, 'pending', 'Hanya pesanan Pending yang bisa ditolak.');

            $locked->update([
                'status' => 'rejected',
                'admin_note' => $note,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);
        });
    }

    /**
     * Menyelesaikan reservasi mengubah 3 tabel sekaligus:
     * reservations (status & denda), users (poin), courts (total jam terpakai).
     */
        public function complete(Reservation $reservation, int $overtimeHours = 0, ?string $note = null): void
    {
        DB::transaction(function () use ($reservation, $overtimeHours, $note) {
            $court = Court::whereKey($reservation->court_id)->lockForUpdate()->firstOrFail();
            $locked = $this->lock($reservation);
            $this->ensureStatus($locked, 'approved', 'Hanya pesanan Approved yang bisa diselesaikan.');

            if ($locked->startsAt()->isFuture()) {
                $this->fail('status', 'Jadwal belum dimulai, pesanan belum bisa diselesaikan.');
            }

            // Kelebihan main tidak mungkin melewati jam tutup
            $maxOvertime = config('booking.jam_tutup') - (int) substr($locked->end_time, 0, 2);

            if ($overtimeHours < 0 || $overtimeHours > $maxOvertime) {
                $this->fail('overtime_hours', "Jam kelebihan harus antara 0 dan {$maxOvertime}.");
            }

            $locked->update([
                'status' => 'completed',
                'overtime_hours' => $overtimeHours,
                'penalty' => $overtimeHours * $locked->price_per_hour,
                'admin_note' => $note ?: $locked->admin_note,
                'completed_at' => now(),
            ]);

            // 1 jam sewa = 1 poin
            $locked->user()->increment('points', $locked->duration_hours);
            $court->increment('total_hours_used', $locked->duration_hours);
        });
    }

    /** Admin memblokir jadwal untuk perawatan lapangan */
    public function blockMaintenance(User $admin, array $data): Reservation
    {
        return DB::transaction(function () use ($admin, $data) {
            $attributes = $this->prepareSchedule($data, null, true);

            return Reservation::create(array_merge($attributes, [
                'code' => Reservation::generateCode('MTC'),
                'user_id' => $admin->id,
                'type' => 'maintenance',
                'status' => 'approved',
                'price_per_hour' => 0,
                'total_price' => 0,
                'notes' => $data['notes'],
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]));
        });
    }

    /**
     * Validasi jadwal dan hitung durasi serta tarif di server.
     * Wajib dipanggil di dalam transaksi karena mengunci baris lapangan.
     */
    private function prepareSchedule(array $data, ?int $ignoreId = null, bool $maintenance = false): array
    {
        // lockForUpdate mencegah dua orang memesan slot yang sama secara bersamaan
        $court = Court::whereKey($data['court_id'])->lockForUpdate()->firstOrFail();

        $date = Carbon::parse($data['booking_date'])->toDateString();
        $start = (int) $data['start_hour'];
        $end = (int) $data['end_hour'];
        $duration = $end - $start;

        if (! $maintenance && ! $court->is_active) {
            $this->fail('court_id', 'Lapangan sedang tidak aktif.');
        }

        if ($duration < 1) {
            $this->fail('end_hour', 'Jam selesai harus setelah jam mulai.');
        }

        if ($start < config('booking.jam_buka') || $end > config('booking.jam_tutup')) {
            $this->fail('start_hour', 'Jadwal di luar jam operasional.');
        }

        if (! $maintenance && $duration > config('booking.maks_durasi')) {
            $this->fail('end_hour', 'Maksimal sewa '.config('booking.maks_durasi').' jam per pesanan.');
        }

        if (Carbon::parse($date)->setTime($start, 0)->isPast()) {
            $this->fail('start_hour', 'Jadwal yang dipilih sudah lewat.');
        }

        if ($this->hasConflict($court->id, $date, $start, $end, $ignoreId)) {
            $this->fail('start_hour', 'Jadwal bentrok! Slot jam tersebut sudah terisi atau sedang perawatan.');
        }

        return [
            'court_id' => $court->id,
            'booking_date' => $date,
            'start_time' => $this->toTime($start),
            'end_time' => $this->toTime($end),
            'duration_hours' => $duration,
            'price_per_hour' => $court->price_per_hour,
            'total_price' => $duration * $court->price_per_hour,
        ];
    }

    private function lock(Reservation $reservation): Reservation
    {
        return Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
    }

    private function ensureStatus(Reservation $reservation, string $status, string $message): void
    {
        if ($reservation->type !== 'booking' || $reservation->status !== $status) {
            $this->fail('status', $message);
        }
    }

    private function toTime(int $hour): string
    {
        return sprintf('%02d:00:00', $hour);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
