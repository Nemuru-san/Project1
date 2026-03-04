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

Route::get('purchases/master/supplier', function () {
    return view('pages.purchase.supplier');
})->name('purchases.master.supplier');

Route::get('purchases/master/supplier-add', function () {
    return view('pages.purchase.supplierAdd');
})->name('purchases.master.supplier-add');

Route::get('purchases/transaction/purchase-order', function () {
    return view('pages.purchase.purchaseOrder');
})->name('purchases.transaction.purchase-order');

Route::get('purchases/transaction/purchase-order-add', function () {
    return view('pages.purchase.purchaseOrderAdd');
})->name('purchases.transaction.purchase-order-add');

Route::get('purchases/transaction/good-receive', function () {
    return view('pages.purchase.goodReceive');
})->name('purchases.transaction.good-receive');

Route::get('purchases/transaction/good-receive-add', function () {
    return view('pages.purchase.goodReceiveAdd');
})->name('purchases.transaction.good-receive-add');

Route::get('purchases/transaction/purchase-invoice', function () {
    return view('pages.purchase.purchaseInvoice');
})->name('purchases.transaction.purchase-invoice');

Route::get('purchases/transaction/good-invoice-add', function () {
    return view('pages.purchase.purchaseInvoiceAdd');
})->name('purchases.transaction.purchase-invoice-add');

require __DIR__ . '/settings.php';
