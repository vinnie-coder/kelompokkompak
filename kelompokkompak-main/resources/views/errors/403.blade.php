@extends('layouts.app')
@section('title', 'Akses ditolak')
@section('content')
<div class="text-center py-5">
    <h1 class="display-4">403</h1>
    <p>Anda tidak memiliki akses ke halaman ini.</p>
    <a class="btn btn-primary" href="{{ url('/dashboard') }}">Kembali</a>
</div>
@endsection
