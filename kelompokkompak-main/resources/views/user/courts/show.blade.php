@extends('layouts.app')
@section('title', $court->name)
@section('content')
<a href="{{ route('user.courts.index') }}">&larr; Katalog</a>
<div class="row g-3 mt-1">
    <div class="col-lg-5">
        <div class="card">
            @include('partials.court-image')
            <div class="card-body">
                <h1 class="h4">{{ $court->name }}</h1>
                <p>{{ $court->description }}</p>
                <p class="fw-bold">Rp {{ number_format($court->price_per_hour, 0, ',', '.') }} / jam</p>
                <a class="btn btn-primary" href="{{ route('user.reservations.create', ['court_id' => $court->id, 'date' => $date]) }}">Pesan lapangan ini</a>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h2 class="h6">Ketersediaan slot</h2>
            <form class="d-flex gap-2 mb-3">
                <input class="form-control" type="date" name="date" value="{{ $date }}" aria-label="Tanggal">
                <button class="btn btn-secondary">Cek</button>
            </form>
            <div class="row g-2">
                @for ($h = config('booking.jam_buka'); $h < config('booking.jam_tutup'); $h++)
                    @php
                        $past = \Illuminate\Support\Carbon::parse($date)->setTime($h, 0)->isPast();
                        [$label, $color] = match (true) {
                            ($booked[$h] ?? null) === 'maintenance' => ['Perawatan', 'dark'],
                            isset($booked[$h]) => ['Terisi', 'danger'],
                            $past => ['Lewat', 'secondary'],
                            default => ['Tersedia', 'success'],
                        };
                    @endphp
                    <div class="col-6 col-md-4">
                        <div class="border border-{{ $color }} rounded p-2 text-center">
                            <div>{{ sprintf('%02d:00 - %02d:00', $h, $h + 1) }}</div>
                            <span class="badge text-bg-{{ $color }}">{{ $label }}</span>
                        </div>
                    </div>
                @endfor
            </div>
        </div></div>
    </div>
</div>
@endsection
