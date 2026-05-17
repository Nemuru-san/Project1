<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('purchases/master/supplier', function () {
    return view('pages.purchase.supplier.supplier');
})->name('purchases.master.supplier');

Route::get('purchases/transaction/purchase-order', function () {
    return view('pages.purchase.purchaseOrder.purchaseOrder');
})->name('purchases.transaction.purchase-order');

Route::get('purchases/transaction/good-receive', function () {
    return view('pages.purchase.goodReceive.goodReceive');
})->name('purchases.transaction.good-receive');

Route::get('purchases/transaction/purchase-invoice', function () {
    return view('pages.purchase.purchaseInvoice.purchaseInvoice');
})->name('purchases.transaction.purchase-invoice');

Route::get('inventory/master/product-master', function () {
    return view('pages.inventory.productMaster.productMaster');
})->name('inventory.master.productMaster');

Route::get('inventory/master/product-category', function () {
    return view('pages.inventory.productMaster.productCategory');
})->name('inventory.master.productCategory');

require __DIR__ . '/settings.php';
