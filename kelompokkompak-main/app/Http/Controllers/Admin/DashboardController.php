<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;

class DashboardController extends Controller
{
    public function __invoke(ReservationService $service)
    {
        $completed = Reservation::bookings()->where('status', 'completed');

        $stats = [
            'users' => User::where('role', 'user')->count(),
            'courts' => Court::count(),
            'pending' => Reservation::bookings()->where('status', 'pending')->count(),
            'completed' => (clone $completed)->count(),
            'revenue' => (clone $completed)->sum('total_price') + (clone $completed)->sum('penalty'),
        ];

        // Kuota menipis = sisa slot jam hari ini tinggal sedikit
        $lowSlots = Court::where('is_active', true)->orderBy('name')->get()
            ->map(function (Court $court) use ($service) {
                $court->remaining = $service->remainingSlotsToday($court);

                return $court;
            })
            ->filter(fn (Court $court) => $court->remaining <= config('booking.batas_slot_menipis'));

        $latest = Reservation::bookings()->with(['user', 'court'])->latest()->latest('id')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'lowSlots', 'latest'));
    }
}
