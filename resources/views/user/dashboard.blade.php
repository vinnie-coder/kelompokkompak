@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Halo, {{ auth()->user()->name }}</h1>
    <a class="btn btn-primary" href="{{ route('user.reservations.create') }}">Pesan lapangan</a>
</div>
<div class="row g-3 mb-4">
    @foreach ([
        ['Total pesanan', $stats['total']],
        ['Pending', $stats['pending']],
        ['Approved', $stats['approved']],
        ['Completed', $stats['completed']],
        ['Total belanja', 'Rp '.number_format($stats['spending'], 0, ',', '.')],
        ['Poin', $stats['points']],
    ] as [$label, $value])
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-5 fw-bold">{{ $value }}</div>
            </div></div>
        </div>
    @endforeach
</div>
<div class="card"><div class="card-body">
    <h2 class="h6">Status pengajuan terbaru</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Kode</th><th>Lapangan</th><th>Jadwal</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($latest as $r)
                <tr>
                    <td><a href="{{ route('user.reservations.show', $r) }}">{{ $r->code }}</a></td>
                    <td>{{ $r->court->name }}</td>
                    <td>{{ $r->booking_date->format('d-m-Y') }}, {{ $r->timeRange() }}</td>
                    <td>@include('partials.badge', ['r' => $r])</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">Belum ada pesanan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div>
@endsection
