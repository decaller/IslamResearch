<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/scholar');
});

Route::view('/scholar', 'scholar')->name('scholar.workspace');
