<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\SportCategory;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $service) {}

    public function index(Request $request)
    {
        $type = $request->query('type') === 'maintenance' ? 'maintenance' : 'booking';

        $reservations = Reservation::with(['user', 'court.sportCategory'])
            ->where('type', $type)
            ->filter($request->only(['q', 'status', 'category', 'date']))
            ->latest()
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $categories = SportCategory::orderBy('name')->get();

        return view('admin.reservations.index', compact('reservations', 'categories', 'type'));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'court.sportCategory', 'verifier']);

        return view('admin.reservations.show', compact('reservation'));
    }

    public function approve(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);
        $this->service->approve($reservation, $request->user(), $data['admin_note'] ?? null);

        return back()->with('success', 'Pesanan disetujui.');
    }

    public function reject(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['admin_note' => ['required', 'string', 'max:500']], [
            'admin_note.required' => 'Alasan penolakan wajib diisi.',
        ]);
        $this->service->reject($reservation, $request->user(), $data['admin_note']);

        return back()->with('success', 'Pesanan ditolak.');
    }

    public function complete(Request $request, Reservation $reservation)
    {
        // Nominal denda tidak diterima dari form, hanya jumlah jam kelebihan
        $data = $request->validate([
            'overtime_hours' => ['required', 'integer', 'min:0'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->complete($reservation, (int) $data['overtime_hours'], $data['admin_note'] ?? null);

        return back()->with('success', 'Reservasi diselesaikan. Denda, poin user, dan jam pakai lapangan diperbarui.');
    }

    public function destroy(Reservation $reservation)
    {
        $removable = $reservation->type === 'maintenance'
            || in_array($reservation->status, ['rejected', 'cancelled']);

        if (! $removable) {
            return back()->with('error', 'Hanya jadwal perawatan atau pesanan ditolak/dibatalkan yang bisa dihapus.');
        }

        $type = $reservation->type;
        $reservation->delete(); // bukti transfer terhapus lewat event model

        return redirect()->route('admin.reservations.index', ['type' => $type])->with('success', 'Data dihapus.');
    }
}
