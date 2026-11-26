@extends('layouts.app')
@section('title', 'Pesanan Saya')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Riwayat Pesanan Saya</h1>
    <a class="btn btn-primary" href="{{ route('user.reservations.create') }}">Pesan lapangan</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari kode / lapangan"></div>
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">Semua status</option>
            @foreach (\App\Models\Reservation::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input class="form-control" type="date" name="date" value="{{ request('date') }}" aria-label="Tanggal main"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button> <a class="btn btn-link" href="{{ route('user.reservations.index') }}">Reset</a></div>
</form>
<div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Kode</th><th>Lapangan</th><th>Jadwal</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        @forelse ($reservations as $r)
            <tr>
                <td>{{ $r->code }}</td>
                <td>{{ $r->court->name }}</td>
                <td>{{ $r->booking_date->format('d-m-Y') }}<br><small>{{ $r->timeRange() }}</small></td>
                <td>Rp {{ number_format($r->grandTotal(), 0, ',', '.') }}</td>
                <td>@include('partials.badge', ['r' => $r])</td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('user.reservations.show', $r) }}">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">Data tidak ditemukan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $reservations->links() }}</div>
@endsection
