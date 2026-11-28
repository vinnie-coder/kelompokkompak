<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Reservasi') - Sportify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; }
        .navbar-admin { background: #1f2937; }
        .navbar-user { background: #0f766e; }
        .card { border: 0; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .court-img { height: 180px; object-fit: cover; width: 100%; }
        .court-empty { height: 180px; display: flex; align-items: center; justify-content: center; background: #dbe4e8; color: #51636d; }
        .thumb { width: 72px; height: 48px; object-fit: cover; border-radius: 4px; }
    </style>
</head>
<body>
@auth
@php($admin = auth()->user()->isAdmin())
<nav class="navbar navbar-expand-lg navbar-dark {{ $admin ? 'navbar-admin' : 'navbar-user' }}">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Sportify {{ $admin ? '· Admin' : '' }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                @if ($admin)
                    @foreach ([
                        ['admin.dashboard', 'admin.dashboard', 'Dashboard'],
                        ['admin.categories.index', 'admin.categories.*', 'Kategori'],
                        ['admin.courts.index', 'admin.courts.*', 'Lapangan'],
                        ['admin.reservations.index', 'admin.reservations.*', 'Reservasi'],
                        ['admin.maintenance.create', 'admin.maintenance.*', 'Blokir Perawatan'],
                    ] as [$route, $pattern, $label])
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                @else
                    @foreach ([
                        ['user.dashboard', 'user.dashboard', 'Dashboard'],
                        ['user.courts.index', 'user.courts.*', 'Katalog Lapangan'],
                        ['user.reservations.index', 'user.reservations.*', 'Pesanan Saya'],
                    ] as [$route, $pattern, $label])
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}">{{ $label }}</a></li>
                    @endforeach
                @endif
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-sm btn-outline-light" href="{{ route('profile.edit') }}">{{ auth()->user()->name }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-light">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</nav>
@endauth

<main class="container py-4">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Konfirmasi sebelum menghapus / mengubah status
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
</script>
@stack('scripts')
</body>
</html>
