<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // "role" dan "points" sengaja tidak fillable agar tidak bisa diisi dari form
    protected $fillable = ['name', 'email', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Reservasi yang diverifikasi oleh user ini (khusus admin) */
    public function verifiedReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'verified_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
