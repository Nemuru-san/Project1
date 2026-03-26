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

Route::get('purchases/master/supplier-add', function () {
    return view('pages.purchase.supplier.supplierAdd');
})->name('purchases.master.supplier-add');

Route::get('purchases/transaction/purchase-order', function () {
    return view('pages.purchase.purchaseOrder.purchaseOrder');
})->name('purchases.transaction.purchase-order');

Route::get('purchases/transaction/purchase-order-add', function () {
    return view('pages.purchase.purchaseOrder.purchaseOrderAdd');
})->name('purchases.transaction.purchase-order-add');

Route::get('purchases/transaction/good-receive', function () {
    return view('pages.purchase.goodReceive.goodReceive');
})->name('purchases.transaction.good-receive');

Route::get('purchases/transaction/good-receive-add', function () {
    return view('pages.purchase.goodReceive.goodReceiveAdd');
})->name('purchases.transaction.good-receive-add');

Route::get('purchases/transaction/purchase-invoice', function () {
    return view('pages.purchase.purchaseInvoice.purchaseInvoice');
})->name('purchases.transaction.purchase-invoice');

Route::get('purchases/transaction/good-invoice-add', function () {
    return view('pages.purchase.purchaseInvoice.purchaseInvoiceAdd');
})->name('purchases.transaction.purchase-invoice-add');

require __DIR__ . '/settings.php';
