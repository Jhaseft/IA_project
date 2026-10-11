<?php

use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\DamasController;
use App\Http\Controllers\MisionerosController;
use App\Http\Controllers\ProfileController;
use App\Services\BuscadorMisioneros;
use App\Services\BuscadorPuzzle;
use App\Services\JuegoDamas;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'misioneros' => BuscadorMisioneros::algoritmosDetallados(),
        'damas' => JuegoDamas::algoritmosDetallados(),
        'puzzle' => BuscadorPuzzle::algoritmosDetallados(),
    ]);
});

// Problema Grupo 1(c): Misioneros y Caníbales — BFS y DFS
Route::get('/misioneros', [MisionerosController::class, 'index'])->name('misioneros');
Route::post('/misioneros/resolver', [MisionerosController::class, 'resolver'])->name('misioneros.resolver');

// Problema Grupo 2(a): Juego de Damas — Minimax y Alfa-Beta
Route::get('/damas', [DamasController::class, 'index'])->name('damas');
Route::post('/damas/movimientos', [DamasController::class, 'movimientos'])->name('damas.movimientos');
Route::post('/damas/aplicar', [DamasController::class, 'aplicar'])->name('damas.aplicar');
Route::post('/damas/analizar', [DamasController::class, 'analizar'])->name('damas.analizar');

// Demo extra (8-puzzle): BFS, DFS y A*
Route::get('/buscador', [BuscadorController::class, 'index'])->name('buscador');
Route::post('/buscador/resolver', [BuscadorController::class, 'resolver'])->name('buscador.resolver');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
