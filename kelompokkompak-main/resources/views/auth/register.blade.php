@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card"><div class="card-body p-4">
            <h1 class="h4 mb-3">Daftar Akun</h1>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                @foreach ([['name', 'Nama', 'text'], ['email', 'Email', 'email'], ['phone', 'Nomor HP', 'text'], ['password', 'Password (min. 8 karakter)', 'password'], ['password_confirmation', 'Ulangi password', 'password']] as [$field, $label, $type])
                    <div class="mb-3">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <input class="form-control" type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" value="{{ $type === 'password' ? '' : old($field) }}" required>
                    </div>
                @endforeach
                <button class="btn btn-primary w-100">Daftar</button>
            </form>
            <p class="mt-3 mb-0 text-center">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div></div>
    </div>
</div>
@endsection
