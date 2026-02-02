<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('purchases/master/supplier-categories', function () {
    return view('pages.purchase.supplierCategory');
})->name('purchases.master.supplier-categories');

Route::get('purchases/master/supplier-categories-add', function () {
    return view('pages.purchase.supplierCategoryAdd');
})->name('purchases.master.supplier-categories-add');

Route::get('supplier', function () {
    return view('pages.purchase.supplier');
})->name('purchases.master.supplier');

require __DIR__ . '/settings.php';
