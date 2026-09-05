<?php

use App\Livewire\Inventory\InventoryTransaction\AdjustmentIn;
use App\Livewire\Inventory\InventoryTransaction\AdjustmentOut;
use App\Livewire\Inventory\InventoryTransaction\TransferStock;
use App\Livewire\Purchasing\Transaction\GoodsReceive;
use App\Livewire\Purchasing\Transaction\PurchaseInvoice;
use App\Livewire\Purchasing\Transaction\PurchaseOrder;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

it('blocks purchasing and inventory authorization actions without granular permissions', function () {
    $role = Role::create([
        'name' => 'Transaction Viewer',
        'permissions' => [
            'purchases.transaction.purchase-order',
            'purchases.transaction.good-receive',
            'purchases.transaction.purchase-invoice',
            'inventory.transaction.transfer-stock',
            'inventory.transaction.adjustment-in',
            'inventory.transaction.adjustment-out',
        ],
    ]);

    $this->actingAs(User::factory()->for($role)->create());

    $checks = [
        [PurchaseOrder::class, 'confirmApprove', 'Anda tidak memiliki izin untuk menyetujui Pesanan Pembelian.'],
        [PurchaseOrder::class, 'confirmDelete', 'Anda tidak memiliki izin untuk menghapus Pesanan Pembelian.'],
        [GoodsReceive::class, 'confirmReceive', 'Anda tidak memiliki izin untuk menerima barang.'],
        [GoodsReceive::class, 'confirmDelete', 'Anda tidak memiliki izin untuk menghapus Penerimaan Barang.'],
        [PurchaseInvoice::class, 'confirmPost', 'Anda tidak memiliki izin untuk memposting Faktur Pembelian.'],
        [PurchaseInvoice::class, 'confirmDelete', 'Anda tidak memiliki izin untuk menghapus Faktur Pembelian.'],
        [TransferStock::class, 'confirmApprove', 'Anda tidak memiliki izin untuk menyetujui Transfer Stok.'],
        [TransferStock::class, 'confirmDelete', 'Anda tidak memiliki izin untuk menghapus Transfer Stok.'],
        [AdjustmentIn::class, 'confirmApprove', 'Anda tidak memiliki izin untuk menyetujui Penyesuaian Stok Masuk.'],
        [AdjustmentIn::class, 'confirmDelete', 'Anda tidak memiliki izin untuk menghapus Penyesuaian Stok Masuk.'],
        [AdjustmentOut::class, 'confirmApprove', 'Anda tidak memiliki izin untuk menyetujui Penyesuaian Stok Keluar.'],
        [AdjustmentOut::class, 'confirmDelete', 'Anda tidak memiliki izin untuk menghapus Penyesuaian Stok Keluar.'],
    ];

    foreach ($checks as [$component, $method, $message]) {
        Livewire::test($component)
            ->call($method, 999999)
            ->assertDispatched('toast', message: $message, type: 'error');
    }
});
