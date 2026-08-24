<?php

use App\Models\DeliveryOrder;
use App\Models\GoodsReceive;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnInvoice;
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
            'goodsReceives',
            'items.product',
            'items.unit',
        ])->findOrFail($id);

        return view('prints.purchase-invoice', compact('invoice'));
    })->name('purchases.transaction.purchase-invoice.print');

    Route::view('purchases/return/purchase-return', 'pages.purchase.return.purchaseReturn')->name('purchases.return.purchase-return');
    Route::get('purchases/return/purchase-return/{id}/print', function ($id) {
        $return = PurchaseReturn::with(['supplier', 'goodsReceive', 'purchaseOrder', 'items.product', 'items.warehouse', 'items.unit'])->findOrFail($id);

        return view('prints.purchase-return', compact('return'));
    })->name('purchases.return.purchase-return.print');

    Route::view('purchases/return/purchase-return-invoice', 'pages.purchase.return.purchaseReturnInvoice')->name('purchases.return.purchase-return-invoice');
    Route::get('purchases/return/purchase-return-invoice/{id}/print', function ($id) {
        $invoice = PurchaseReturnInvoice::with(['supplier', 'purchaseInvoice', 'purchaseReturn.items.product', 'purchaseReturn.items.unit'])->findOrFail($id);

        return view('prints.purchase-return-invoice', compact('invoice'));
    })->name('purchases.return.purchase-return-invoice.print');

    Route::view('purchases/report/unfinished-purchase-order', 'pages.purchase.report.unfinishedPurchaseOrder')
        ->name('purchases.report.unfinished-purchase-order');

    Route::view('purchases/report/unfinished-purchase-invoice', 'pages.purchase.report.unfinishedPurchaseInvoice')
        ->name('purchases.report.unfinished-purchase-invoice');

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

    Route::view('inventory/report/stock-card', 'pages.inventory.report.stockCard')
        ->name('inventory.report.stock-card');

    Route::view('inventory/report/stock-movement', 'pages.inventory.report.stockMovement')
        ->name('inventory.report.stock-movement');

    // Inventory - Transaction
    Route::get('inventory/transaction/transfer-stock', function () {
        return view('pages.inventory.inventoryTransaction.transferStock');
    })->name('inventory.transaction.transfer-stock');

    Route::get('inventory/transaction/transfer-stock/{id}/nota', function ($id) {
        return view('prints.transfer-stock', [
            'transfer' => StockTransfer::forPrint($id),
            'autoPrint' => false,
        ]);
    })->name('inventory.transaction.transfer-stock.view');

    Route::get('inventory/transaction/transfer-stock/{id}/print', function ($id) {
        return view('prints.transfer-stock', [
            'transfer' => StockTransfer::forPrint($id),
            'autoPrint' => true,
        ]);
    })->name('inventory.transaction.transfer-stock.print');

    Route::get('inventory/transaction/adjustment-in', function () {
        return view('pages.inventory.inventoryTransaction.adjustmentIn');
    })->name('inventory.transaction.adjustment-in');

    Route::get('inventory/transaction/adjustment-out', function () {
        return view('pages.inventory.inventoryTransaction.adjustmentOut');
    })->name('inventory.transaction.adjustment-out');

    // Sales
    Route::get('sales/master/customer', function () {
        return view('pages.sales.salesMaster.customer');
    })->name('sales.master.customer');

    Route::get('sales/master/salesman', function () {
        return view('pages.sales.salesMaster.salesMan');
    })->name('sales.master.salesman');

    Route::get('sales/transaction/salesCanvas', function () {
        return view('pages.sales.salesTransaction.salesCanvas');
    })->name('sales.transaction.salesCanvas');

    Route::get('sales/transaction/salesPreOrder', function () {
        return view('pages.sales.salesTransaction.salesPreOrder');
    })->name('sales.transaction.salesPreOrder');

    Route::get('sales/transaction/salesOrder', function () {
        return view('pages.sales.salesTransaction.salesOrder');
    })->name('sales.transaction.salesOrder');

    Route::get('sales/transaction/deliveryOrder', function () {
        return view('pages.sales.salesTransaction.deliveryOrder');
    })->name('sales.transaction.deliveryOrder');

    Route::get('sales/transaction/deliveryOrder/{id}/surat-jalan', function ($id) {
        $deliveryOrder = DeliveryOrder::forPrint($id);
        abort_unless($deliveryOrder->isAccessibleBy(auth()->user()), 403);

        return view('prints.delivery-order', compact('deliveryOrder') + ['autoPrint' => false]);
    })->name('sales.transaction.deliveryOrder.view');

    Route::get('sales/transaction/deliveryOrder/{id}/print', function ($id) {
        $deliveryOrder = DeliveryOrder::forPrint($id);
        abort_unless($deliveryOrder->isAccessibleBy(auth()->user()), 403);

        return view('prints.delivery-order', compact('deliveryOrder') + ['autoPrint' => true]);
    })->name('sales.transaction.deliveryOrder.print');

    Route::get('sales/transaction/salesInvoice', function () {
        return view('pages.sales.salesTransaction.salesInvoice');
    })->name('sales.transaction.salesInvoice');
    Route::get('sales/transaction/salesInvoice/{id}/invoice', function ($id) {
        return view('prints.sales-invoice', [
            'invoice' => SalesInvoice::forPrint($id),
            'autoPrint' => false,
        ]);
    })->name('sales.transaction.salesInvoice.view');

    Route::get('sales/transaction/salesInvoice/{id}/print', function ($id) {
        return view('prints.sales-invoice', [
            'invoice' => SalesInvoice::forPrint($id),
            'autoPrint' => true,
        ]);
    })->name('sales.transaction.salesInvoice.print');

    Route::view('sales/report/unfinished-sales-order', 'pages.sales.report.unfinishedSalesOrder')
        ->name('sales.report.po-outstanding');

    Route::view('sales/report/unpaid-sales-invoice', 'pages.sales.report.unpaidSalesInvoice')
        ->name('sales.report.invoice-outstanding');
    // Finance
    Route::get('finance/master/accounts', function () {
        return view('pages.finance.master.chartOfAccount');
    })->name('finance.master.chart-of-accounts');

    Route::get('finance/master/bank-accounts', function () {
        return view('pages.finance.master.bankAccount');
    })->name('finance.master.bank-accounts');

    Route::view('sales/return/sales-return', 'pages.sales.return.salesReturn')->name('sales.return.sales-return');
    Route::get('sales/return/sales-return/{id}/print', function ($id) {
        $return = SalesReturn::with(['customer', 'deliveryOrder', 'salesOrder', 'items.product', 'items.warehouse', 'items.unit'])->findOrFail($id);

        return view('prints.sales-return', compact('return'));
    })->name('sales.return.sales-return.print');
    Route::view('sales/return/sales-return-invoice', 'pages.sales.return.salesReturnInvoice')->name('sales.return.sales-return-invoice');
    Route::get('sales/return/sales-return-invoice/{id}/print', function ($id) {
        $invoice = SalesReturnInvoice::with(['customer', 'salesInvoice', 'salesReturn.items.product', 'salesReturn.items.unit'])->findOrFail($id);

        return view('prints.sales-return-invoice', compact('invoice'));
    })->name('sales.return.sales-return-invoice.print');

    Route::get('finance/transaction/ap-payment', function () {
        return view('pages.finance.transaction.apPayment');
    })->name('finance.transaction.ap-payment');

    Route::get('finance/transaction/ar-dp-payment', function () {
        return view('pages.finance.transaction.arDpPayment');
    })->name('finance.transaction.ar-dp-payment');

    Route::view('finance/transaction/ar-payment', 'pages.finance.transaction.arPayment')
        ->name('finance.transaction.ar-payment');

    Route::get('finance/transaction/expense', function () {
        return view('pages.finance.transaction.expense');
    })->name('finance.transaction.expense');

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

require __DIR__.'/settings.php';
