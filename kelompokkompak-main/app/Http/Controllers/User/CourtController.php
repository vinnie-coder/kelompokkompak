<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\SportCategory;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CourtController extends Controller
{
    public function index(Request $request)
    {
        $courts = Court::with('sportCategory')
            ->where('is_active', true)
            ->filter($request->only(['q', 'category']))
            ->orderBy('name')
            ->paginate(6)
            ->withQueryString();

        $categories = SportCategory::orderBy('name')->get();

        return view('user.courts.index', compact('courts', 'categories'));
    }

    public function show(Request $request, Court $court, ReservationService $service)
    {
        abort_unless($court->is_active, 404);

        $request->validate(['date' => ['nullable', 'date']]);
        $date = Carbon::parse($request->query('date') ?: today())->toDateString();
        $booked = $service->bookedHours($court->id, $date);

        return view('user.courts.show', compact('court', 'date', 'booked'));
    }
}
