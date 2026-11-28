@extends('layouts.app')
@section('title', $reservation->exists ? 'Ubah Pesanan' : 'Pesan Lapangan')
@section('content')
@php
    $courtId = old('court_id', $reservation->exists ? $reservation->court_id : request('court_id'));
    $date = old('booking_date', $reservation->exists ? $reservation->booking_date->toDateString() : request('date'));
    $start = old('start_hour', $reservation->exists ? (int) substr($reservation->start_time, 0, 2) : config('booking.jam_buka'));
    $end = old('end_hour', $reservation->exists ? (int) substr($reservation->end_time, 0, 2) : config('booking.jam_buka') + 1);
@endphp
<h1 class="h4 mb-3">{{ $reservation->exists ? 'Ubah Pesanan '.$reservation->code : 'Pesan Lapangan' }}</h1>
<div class="card col-lg-8"><div class="card-body">
    <form method="POST" enctype="multipart/form-data" action="{{ $reservation->exists ? route('user.reservations.update', $reservation) : route('user.reservations.store') }}">
        @csrf
        @if ($reservation->exists) @method('PUT') @endif
        <div class="mb-3">
            <label class="form-label" for="court_id">Lapangan</label>
            <select class="form-select" id="court_id" name="court_id" required>
                <option value="" data-price="0">Pilih lapangan</option>
                @foreach ($courts as $court)
                    <option value="{{ $court->id }}" data-price="{{ $court->price_per_hour }}" @selected($courtId == $court->id)>
                        {{ $court->name }} - Rp {{ number_format($court->price_per_hour, 0, ',', '.') }}/jam
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="booking_date">Tanggal main</label>
            <input class="form-control" type="date" id="booking_date" name="booking_date" value="{{ $date }}" min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" required>
        </div>
        <div class="row">
            <div class="col mb-3">
                <label class="form-label" for="start_hour">Jam mulai</label>
                <select class="form-select" id="start_hour" name="start_hour">
                    @for ($h = config('booking.jam_buka'); $h < config('booking.jam_tutup'); $h++)
                        <option value="{{ $h }}" @selected($start == $h)>{{ sprintf('%02d:00', $h) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col mb-3">
                <label class="form-label" for="end_hour">Jam selesai</label>
                <select class="form-select" id="end_hour" name="end_hour">
                    @for ($h = config('booking.jam_buka') + 1; $h <= config('booking.jam_tutup'); $h++)
                        <option value="{{ $h }}" @selected($end == $h)>{{ sprintf('%02d:00', $h) }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="alert alert-info">Perkiraan biaya: <strong id="estimate">-</strong><br><small>Total resmi dihitung oleh server.</small></div>
        <div class="mb-3">
            <label class="form-label" for="payment_proof">Bukti transfer (JPG/PNG/PDF, maks. 2 MB)</label>
            <input class="form-control" type="file" id="payment_proof" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" @required(! $reservation->exists)>
            @if ($reservation->payment_proof)
                <small class="text-muted">Sudah ada <a href="{{ asset('storage/'.$reservation->payment_proof) }}" target="_blank" rel="noopener">file bukti</a>. Unggah baru untuk mengganti.</small>
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label" for="notes">Catatan (opsional)</label>
            <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500">{{ old('notes', $reservation->notes) }}</textarea>
        </div>
        <button class="btn btn-primary">Simpan</button>
        <a class="btn btn-link" href="{{ route('user.reservations.index') }}">Batal</a>
    </form>
</div></div>
@endsection

@push('scripts')
<script>
    const court = document.getElementById('court_id');
    const startHour = document.getElementById('start_hour');
    const endHour = document.getElementById('end_hour');

    function estimate() {
        const price = Number(court.options[court.selectedIndex].dataset.price);
        const hours = endHour.value - startHour.value;
        document.getElementById('estimate').textContent =
            price > 0 && hours > 0 ? hours + ' jam = Rp ' + (hours * price).toLocaleString('id-ID') : '-';
    }

    [court, startHour, endHour].forEach(el => el.addEventListener('change', estimate));
    estimate();
</script>
@endpush
