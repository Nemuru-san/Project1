<?php


use App\Models\GoodsReceive;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')
        ->name('dashboard');

    // Purchasing - Master
    Route::get('purchases/master/supplier', function () {
        return view('pages.purchase.supplier.supplier-manager');
    })->name('purchases.master.supplier');

    // Purchasing - Transaction
    Route::get('purchases/transaction/purchase-order', function () {
        return view('pages.purchase.purchaseOrder.purchaseOrder');
    })->name('purchases.transaction.purchase-order');

    Route::get('purchases/transaction/purchase-order/{id}/print', function ($id) {
        $po = PurchaseOrder::with([
            'supplier',
            'user',
            'items.product',
            'items.unit',
        ])->findOrFail($id);

        return view('prints.purchase-order', compact('po'));
    })->name('purchases.transaction.purchase-order.print');

    Route::get('purchases/transaction/good-receive', function () {
        return view('pages.purchase.goodsReceive.goodsReceive');
    })->name('purchases.transaction.good-receive');

    Route::get('purchases/transaction/good-receive/{id}/print', function ($id) {
        $gr = GoodsReceive::with([
            'supplier',
            'purchaseOrder',
            'items.product',
            'items.unit',
        ])->findOrFail($id);

        return view('prints.goods-receive', compact('gr'));
    })->name('purchases.transaction.good-receive.print');

    Route::get('purchases/transaction/purchase-invoice', function () {
        return view('pages.purchase.purchaseInvoice.purchaseInvoice');
    })->name('purchases.transaction.purchase-invoice');

    Route::get('purchases/transaction/purchase-invoice/{id}/print', function ($id) {
        $invoice = PurchaseInvoice::with([
            'supplier',
            'purchaseOrder',
            'items.product',
            'items.unit',
        ])->findOrFail($id);

        return view('prints.purchase-invoice', compact('invoice'));
    })->name('purchases.transaction.purchase-invoice.print');

    // Inventory - Master
    Route::get('inventory/master/product-master', function () {
        return view('pages.inventory.productMaster.productMaster');
    })->name('inventory.product.productMaster');

    Route::get('inventory/master/product-category', function () {
        return view('pages.inventory.productMaster.productCategory');
    })->name('inventory.product.productCategory');

    Route::get('inventory/master/uom', function () {
        return view('pages.inventory.productMaster.uom');
    })->name('inventory.product.uom');

    Route::get('inventory/master/warehouse', function () {
        return view('pages.inventory.productMaster.warehouse');
    })->name('inventory.product.warehouse');

    Route::get('inventory/report/stock-balance', function () {
        return view('pages.inventory.report.stockBalance');
    })->name('inventory.report.stock-balance');

    // Inventory - Transaction
    Route::get('inventory/transaction/transfer-stock', function () {
        return view('pages.inventory.inventoryTransaction.transferStock');
    })->name('inventory.transaction.transfer-stock');

    Route::get('inventory/transaction/transfer-stock/{id}/print', function ($id) {
        $transfer = StockTransfer::with([
            'warehouseFrom',
            'warehouseTo',
            'items.product',
            'items.unit',
        ])->findOrFail($id);

        return view('prints.transfer-stock', compact('transfer'));
    })->name('inventory.transaction.transfer-stock.print');

    Route::get('inventory/transaction/adjustment-in', function () {
        return view('pages.inventory.inventoryTransaction.adjustmentIn');
    })->name('inventory.transaction.adjustment-in');

    Route::get('inventory/transaction/adjustment-out', function () {
        return view('pages.inventory.inventoryTransaction.adjustmentOut');
    })->name('inventory.transaction.adjustment-out');

    // Finance
    Route::get('finance/master/accounts', function () {
        return view('pages.finance.master.chartOfAccount');
    })->name('finance.master.chart-of-accounts');

    Route::get('finance/master/bank-accounts', function () {
        return view('pages.finance.master.bankAccount');
    })->name('finance.master.bank-accounts');

    Route::get('finance/transaction/ap-payment', function () {
        return view('pages.finance.transaction.apPayment');
    })->name('finance.transaction.ap-payment');

    Route::get('finance/report/journal-entry', function () {
        return view('pages.finance.report.journalEntry');
    })->name('finance.report.journal-entry');

    // User
    Route::get('user/action/user', function () {
        return view('pages.users.user');
    })->name('user.action.user');

    Route::get('user/action/role-user', function () {
        return view('pages.users.role-user');
    })->name('user.action.role');
});

require __DIR__ . '/settings.php';
