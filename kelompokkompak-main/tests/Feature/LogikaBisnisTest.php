<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Reservation;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengujian aturan bisnis dan relasi database (Reservasi Lapangan Padel).
 * Waktu "sekarang" dikunci ke 2026-12-01 08:00 WIB supaya hasil tes selalu sama.
 */
class LogikaBisnisTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;
    private User $other;
    private SportCategory $category;
    private Court $court;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-12-01 08:00:00', 'Asia/Jakarta'));

        $this->admin = $this->makeUser('admin@test.com', 'admin');
        $this->user = $this->makeUser('user@test.com', 'user');
        $this->other = $this->makeUser('lain@test.com', 'user');

        $this->category = SportCategory::create(['name' => 'Padel']);
        $this->court = Court::create([
            'sport_category_id' => $this->category->id,
            'name' => 'Padel A',
            'price_per_hour' => 150000,
            'is_active' => true,
        ]);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = new User(['name' => $email, 'email' => $email, 'password' => 'password']);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function payload(int $start, int $end, string $date = '2026-12-02'): array
    {
        return [
            'court_id' => $this->court->id,
            'booking_date' => $date,
            'start_hour' => $start,
            'end_hour' => $end,
            'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ];
    }

    private function book(User $as, int $start = 10, int $end = 12): Reservation
    {
        $this->actingAs($as)
            ->post(route('user.reservations.store'), $this->payload($start, $end))
            ->assertSessionHasNoErrors();

        return Reservation::latest('id')->firstOrFail();
    }

    private function approve(Reservation $reservation): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.approve', $reservation))
            ->assertSessionHasNoErrors();
    }

    // ---------------------------------------------------------------- Relasi

    public function test_relasi_eloquent_antar_tabel_berfungsi(): void
    {
        $r = $this->book($this->user, 10, 12);
        $this->approve($r);
        $r = $r->fresh();

        $this->assertTrue($r->user->is($this->user));
        $this->assertTrue($r->court->is($this->court));
        $this->assertTrue($r->verifier->is($this->admin));
        $this->assertTrue($this->court->sportCategory->is($this->category));
        $this->assertSame(1, $this->category->courts()->count());
        $this->assertSame(1, $this->court->reservations()->count());
        $this->assertSame(1, $this->user->reservations()->count());
        $this->assertSame(1, $this->admin->verifiedReservations()->count());
    }

    public function test_kategori_dan_lapangan_yang_masih_dipakai_tidak_bisa_dihapus(): void
    {
        $this->book($this->user);

        $this->actingAs($this->admin)
            ->delete(route('admin.courts.destroy', $this->court))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('courts', ['id' => $this->court->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $this->category))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('sport_categories', ['id' => $this->category->id]);
    }

    // ------------------------------------------------------ Validasi jadwal

    public function test_aturan_jadwal_yang_dilanggar_ditolak(): void
    {
        $user = $this->actingAs($this->user);

        // Di luar jam operasional (07.00 - 23.00)
        $user->post(route('user.reservations.store'), $this->payload(5, 7))->assertSessionHasErrors('start_hour');
        $user->post(route('user.reservations.store'), $this->payload(20, 24))->assertSessionHasErrors('end_hour');

        // Jam selesai tidak boleh sebelum jam mulai
        $user->post(route('user.reservations.store'), $this->payload(12, 10))->assertSessionHasErrors('end_hour');

        // Lebih dari maksimal 5 jam per pesanan
        $user->post(route('user.reservations.store'), $this->payload(8, 14))->assertSessionHasErrors('end_hour');

        // Tanggal sudah lewat dan terlalu jauh (maks 30 hari)
        $user->post(route('user.reservations.store'), $this->payload(10, 12, '2026-11-30'))->assertSessionHasErrors('booking_date');
        $user->post(route('user.reservations.store'), $this->payload(10, 12, '2027-02-01'))->assertSessionHasErrors('booking_date');

        // Hari ini, tetapi jam 07.00 sudah lewat (sekarang 08.00)
        $user->post(route('user.reservations.store'), $this->payload(7, 8, '2026-12-01'))->assertSessionHasErrors('start_hour');

        $this->assertSame(0, Reservation::count());
        $this->assertCount(0, Storage::disk('public')->files('payment-proofs'));
    }

    public function test_lapangan_nonaktif_tidak_bisa_dipesan(): void
    {
        $this->court->forceFill(['is_active' => false])->save();

        $this->actingAs($this->user)
            ->post(route('user.reservations.store'), $this->payload(10, 12))
            ->assertSessionHasErrors('court_id');

        $this->assertSame(0, Reservation::count());
    }

    // ------------------------------------------------- Alur status pesanan

    public function test_user_membatalkan_pending_dan_slot_kembali_tersedia(): void
    {
        $r = $this->book($this->user, 10, 12);

        $this->actingAs($this->user)
            ->patch(route('user.reservations.cancel', $r))
            ->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $r->fresh()->status);

        // Slot yang sama sekarang bisa dipesan user lain
        $this->actingAs($this->other)
            ->post(route('user.reservations.store'), $this->payload(10, 12))
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Reservation::count());
    }

    public function test_pesanan_yang_sudah_disetujui_tidak_bisa_diubah(): void
    {
        $r = $this->book($this->user, 10, 12);
        $this->approve($r);

        $this->actingAs($this->user)
            ->put(route('user.reservations.update', $r), $this->payload(15, 16))
            ->assertSessionHasErrors('status');

        $this->assertSame('10:00:00', $r->fresh()->start_time);
        // File bukti baru yang sempat diunggah ikut dibuang, hanya bukti asli yang tersisa
        $this->assertCount(1, Storage::disk('public')->files('payment-proofs'));
    }

    public function test_penolakan_wajib_alasan_dan_membebaskan_slot(): void
    {
        $r = $this->book($this->user, 10, 12);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.reject', $r))
            ->assertSessionHasErrors('admin_note');
        $this->assertSame('pending', $r->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.reject', $r), ['admin_note' => 'Bukti transfer tidak jelas'])
            ->assertSessionHasNoErrors();

        $r = $r->fresh();
        $this->assertSame('rejected', $r->status);
        $this->assertEquals($this->admin->id, $r->verified_by);

        $this->actingAs($this->other)
            ->post(route('user.reservations.store'), $this->payload(10, 12))
            ->assertSessionHasNoErrors();
    }

    public function test_persetujuan_ditolak_jika_jadwal_sudah_lewat(): void
    {
        $r = $this->book($this->user, 10, 12);

        $this->travelTo(Carbon::parse('2026-12-02 13:00:00', 'Asia/Jakarta'));

        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.approve', $r))
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $r->fresh()->status);
    }

    public function test_penyelesaian_yang_tidak_valid_tidak_mengubah_data_apa_pun(): void
    {
        $r = $this->book($this->user, 10, 12);
        $this->approve($r);

        // Jadwal belum dimulai (sekarang 1 Desember)
        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.complete', $r), ['overtime_hours' => 0])
            ->assertSessionHasErrors('status');

        // Jam kelebihan melewati jam tutup (maksimal 23 - 12 = 11 jam)
        $this->travelTo(Carbon::parse('2026-12-02 12:30:00', 'Asia/Jakarta'));
        $this->actingAs($this->admin)
            ->patch(route('admin.reservations.complete', $r), ['overtime_hours' => 12])
            ->assertSessionHasErrors('overtime_hours');

        // Tidak ada tabel yang berubah: status, poin user, dan jam pakai lapangan tetap
        $this->assertSame('approved', $r->fresh()->status);
        $this->assertSame(0, $this->user->fresh()->points);
        $this->assertSame(0, $this->court->fresh()->total_hours_used);
    }

    // -------------------------------------------------- Jadwal perawatan

    public function test_perawatan_tidak_bisa_menimpa_pesanan_yang_sudah_ada(): void
    {
        $this->book($this->user, 10, 12);

        $this->actingAs($this->admin)->post(route('admin.maintenance.store'), [
            'court_id' => $this->court->id,
            'booking_date' => '2026-12-02',
            'start_hour' => 11,
            'end_hour' => 13,
            'notes' => 'Ganti kaca',
        ])->assertSessionHasErrors('start_hour');

        $this->assertSame(0, Reservation::where('type', 'maintenance')->count());
    }

    public function test_perawatan_gratis_tidak_muncul_di_riwayat_user_dan_bisa_dihapus(): void
    {
        $this->actingAs($this->admin)->post(route('admin.maintenance.store'), [
            'court_id' => $this->court->id,
            'booking_date' => '2026-12-02',
            'start_hour' => 14,
            'end_hour' => 18,
            'notes' => 'Ganti rumput',
        ])->assertSessionHasNoErrors();

        $m = Reservation::where('type', 'maintenance')->firstOrFail();
        $this->assertSame(0, $m->total_price);
        $this->assertSame(4, $m->duration_hours);
        $this->assertSame('approved', $m->status);

        $this->actingAs($this->user)
            ->get(route('user.reservations.index'))
            ->assertViewHas('reservations', fn ($p) => $p->total() === 0);

        $this->actingAs($this->admin)->delete(route('admin.reservations.destroy', $m));
        $this->assertDatabaseMissing('reservations', ['id' => $m->id]);
    }

    public function test_pesanan_approved_tidak_boleh_dihapus_admin(): void
    {
        $r = $this->book($this->user, 10, 12);
        $this->approve($r);

        $this->actingAs($this->admin)
            ->delete(route('admin.reservations.destroy', $r))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('reservations', ['id' => $r->id]);
    }

    // ------------------------------------ Pencarian, filter, dan pagination

    public function test_pencarian_dan_filter_status_pada_riwayat_user(): void
    {
        $a = $this->book($this->user, 10, 12);
        $b = $this->book($this->user, 13, 14);
        $this->approve($b);

        $this->actingAs($this->user)
            ->get(route('user.reservations.index', ['status' => 'approved']))
            ->assertViewHas('reservations', fn ($p) => $p->total() === 1 && $p->first()->id === $b->id);

        $this->actingAs($this->user)
            ->get(route('user.reservations.index', ['status' => 'pending']))
            ->assertViewHas('reservations', fn ($p) => $p->total() === 1 && $p->first()->id === $a->id);

        $this->actingAs($this->user)
            ->get(route('user.reservations.index', ['q' => $a->code]))
            ->assertViewHas('reservations', fn ($p) => $p->total() === 1 && $p->first()->id === $a->id);
    }

    public function test_pagination_mempertahankan_filter_di_url(): void
    {
        // 12 pesanan 1 jam berurutan (07.00 - 19.00), per halaman 10 data
        for ($i = 0; $i < 12; $i++) {
            $this->book($this->user, 7 + $i, 8 + $i);
        }

        $this->actingAs($this->user)
            ->get(route('user.reservations.index', ['status' => 'pending']))
            ->assertViewHas('reservations', fn ($p) => $p->count() === 10
                && $p->total() === 12
                && $p->hasMorePages()
                && str_contains($p->url(2), 'status=pending'));

        $this->actingAs($this->user)
            ->get(route('user.reservations.index', ['status' => 'pending', 'page' => 2]))
            ->assertViewHas('reservations', fn ($p) => $p->count() === 2);
    }
}
