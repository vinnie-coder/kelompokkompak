@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<h1 class="h4 mb-3">Dashboard Admin</h1>
<div class="row g-3 mb-4">
    @foreach ([
        ['Total user', $stats['users']],
        ['Total lapangan', $stats['courts']],
        ['Transaksi pending', $stats['pending']],
        ['Transaksi selesai', $stats['completed']],
        ['Pendapatan selesai', 'Rp '.number_format($stats['revenue'], 0, ',', '.')],
    ] as [$label, $value])
        <div class="col-6 col-lg">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-4 fw-bold">{{ $value }}</div>
            </div></div>
        </div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h2 class="h6">Kuota slot hari ini menipis (&le; {{ config('booking.batas_slot_menipis') }} jam)</h2>
            <ul class="list-group list-group-flush">
                @forelse ($lowSlots as $court)
                    <li class="list-group-item d-flex justify-content-between px-0">
                        {{ $court->name }} <span class="badge text-bg-danger">sisa {{ $court->remaining }} jam</span>
                    </li>
                @empty
                    <li class="list-group-item px-0 text-muted">Semua lapangan masih longgar.</li>
                @endforelse
            </ul>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <h2 class="h6">Transaksi terbaru</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Kode</th><th>User</th><th>Lapangan</th><th>Jadwal</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($latest as $r)
                        <tr>
                            <td><a href="{{ route('admin.reservations.show', $r) }}">{{ $r->code }}</a></td>
                            <td>{{ $r->user->name }}</td>
                            <td>{{ $r->court->name }}</td>
                            <td>{{ $r->booking_date->format('d-m-Y') }}<br><small>{{ $r->timeRange() }}</small></td>
                            <td>@include('partials.badge', ['r' => $r])</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada transaksi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
@endsection
