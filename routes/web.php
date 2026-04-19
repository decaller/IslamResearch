<?php

use App\Http\Controllers\QuranAuthController;
use App\Livewire\Notebook\NotebookViewer;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::view('/scholar', 'scholar')->name('scholar.workspace');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/terms', 'terms')->name('terms');

Route::get('/notebooks/{notebook}', NotebookViewer::class)->name('notebooks.show');

Route::get('/auth/quran/redirect', [QuranAuthController::class, 'redirect'])->name('auth.quran.redirect');
Route::get('/auth/quran/callback', [QuranAuthController::class, 'callback'])->name('auth.quran.callback');
