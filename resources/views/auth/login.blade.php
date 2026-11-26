@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card"><div class="card-body p-4">
            <h1 class="h4 mb-1">ArenaKu</h1>
            <p class="text-muted">Reservasi lapangan futsal dan padel</p>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
                <button class="btn btn-primary w-100">Masuk</button>
            </form>
            <p class="mt-3 mb-0 text-center">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
        </div></div>
    </div>
</div>
@endsection
