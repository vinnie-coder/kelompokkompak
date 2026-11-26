@extends('layouts.app')
@section('title', $reservation->code)
@section('content')
<a href="{{ route('user.reservations.index') }}">&larr; Pesanan saya</a>
<h1 class="h4 my-3">Detail Pesanan</h1>
<div class="card col-lg-8"><div class="card-body">
    @include('partials.reservation-detail')
    @if ($reservation->isPending())
        <hr>
        <a class="btn btn-outline-primary" href="{{ route('user.reservations.edit', $reservation) }}">Ubah</a>
        <form class="d-inline" method="POST" action="{{ route('user.reservations.cancel', $reservation) }}" data-confirm="Batalkan pesanan ini?">
            @csrf @method('PATCH')
            <button class="btn btn-outline-danger">Batalkan pesanan</button>
        </form>
    @endif
</div></div>
@endsection
