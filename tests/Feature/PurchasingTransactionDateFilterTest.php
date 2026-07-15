<?php

use App\Livewire\Purchasing\Transaction\GoodsReceive as GoodsReceiveComponent;
use App\Livewire\Purchasing\Transaction\PurchaseInvoice as PurchaseInvoiceComponent;
use App\Livewire\Purchasing\Transaction\PurchaseOrder as PurchaseOrderComponent;
use App\Models\GoodsReceive;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->supplier = Supplier::create([
        'code' => 'SUP-DATE-FILTER',
        'name' => 'Supplier Date Filter',
        'address' => 'Test Address',
        'contact' => 'Test Contact',
        'created_by' => (string) $this->user->id,
    ]);

    $this->purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-DATE-IN-RANGE',
        'date' => '2026-07-10',
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'total_price' => 100000,
        'tax' => false,
        'ppn' => 0,
        'gross' => 100000,
        'nett' => 100000,
        'status' => PurchaseOrder::STATUS_APPROVED,
    ]);
});

it('filters purchase orders by an inclusive transaction date range', function () {
    foreach ([
        ['PO-DATE-BEFORE', '2026-07-01'],
        ['PO-DATE-AFTER', '2026-07-20'],
    ] as [$code, $date]) {
        PurchaseOrder::create([
            'code' => $code,
            'date' => $date,
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'total_price' => 100000,
            'tax' => false,
            'ppn' => 0,
            'gross' => 100000,
            'nett' => 100000,
            'status' => PurchaseOrder::STATUS_APPROVED,
        ]);
    }

    Livewire::test(PurchaseOrderComponent::class)
        ->assertDontSee('Data Tabel Pesanan Pembelian')
        ->assertSee('Bersihkan Filter')
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('PO-DATE-IN-RANGE')
        ->assertDontSee('PO-DATE-BEFORE')
        ->assertDontSee('PO-DATE-AFTER')
        ->call('resetFilters')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSee('PO-DATE-BEFORE')
        ->assertSee('PO-DATE-AFTER');
});

it('filters goods receives by an inclusive transaction date range', function () {
    foreach ([
        ['GR-DATE-BEFORE', '2026-07-01'],
        ['GR-DATE-IN-RANGE', '2026-07-10'],
        ['GR-DATE-AFTER', '2026-07-20'],
    ] as [$code, $date]) {
        GoodsReceive::create([
            'code' => $code,
            'date' => $date,
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'status' => GoodsReceive::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);
    }

    Livewire::test(GoodsReceiveComponent::class)
        ->assertDontSee('Data Tabel Penerimaan Barang')
        ->assertSee('Tambah Transaksi')
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('GR-DATE-IN-RANGE')
        ->assertDontSee('GR-DATE-BEFORE')
        ->assertDontSee('GR-DATE-AFTER')
        ->call('resetFilters')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSee('GR-DATE-BEFORE')
        ->assertSee('GR-DATE-AFTER');
});

it('filters purchase invoices by an inclusive transaction date range', function () {
    foreach ([
        ['INV-DATE-BEFORE', '2026-07-01'],
        ['INV-DATE-IN-RANGE', '2026-07-10'],
        ['INV-DATE-AFTER', '2026-07-20'],
    ] as [$code, $date]) {
        PurchaseInvoice::create([
            'code' => $code,
            'date' => $date,
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'grand_total' => 100000,
            'remaining_amount' => 100000,
            'status' => PurchaseInvoice::STATUS_DRAFT,
            'payment_status' => PurchaseInvoice::PAYMENT_UNPAID,
            'created_by' => $this->user->id,
        ]);
    }

    Livewire::test(PurchaseInvoiceComponent::class)
        ->assertDontSee('Data Tabel Faktur Pembelian')
        ->assertSee('Tambah Transaksi')
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('INV-DATE-IN-RANGE')
        ->assertDontSee('INV-DATE-BEFORE')
        ->assertDontSee('INV-DATE-AFTER')
        ->call('resetFilters')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSee('INV-DATE-BEFORE')
        ->assertSee('INV-DATE-AFTER');
});
