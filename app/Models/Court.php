<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Court extends Model
{
    protected $fillable = ['sport_category_id', 'name', 'description', 'price_per_hour', 'image', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'integer',
            'is_active' => 'boolean',
            'total_hours_used' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Gambar ikut terhapus saat data lapangan dihapus
        static::deleted(function (Court $court) {
            if ($court->image) {
                Storage::disk('public')->delete($court->image);
            }
        });
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Pencarian kata kunci dan filter kategori */
    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['q'] ?? null, fn (Builder $q, $keyword) => $q->where(function (Builder $sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            }))
            ->when($filter['category'] ?? null, fn (Builder $q, $id) => $q->where('sport_category_id', $id));
    }
}
