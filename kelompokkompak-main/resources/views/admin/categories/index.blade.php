@extends('layouts.app')
@section('title', 'Kategori')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Kategori Olahraga</h1>
    <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Tambah</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari nama kategori"></div>
    <div class="col-auto"><button class="btn btn-secondary">Cari</button> <a class="btn btn-link" href="{{ route('admin.categories.index') }}">Reset</a></div>
</form>
<div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Nama</th><th>Deskripsi</th><th>Jumlah lapangan</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        @forelse ($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description ?: '-' }}</td>
                <td>{{ $category->courts_count }}</td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                    <form class="d-inline" method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Hapus kategori ini?">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">Data tidak ditemukan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $categories->links() }}</div>
@endsection
