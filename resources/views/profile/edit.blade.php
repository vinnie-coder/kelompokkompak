@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card"><div class="card-body p-4">
            <h1 class="h4 mb-3">Profil Saya</h1>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf @method('PUT')
                @foreach ([['name', 'Nama', 'text'], ['email', 'Email', 'text'], ['phone', 'Nomor HP', 'text']] as [$field, $label, $type])
                    <div class="mb-3">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <input class="form-control" type="{{ $type }}" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $user->$field) }}">
                    </div>
                @endforeach
                <hr>
                <p class="text-muted small">Kosongkan jika tidak ingin mengganti password.</p>
                @foreach ([['current_password', 'Password lama'], ['password', 'Password baru'], ['password_confirmation', 'Ulangi password baru']] as [$field, $label])
                    <div class="mb-3">
                        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                        <input class="form-control" type="password" id="{{ $field }}" name="{{ $field }}">
                    </div>
                @endforeach
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
