<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $mine = fn () => $user->reservations()->bookings();

        $stats = [
            'total' => $mine()->count(),
            'pending' => $mine()->where('status', 'pending')->count(),
            'approved' => $mine()->where('status', 'approved')->count(),
            'completed' => $mine()->where('status', 'completed')->count(),
            'spending' => $mine()->where('status', 'completed')->sum('total_price')
                + $mine()->where('status', 'completed')->sum('penalty'),
            'points' => $user->points,
        ];

        $latest = $mine()->with('court')->latest()->latest('id')->take(5)->get();

        return view('user.dashboard', compact('stats', 'latest'));
    }
}
