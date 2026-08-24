<?php

use App\Livewire\Purchasing\Transaction\GoodsReceive as GoodsReceiveComponent;
use App\Livewire\Purchasing\Transaction\PurchaseInvoice as PurchaseInvoiceComponent;
use App\Livewire\Purchasing\Transaction\PurchaseOrder as PurchaseOrderComponent;
use App\Models\ChartOfAccount;
use App\Models\GoodsReceive;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
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
it('allows a posted purchase invoice to be edited and deleted with its journal', function () {
    $role = Role::create([
        'name' => 'Pengelola Faktur Pembelian',
        'permissions' => ['purchases.transaction.purchase-invoice', 'purchases.transaction.purchase-invoice.delete'],
    ]);
    $this->user->forceFill(['role_id' => $role->id])->save();
    $this->actingAs($this->user);

    $category = ProductCategory::create(['code' => 'PI-EDIT', 'name' => 'Kategori Invoice']);
    $unit = ProductUnit::create(['code' => 'PIPCS', 'name' => 'Pcs']);
    $product = Product::create([
        'name' => 'Produk Invoice Pembelian',
        'sku' => 'PI-EDIT-001',
        'category_id' => $category->id,
        'base_unit_id' => $unit->id,
        'created_by' => (string) $this->user->id,
    ]);
    $orderItem = $this->purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'qty' => 10,
        'conversion' => 1,
        'qty_base' => 10,
        'price' => 10000,
        'total_harga' => 100000,
        'disc' => 0,
    ]);
    $invoice = PurchaseInvoice::create([
        'code' => 'INV-POSTED-EDIT',
        'date' => '2026-07-10',
        'due_date' => '2026-08-10',
        'supplier_id' => $this->supplier->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'sub_total' => 100000,
        'grand_total' => 100000,
        'remaining_amount' => 100000,
        'status' => PurchaseInvoice::STATUS_POSTED,
        'payment_status' => PurchaseInvoice::PAYMENT_UNPAID,
        'created_by' => $this->user->id,
    ]);
    $invoice->items()->create([
        'purchase_order_item_id' => $orderItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'qty' => 10,
        'qty_base' => 10,
        'price' => 10000,
        'discount' => 0,
        'tax_amount' => 0,
        'total' => 100000,
    ]);

    foreach ([['1200', 'Persediaan', 'Asset', 'Debit'], ['1400', 'Pajak Masukan', 'Asset', 'Debit'], ['2100', 'Utang Usaha', 'Liability', 'Credit']] as [$code, $name, $type, $normal]) {
        ChartOfAccount::updateOrCreate(['code' => $code], [
            'name' => $name, 'type' => $type, 'normal_balance' => $normal,
            'is_postable' => true, 'is_active' => true,
        ]);
    }
    $journal = JournalEntry::create([
        'code' => 'JE-PI-EDIT',
        'date' => $invoice->date,
        'source_type' => JournalEntry::SOURCE_PURCHASE_INVOICE,
        'source_id' => $invoice->id,
        'description' => 'Faktur Pembelian '.$invoice->code,
        'status' => JournalEntry::STATUS_POSTED,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openEdit', $invoice->id)
        ->assertSet('invoiceId', $invoice->id)
        ->set('date', '2026-07-20')
        ->set('note', 'Diubah setelah posting')
        ->call('save')
        ->assertHasNoErrors();

    $invoice->refresh();
    $journal->refresh()->load('lines');
    expect($invoice->status)->toBe(PurchaseInvoice::STATUS_POSTED)
        ->and($invoice->date->toDateString())->toBe('2026-07-20')
        ->and($invoice->note)->toBe('Diubah setelah posting')
        ->and($journal->date->toDateString())->toBe('2026-07-20')
        ->and((int) $journal->lines->sum('debit'))->toBe(100000)
        ->and((int) $journal->lines->sum('credit'))->toBe(100000);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('confirmDelete', $invoice->id)
        ->assertSet('showDeleteModal', true)
        ->call('delete');

    expect($invoice->fresh()->trashed())->toBeTrue()
        ->and(JournalEntry::withTrashed()->findOrFail($journal->id)->trashed())->toBeTrue();
});

it('creates one purchase invoice from multiple received goods receives', function () {
    $this->actingAs($this->user);
    $category = ProductCategory::create(['code' => 'PI-MULTI', 'name' => 'Kategori Multi GR']);
    $unit = ProductUnit::create(['code' => 'MGRPCS', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'Gudang Multi GR', 'desc' => '-', 'address' => '-']);
    $product = Product::create([
        'name' => 'Produk Multi GR',
        'sku' => 'SKU-MULTI-GR',
        'category_id' => $category->id,
        'base_unit_id' => $unit->id,
        'created_by' => (string) $this->user->id,
    ]);
    $orderItem = $this->purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'qty' => 10,
        'conversion' => 1,
        'qty_base' => 10,
        'price' => 10000,
        'total_harga' => 100000,
        'disc' => 0,
    ]);

    $receives = collect([
        ['GR-MULTI-001', 4],
        ['GR-MULTI-002', 3],
    ])->map(function (array $source) use ($warehouse, $product, $unit, $orderItem) {
        [$code, $qty] = $source;
        $receive = GoodsReceive::create([
            'code' => $code,
            'date' => '2026-07-20',
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'status' => GoodsReceive::STATUS_RECEIVED,
            'created_by' => $this->user->id,
        ]);
        $receive->items()->create([
            'purchase_order_item_id' => $orderItem->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'unit_id' => $unit->id,
            'conversion' => 1,
            'qty_order' => 10,
            'qty_received' => $qty,
            'qty_outstanding' => 10 - $qty,
            'qty_base' => $qty,
        ]);

        return $receive;
    });

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $this->purchaseOrder->id)
        ->assertSee('GR-MULTI-001')
        ->assertSee('GR-MULTI-002')
        ->set('selectedGoodsReceiveIds', $receives->pluck('id')->all())
        ->assertSet('itemRows.0.qty', 7)
        ->call('save')
        ->assertHasNoErrors();

    $invoice = PurchaseInvoice::with(['goodsReceives', 'items'])->sole();
    expect($invoice->goodsReceives)->toHaveCount(2)
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->qty)->toBe(7)
        ->and($invoice->grand_total)->toBe(70000)
        ->and($invoice->remaining_amount)->toBe(70000);
});
