<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationRequest;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $service) {}

    public function index(Request $request)
    {
        $reservations = $request->user()->reservations()
            ->bookings()
            ->with('court.sportCategory')
            ->filter($request->only(['q', 'status', 'category', 'date']))
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('user.reservations.index', compact('reservations'));
    }

    public function create()
    {
        return view('user.reservations.form', [
            'reservation' => new Reservation,
            'courts' => Court::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ReservationRequest $request)
    {
        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        try {
            $reservation = $this->service->create($request->user(), $request->validated(), $path);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path); // buang file jika transaksi gagal
            throw $e;
        }

        return redirect()->route('user.reservations.show', $reservation)
            ->with('success', 'Reservasi dibuat. Menunggu verifikasi admin.');
    }

    public function show(Reservation $reservation)
    {
        $this->authorizeOwner($reservation);
        $reservation->load('court.sportCategory');

        return view('user.reservations.show', compact('reservation'));
    }

    public function edit(Reservation $reservation)
    {
        $this->authorizeOwner($reservation);

        if (! $reservation->isPending()) {
            return redirect()->route('user.reservations.show', $reservation)
                ->with('error', 'Hanya pesanan berstatus Pending yang bisa diubah.');
        }

        return view('user.reservations.form', [
            'reservation' => $reservation,
            'courts' => Court::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(ReservationRequest $request, Reservation $reservation)
    {
        $this->authorizeOwner($reservation);

        $path = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('payment-proofs', 'public')
            : null;

        try {
            $this->service->update($reservation, $request->validated(), $path);
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return redirect()->route('user.reservations.show', $reservation)->with('success', 'Reservasi diperbarui.');
    }

    public function cancel(Reservation $reservation)
    {
        $this->authorizeOwner($reservation);
        $this->service->cancel($reservation);

        return redirect()->route('user.reservations.show', $reservation)->with('success', 'Reservasi dibatalkan.');
    }

    private function authorizeOwner(Reservation $reservation): void
    {
        abort_if($reservation->user_id !== auth()->id() || $reservation->type !== 'booking', 403);
    }
}
