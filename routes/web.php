<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UrlController;
use App\Http\Controllers\RedirectController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // URL Management Routes
    Route::get('/urls', [UrlController::class, 'index'])->name('urls.index');
    Route::post('/urls', [UrlController::class, 'store'])->name('urls.store');
    Route::get('/urls/{url}/stats', [UrlController::class, 'stats'])->name('urls.stats');
    Route::delete('/urls/{url}', [UrlController::class, 'destroy'])->name('urls.destroy');
});

require __DIR__.'/auth.php';

// Public URL redirect route (must be last to avoid conflicts)
Route::get('/{code}', [RedirectController::class, 'redirect'])
    ->where('code', '[a-zA-Z0-9]+')
    ->name('url.redirect');
