<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Arahkan ke dashboard sesuai role
    Route::get('/dashboard', fn () => redirect()->route(
        auth()->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard'
    ))->name('dashboard');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');
        Route::resource('categories', Admin\SportCategoryController::class)->except('show');
        Route::resource('courts', Admin\CourtController::class)->except('show');
        Route::resource('reservations', Admin\ReservationController::class)->only(['index', 'show', 'destroy']);
        Route::patch('reservations/{reservation}/approve', [Admin\ReservationController::class, 'approve'])->name('reservations.approve');
        Route::patch('reservations/{reservation}/reject', [Admin\ReservationController::class, 'reject'])->name('reservations.reject');
        Route::patch('reservations/{reservation}/complete', [Admin\ReservationController::class, 'complete'])->name('reservations.complete');
        Route::get('maintenance/create', [Admin\MaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('maintenance', [Admin\MaintenanceController::class, 'store'])->name('maintenance.store');
    });

    Route::middleware('role:user')->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', User\DashboardController::class)->name('dashboard');
        Route::get('courts', [User\CourtController::class, 'index'])->name('courts.index');
        Route::get('courts/{court}', [User\CourtController::class, 'show'])->name('courts.show');
        Route::resource('reservations', User\ReservationController::class)->except('destroy');
        Route::patch('reservations/{reservation}/cancel', [User\ReservationController::class, 'cancel'])->name('reservations.cancel');
    });
});
