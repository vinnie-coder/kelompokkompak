@extends('layouts.app')
@section('title', 'Reservasi')
@section('content')
<h1 class="h4 mb-3">{{ $type === 'maintenance' ? 'Jadwal Perawatan' : 'Semua Reservasi' }}</h1>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link {{ $type === 'booking' ? 'active' : '' }}" href="{{ route('admin.reservations.index') }}">Transaksi</a></li>
    <li class="nav-item"><a class="nav-link {{ $type === 'maintenance' ? 'active' : '' }}" href="{{ route('admin.reservations.index', ['type' => 'maintenance']) }}">Perawatan</a></li>
</ul>
<form class="row g-2 mb-3">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="col-md-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama / lapangan"></div>
    @if ($type === 'booking')
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">Semua status</option>
                @foreach (\App\Models\Reservation::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-md-2">
        <select class="form-select" name="category">
            <option value="">Semua kategori</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input class="form-control" type="date" name="date" value="{{ request('date') }}" aria-label="Tanggal main"></div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button> <a class="btn btn-link" href="{{ route('admin.reservations.index', ['type' => $type]) }}">Reset</a></div>
</form>
<div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Kode</th><th>{{ $type === 'maintenance' ? 'Dibuat oleh' : 'User' }}</th><th>Lapangan</th><th>Jadwal</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        @forelse ($reservations as $r)
            <tr>
                <td>{{ $r->code }}</td>
                <td>{{ $r->user->name }}</td>
                <td>{{ $r->court->name }}</td>
                <td>{{ $r->booking_date->format('d-m-Y') }}<br><small>{{ $r->timeRange() }}</small></td>
                <td>Rp {{ number_format($r->grandTotal(), 0, ',', '.') }}</td>
                <td>
                    @if ($type === 'maintenance')<span class="badge text-bg-dark">Perawatan</span>@else @include('partials.badge', ['r' => $r]) @endif
                </td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reservations.show', $r) }}">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">Data tidak ditemukan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $reservations->links() }}</div>
@endsection
