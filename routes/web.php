<?php

use App\Http\Controllers\Auth\DvofaktorskaPrijavaController;
use App\Http\Controllers\Auth\PrijavaController;
use App\Http\Controllers\DvofaktorskaController;
use App\Http\Controllers\KorisnikController;
use App\Http\Controllers\ObracunController;
use App\Http\Controllers\PostavkaController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SimulatorController;
use App\Http\Middleware\ZahtijevajKonstante;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('prijava', [PrijavaController::class, 'create'])->name('login');
    Route::post('prijava', [PrijavaController::class, 'store'])->middleware('throttle:5,1');
    Route::get('prijava/kod', [DvofaktorskaPrijavaController::class, 'create'])->name('prijava.kod');
    Route::post('prijava/kod', [DvofaktorskaPrijavaController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('odjava', [PrijavaController::class, 'destroy'])->name('odjava');

    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('profil/dvofaktorska', [DvofaktorskaController::class, 'store'])->name('dvofaktorska.store');
    Route::put('profil/dvofaktorska', [DvofaktorskaController::class, 'update'])->name('dvofaktorska.update');
    Route::post('profil/dvofaktorska/kodovi', [DvofaktorskaController::class, 'kodovi'])->name('dvofaktorska.kodovi');
    Route::delete('profil/dvofaktorska', [DvofaktorskaController::class, 'destroy'])->name('dvofaktorska.destroy');

    Route::resource('postavke', PostavkaController::class)->except('show')->parameters(['postavke' => 'postavka']);

    Route::resource('korisnici', KorisnikController::class)->except('show')
        ->parameters(['korisnici' => 'korisnik'])
        ->middleware('can:admin');

    Route::middleware(ZahtijevajKonstante::class)->group(function () {
        Route::redirect('/', '/obracuni');
        Route::get('obracuni/{obracun}/pdf', [ObracunController::class, 'pdf'])->name('obracuni.pdf');
        Route::resource('obracuni', ObracunController::class)->parameters(['obracuni' => 'obracun']);
        Route::get('simulator', [SimulatorController::class, 'index'])->name('simulator');
    });
});
