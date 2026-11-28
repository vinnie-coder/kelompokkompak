@extends('layouts.app')
@section('title', $reservation->code)
@section('content')
<a href="{{ route('admin.reservations.index', ['type' => $reservation->type]) }}">&larr; Kembali</a>
<h1 class="h4 my-3">Detail {{ $reservation->type === 'maintenance' ? 'Perawatan' : 'Reservasi' }}</h1>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <p><strong>{{ $reservation->type === 'maintenance' ? 'Dibuat oleh' : 'Pemesan' }}:</strong>
                {{ $reservation->user->name }} ({{ $reservation->user->email }}, {{ $reservation->user->phone ?: '-' }})</p>
            @include('partials.reservation-detail')
            @if ($reservation->verifier)
                <p class="text-muted small mt-3 mb-0">Diverifikasi oleh {{ $reservation->verifier->name }} pada {{ $reservation->verified_at?->format('d-m-Y H:i') }}</p>
            @endif
        </div></div>
    </div>
    <div class="col-lg-5">
        @if ($reservation->type === 'booking' && $reservation->isPending())
            <div class="card mb-3"><div class="card-body">
                <h2 class="h6">Setujui</h2>
                <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}" data-confirm="Bukti transfer sudah benar? Setujui pesanan ini?">
                    @csrf @method('PATCH')
                    <input class="form-control mb-2" name="admin_note" placeholder="Catatan (opsional)" maxlength="500">
                    <button class="btn btn-success">Approve</button>
                </form>
            </div></div>
            <div class="card"><div class="card-body">
                <h2 class="h6">Tolak</h2>
                <form method="POST" action="{{ route('admin.reservations.reject', $reservation) }}" data-confirm="Tolak pesanan ini?">
                    @csrf @method('PATCH')
                    <input class="form-control mb-2" name="admin_note" placeholder="Alasan penolakan" maxlength="500" required>
                    <button class="btn btn-danger">Reject</button>
                </form>
            </div></div>
        @elseif ($reservation->type === 'booking' && $reservation->status === 'approved')
            <div class="card"><div class="card-body">
                <h2 class="h6">Selesaikan sewa</h2>
                @if ($reservation->startsAt()->isFuture())
                    <p class="text-muted mb-0">Bisa diselesaikan setelah jadwal dimulai.</p>
                @else
                    <form method="POST" action="{{ route('admin.reservations.complete', $reservation) }}" data-confirm="Selesaikan reservasi ini?">
                        @csrf @method('PATCH')
                                               <label class="form-label" for="overtime_hours">Kelebihan main (jam), isi 0 jika tepat waktu</label>
                        <input class="form-control" type="number" min="0" max="{{ config('booking.jam_tutup') - (int) substr($reservation->end_time, 0, 2) }}" id="overtime_hours" name="overtime_hours" value="{{ old('overtime_hours', 0) }}" required>
                        <div class="form-text mb-2">Denda dihitung otomatis: jam kelebihan &times; Rp {{ number_format($reservation->price_per_hour, 0, ',', '.') }}.</div>
                        <input class="form-control mb-2" name="admin_note" value="{{ old('admin_note') }}" placeholder="Catatan (opsional)" maxlength="500">
                        <button class="btn btn-primary">Completed</button>
                    </form>
                @endif
            </div></div>
        @elseif ($reservation->type === 'maintenance' || in_array($reservation->status, ['rejected', 'cancelled']))
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}" data-confirm="Hapus data ini secara permanen?">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger">{{ $reservation->type === 'maintenance' ? 'Buka blokir (hapus)' : 'Hapus data' }}</button>
                </form>
            </div></div>
        @endif
    </div>
</div>
@endsection
