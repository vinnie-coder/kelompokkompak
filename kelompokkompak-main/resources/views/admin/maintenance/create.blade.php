@extends('layouts.app')
@section('title', 'Blokir Perawatan')
@section('content')
<h1 class="h4 mb-3">Blokir Jadwal Perawatan</h1>
<div class="card col-md-8"><div class="card-body">
    <form method="POST" action="{{ route('admin.maintenance.store') }}" data-confirm="Blokir jadwal ini?">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="court_id">Lapangan</label>
            <select class="form-select" id="court_id" name="court_id" required>
                <option value="">Pilih lapangan</option>
                @foreach ($courts as $court)
                    <option value="{{ $court->id }}" @selected(old('court_id') == $court->id)>{{ $court->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="booking_date">Tanggal</label>
            <input class="form-control" type="date" id="booking_date" name="booking_date" min="{{ today()->toDateString() }}" value="{{ old('booking_date') }}" required>
        </div>
        <div class="row">
            <div class="col mb-3">
                <label class="form-label" for="start_hour">Jam mulai</label>
                <select class="form-select" id="start_hour" name="start_hour">
                    @for ($h = config('booking.jam_buka'); $h < config('booking.jam_tutup'); $h++)
                        <option value="{{ $h }}" @selected(old('start_hour') == $h)>{{ sprintf('%02d:00', $h) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col mb-3">
                <label class="form-label" for="end_hour">Jam selesai</label>
                <select class="form-select" id="end_hour" name="end_hour">
                    @for ($h = config('booking.jam_buka') + 1; $h <= config('booking.jam_tutup'); $h++)
                        <option value="{{ $h }}" @selected(old('end_hour') == $h)>{{ sprintf('%02d:00', $h) }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="notes">Keterangan perawatan</label>
            <input class="form-control" id="notes" name="notes" value="{{ old('notes') }}" maxlength="500" required>
        </div>
        <button class="btn btn-primary">Blokir jadwal</button>
    </form>
</div></div>
@endsection
