<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Reservation extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /** Status yang membuat slot jam tidak bisa dipesan orang lain */
    public const BLOCKING_STATUSES = ['pending', 'approved', 'completed'];

    protected $fillable = [
        'code', 'user_id', 'court_id', 'type', 'booking_date', 'start_time', 'end_time',
        'duration_hours', 'price_per_hour', 'total_price', 'overtime_hours', 'penalty', 'status',
        'payment_proof', 'notes', 'admin_note', 'verified_by', 'verified_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'duration_hours' => 'integer',
            'price_per_hour' => 'integer',
            'total_price' => 'integer',
            'overtime_hours' => 'integer',
            'penalty' => 'integer',
            'verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Reservation $reservation) {
            if ($reservation->payment_proof) {
                Storage::disk('public')->delete($reservation->payment_proof);
            }
        });
    }

    public static function generateCode(string $prefix = 'RSV'): string
    {
        do {
            $code = $prefix.'-'.Str::upper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeBookings(Builder $query): Builder
    {
        return $query->where('type', 'booking');
    }

    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['q'] ?? null, fn (Builder $q, $keyword) => $q->where(function (Builder $sub) use ($keyword) {
                $sub->where('code', 'like', "%{$keyword}%")
                    ->orWhereHas('court', fn (Builder $c) => $c->where('name', 'like', "%{$keyword}%"))
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$keyword}%"));
            }))
            ->when($filter['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filter['category'] ?? null, fn (Builder $q, $id) => $q->whereHas(
                'court', fn (Builder $c) => $c->where('sport_category_id', $id)
            ))
            ->when($filter['date'] ?? null, fn (Builder $q, $date) => $q->whereDate('booking_date', $date));
    }

    public function startsAt(): \Illuminate\Support\Carbon
    {
        return $this->booking_date->copy()->setTimeFromTimeString($this->start_time);
    }

    public function endsAt(): \Illuminate\Support\Carbon
    {
        return $this->booking_date->copy()->setTimeFromTimeString($this->end_time);
    }

    public function timeRange(): string
    {
        return substr($this->start_time, 0, 5).' - '.substr($this->end_time, 0, 5);
    }

    public function grandTotal(): int
    {
        return $this->total_price + $this->penalty;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function badgeColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }
}
