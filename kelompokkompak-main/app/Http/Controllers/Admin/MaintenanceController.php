<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function create()
    {
        return view('admin.maintenance.create', ['courts' => Court::orderBy('name')->get()]);
    }

    public function store(Request $request, ReservationService $service)
    {
        $open = config('booking.jam_buka');
        $close = config('booking.jam_tutup');

        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_hour' => ['required', 'integer', "between:{$open},".($close - 1)],
            'end_hour' => ['required', 'integer', 'gt:start_hour', 'between:'.($open + 1).",{$close}"],
            'notes' => ['required', 'string', 'max:500'],
        ], [
            'booking_date.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
        ]);

        $service->blockMaintenance($request->user(), $data);

        return redirect()->route('admin.reservations.index', ['type' => 'maintenance'])
            ->with('success', 'Jadwal perawatan berhasil diblokir.');
    }
}
