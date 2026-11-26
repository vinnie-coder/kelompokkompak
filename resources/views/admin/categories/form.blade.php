@extends('layouts.app')
@section('title', $category->exists ? 'Edit Kategori' : 'Tambah Kategori')
@section('content')
<h1 class="h4 mb-3">{{ $category->exists ? 'Edit' : 'Tambah' }} Kategori</h1>
<div class="card col-md-7"><div class="card-body">
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <div class="mb-3">
            <label class="form-label" for="name">Nama</label>
            <input class="form-control" id="name" name="name" value="{{ old('name', $category->name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
        </div>
        <button class="btn btn-primary">Simpan</button>
        <a class="btn btn-link" href="{{ route('admin.categories.index') }}">Batal</a>
    </form>
</div></div>
@endsection
