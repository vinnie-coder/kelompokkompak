@extends('layouts.app')
@section('title', 'Katalog Lapangan')
@section('content')
<h1 class="h4 mb-3">Katalog Lapangan</h1>
<form class="row g-2 mb-3">
    <div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari lapangan"></div>
    <div class="col-md-3">
        <select class="form-select" name="category" onchange="this.form.submit()">
            <option value="">Semua kategori</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-secondary">Cari</button> <a class="btn btn-link" href="{{ route('user.courts.index') }}">Reset</a></div>
</form>
<div class="row g-3">
    @forelse ($courts as $court)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                @include('partials.court-image')
                <div class="card-body d-flex flex-column">
                    <span class="badge text-bg-info align-self-start">{{ $court->sportCategory->name }}</span>
                    <h2 class="h5 mt-2">{{ $court->name }}</h2>
                    <p class="text-muted">{{ $court->description }}</p>
                    <p class="fw-bold mt-auto">Rp {{ number_format($court->price_per_hour, 0, ',', '.') }} / jam</p>
                    <a class="btn btn-primary" href="{{ route('user.courts.show', $court) }}">Lihat jadwal</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center text-muted py-5">Lapangan tidak ditemukan.</div>
    @endforelse
</div>
<div class="mt-3">{{ $courts->links() }}</div>
@endsection
