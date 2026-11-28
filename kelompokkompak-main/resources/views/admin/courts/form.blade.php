@extends('layouts.app')
@section('title', $court->exists ? 'Edit Lapangan' : 'Tambah Lapangan')
@section('content')
<h1 class="h4 mb-3">{{ $court->exists ? 'Edit' : 'Tambah' }} Lapangan</h1>
<div class="card col-md-8"><div class="card-body">
    <form method="POST" enctype="multipart/form-data" action="{{ $court->exists ? route('admin.courts.update', $court) : route('admin.courts.store') }}">
        @csrf
        @if ($court->exists) @method('PUT') @endif
        <div class="mb-3">
            <label class="form-label" for="sport_category_id">Kategori</label>
            <select class="form-select" id="sport_category_id" name="sport_category_id" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('sport_category_id', $court->sport_category_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="name">Nama lapangan</label>
            <input class="form-control" id="name" name="name" value="{{ old('name', $court->name) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $court->description) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label" for="price_per_hour">Tarif per jam (Rp)</label>
            <input class="form-control" type="number" min="1000" id="price_per_hour" name="price_per_hour" value="{{ old('price_per_hour', $court->price_per_hour) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="image">Gambar (JPG/PNG/WEBP, maks. 2 MB)</label>
            @if ($court->image)
                <div class="mb-2"><img class="thumb" src="{{ asset('storage/'.$court->image) }}" alt="{{ $court->name }}"> <small class="text-muted">Unggah baru untuk mengganti.</small></div>
            @endif
            <input class="form-control" type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked($errors->any() ? old('is_active') : $court->is_active)>
            <label class="form-check-label" for="is_active">Lapangan aktif (bisa dipesan)</label>
        </div>
        <button class="btn btn-primary">Simpan</button>
        <a class="btn btn-link" href="{{ route('admin.courts.index') }}">Batal</a>
    </form>
</div></div>
@endsection
