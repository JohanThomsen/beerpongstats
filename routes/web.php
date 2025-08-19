<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\GameUpdateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('user/{user}/match-history', [GameController::class, 'UserMatchHistory'])->name('user.match-history');

// Public live view for a game (no auth required)
Route::get('games/{game}/live', [GameController::class, 'live'])->name('games.live');

// Game routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('games', [GameController::class, 'store'])->name('games.store');
});

// Team routes
Route::resource('teams', TeamController::class);
Route::get('user/{user}/teams', [TeamController::class, 'userTeams'])->name('user.teams');

// Game update routes (API-style JSON endpoints)
// Public: latest state for a game (used on delete fallback)
Route::get('games/{game}/updates/latest', [GameUpdateController::class, 'latestByGame'])->name('games.updates.latest');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('games/{game}/updates', [GameUpdateController::class, 'store'])->name('games.updates.store');
    Route::delete('games/{game}/updates/latest', [GameUpdateController::class, 'deleteLatestByGame'])->name('games.updates.delete-latest');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
