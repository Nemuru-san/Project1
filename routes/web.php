<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('supplier-categories', function () {
    return view('pages.purchase.supplierCategory');
})->name('supplier=categories');

require __DIR__ . '/settings.php';
