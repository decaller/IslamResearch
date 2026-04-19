<?php

use App\Livewire\Notebook\NotebookViewer;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/scholar');
});

Route::view('/scholar', 'scholar')->name('scholar.workspace');

Route::get('/notebooks/{notebook}', NotebookViewer::class)->name('notebooks.show');
