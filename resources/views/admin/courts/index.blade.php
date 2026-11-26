@extends('layouts.app')
@section('title', 'Lapangan')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Data Lapangan</h1>
    <a class="btn btn-primary" href="{{ route('admin.courts.create') }}">Tambah</a>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari lapangan"></div>
    <div class="col-md-3">
        <select class="form-select" name="category">
            <option value="">Semua kategori</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="active">
            <option value="">Semua status</option>
            <option value="1" @selected(request('active') === '1')>Aktif</option>
            <option value="0" @selected(request('active') === '0')>Nonaktif</option>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-secondary">Filter</button> <a class="btn btn-link" href="{{ route('admin.courts.index') }}">Reset</a></div>
</form>
<div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Gambar</th><th>Nama</th><th>Kategori</th><th>Tarif/jam</th><th>Jam terpakai</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        @forelse ($courts as $court)
            <tr>
                <td>
                    @if ($court->image)<img class="thumb" src="{{ asset('storage/'.$court->image) }}" alt="{{ $court->name }}">@else - @endif
                </td>
                <td>{{ $court->name }}</td>
                <td>{{ $court->sportCategory->name }}</td>
                <td>Rp {{ number_format($court->price_per_hour, 0, ',', '.') }}</td>
                <td>{{ $court->total_hours_used }}</td>
                <td><span class="badge text-bg-{{ $court->is_active ? 'success' : 'secondary' }}">{{ $court->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.courts.edit', $court) }}">Edit</a>
                    <form class="d-inline" method="POST" action="{{ route('admin.courts.destroy', $court) }}" data-confirm="Hapus lapangan ini beserta gambarnya?">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted">Data tidak ditemukan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
<div class="mt-3">{{ $courts->links() }}</div>
@endsection
