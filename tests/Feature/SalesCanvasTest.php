<?php

use App\Livewire\Sales\Transaction\SalesCanvas;
use App\Livewire\Sales\Transaction\SalesOrder;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesCanvas as SalesCanvasModel;
use App\Models\Salesman;
use App\Models\SalesOrder as SalesOrderModel;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function salesCanvasFixture(array $overrides = []): array
{
    $role = Role::firstOrCreate(['name' => 'Salesman'], ['permissions' => ['sales.transaction.salesCanvas']]);
    $user = User::factory()->for($role)->create();
    $customer = Customer::factory()->create();
    $address = CustomerAddress::factory()->for($customer)->create();
    $salesman = Salesman::create([
        'code' => fake()->unique()->bothify('SM-###'),
        'name' => $user->name,
        'user_id' => $user->id,
        'default_customer_id' => $customer->id,
        'default_customer_address_id' => $address->id,
        'is_active' => true,
    ]);
    $category = ProductCategory::create([
        'code' => fake()->unique()->bothify('CAT-###'),
        'name' => 'Kategori Test',
    ]);
    $unit = ProductUnit::create(['code' => 'BOX', 'name' => 'Box']);
    $baseUnit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $warehouse = Warehouse::create([
        'name' => 'Gudang Test',
        'desc' => 'Gudang untuk pengujian',
        'address' => 'Jakarta',
    ]);
    $product = Product::create([
        'name' => 'Produk Test',
        'sku' => fake()->unique()->bothify('SKU-###'),
        'category_id' => $category->id,
        'base_unit_id' => $baseUnit->id,
        'created_by' => 'test',
    ]);
    ProductPrice::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion' => 2,
        'price' => 10000,
    ]);
    ProductPrice::create([
        'product_id' => $product->id,
        'unit_id' => $baseUnit->id,
        'conversion' => 1,
        'price' => 5000,
    ]);
    StockBalance::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 50,
    ]);

    return array_merge(compact('role', 'user', 'salesman', 'customer', 'address', 'category', 'unit', 'warehouse', 'product'), $overrides);
}

it('automatically uses the logged in salesman and their customer defaults', function () {
    $data = salesCanvasFixture();
    $this->actingAs($data['user']);

    $component = Livewire::test(SalesCanvas::class)
        ->call('openCreate')
        ->call('addProduct', $data['product']->id)
        ->call('openProductPicker')
        ->assertSet('showModal', true)
        ->assertSet('salesmanId', $data['salesman']->id)
        ->assertSet('customerId', $data['customer']->id)
        ->assertSet('customerAddressId', $data['address']->id)
        ->assertSeeHtml('h-[80vh]')
        ->assertSeeHtml('max-w-full')
        ->assertSeeHtml('Detail Harga')
        ->assertSeeHtml('Tambah Detail Produk')
        ->assertDontSeeHtml('wire:model.live="salesmanId"')
        ->assertDontSeeHtml('p-2 text-right text-xs')
        ->assertSee('Otomatis mengikuti salesman yang sedang login.');

    expect(substr_count($component->html(), 'inputmode="numeric"'))->toBe(3)
        ->and($component->html())->not->toContain('type="number"');
});

it('refreshes the displayed unit price when the sales unit changes', function () {
    $data = salesCanvasFixture();
    $otherUnit = ProductUnit::create(['code' => 'CTN', 'name' => 'Kotak']);
    ProductPrice::create([
        'product_id' => $data['product']->id,
        'unit_id' => $otherUnit->id,
        'conversion' => 12,
        'price' => 50000,
    ]);
    $this->actingAs($data['user']);

    Livewire::test(SalesCanvas::class)
        ->call('openCreate')
        ->call('addProduct', $data['product']->id)
        ->set('items.0.unit_id', $otherUnit->id)
        ->assertSet('items.0.conversion', 12)
        ->assertSet('items.0.unit_price', 50000)
        ->assertSeeHtml("wire:key=\"canvas-unit-price-{$data['product']->id}-{$otherUnit->id}\"")
        ->assertSeeHtml("x-data=\"{ display: '50.000' }\"");
});
it('creates a draft canvas sale with calculated totals', function () {
    $data = salesCanvasFixture();
    $this->actingAs($data['user']);

    Livewire::test(SalesCanvas::class)
        ->call('openCreate')
        ->set('date', '2026-07-22')
        ->set('tax', true)
        ->set('salesmanId', 999999)
        ->set('items', [[
            'product_id' => $data['product']->id,
            'sku' => $data['product']->sku,
            'name' => $data['product']->name,
            'warehouse_id' => $data['warehouse']->id,
            'unit_id' => $data['unit']->id,
            'conversion' => 2,
            'qty' => 2,
            'unit_price' => 10000,
            'discount_amount' => 1000,
            'stock_available' => 50,
            'unit_options' => [],
        ]])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $canvas = SalesCanvasModel::with('items')->sole();

    expect($canvas->salesman_id)->toBe($data['salesman']->id)
        ->and($canvas->created_by)->toBe($data['user']->id)
        ->and($canvas->status)->toBe('draft')
        ->and($canvas->subtotal)->toBe(20000)
        ->and($canvas->discount_total)->toBe(1000)
        ->and($canvas->tax_amount)->toBe(2090)
        ->and($canvas->grand_total)->toBe(21090)
        ->and($canvas->items)->toHaveCount(1)
        ->and($canvas->items->first()->line_total)->toBe(19000)
        ->and($data['warehouse']->stockBalances()->where('product_id', $data['product']->id)->value('quantity'))->toBe(50);
});

it('shows the remaining product stock from the selected warehouse', function () {
    $data = salesCanvasFixture();
    $otherWarehouse = Warehouse::create([
        'name' => 'Gudang Cabang',
        'desc' => 'Gudang kedua untuk pengujian',
        'address' => 'Bandung',
    ]);

    StockBalance::create([
        'warehouse_id' => $otherWarehouse->id,
        'product_id' => $data['product']->id,
        'quantity' => 17,
    ]);
    $this->actingAs($data['user']);

    Livewire::test(SalesCanvas::class)
        ->call('openCreate')
        ->call('addProduct', $data['product']->id)
        ->set('items.0.warehouse_id', $otherWarehouse->id)
        ->assertSet('items.0.stock_available', 17)
        ->assertSet('items.0.stock_available_display', '8 BOX, 1 PCS')
        ->assertSee('AFS (Stok Tersedia)')
        ->assertSee('8 BOX, 1 PCS');
});

it('requires confirmation before converting a canvas sale into a sales order', function () {
    $data = salesCanvasFixture();
    $canvas = SalesCanvasModel::create([
        'canvas_no' => 'SC-220726-002',
        'date' => '2026-07-22',
        'salesman_id' => $data['salesman']->id,
        'customer_id' => $data['customer']->id,
        'customer_address_id' => $data['address']->id,
        'is_taxed' => true,
        'subtotal' => 20000,
        'discount_total' => 1000,
        'tax_amount' => 2090,
        'grand_total' => 21090,
        'notes' => 'Konversi ke SO',
        'created_by' => $data['user']->id,
    ]);
    $canvas->items()->create([
        'product_id' => $data['product']->id,
        'warehouse_id' => $data['warehouse']->id,
        'unit_id' => $data['unit']->id,
        'qty' => 2,
        'conversion' => 2,
        'unit_price' => 10000,
        'discount_amount' => 1000,
        'line_total' => 19000,
    ]);
    $data['role']->update(['permissions' => [
        'sales.transaction.salesCanvas',
        'sales.transaction.salesCanvas.confirm',
        'sales.transaction.salesCanvas.convert',
    ]]);
    $this->actingAs($data['user']);

    Livewire::test(SalesCanvas::class)
        ->call('confirmConvertToSalesOrder', $canvas->id)
        ->assertSet('showConvertModal', false)
        ->assertDispatched('toast', message: 'Penjualan Kanvas harus dikonfirmasi sebelum dijadikan Sales Order.', type: 'error')
        ->call('openConfirmCanvas', $canvas->id)
        ->assertSet('showConfirmModal', true)
        ->assertSet('confirmTargetId', $canvas->id)
        ->assertSee('Konfirmasi Penjualan Kanvas?')
        ->call('confirmCanvas', $canvas->id)
        ->assertSet('showConfirmModal', false)
        ->assertDispatched('toast', message: 'Penjualan Kanvas berhasil dikonfirmasi.', type: 'success')
        ->assertSee('Jadikan Sales Order')
        ->assertDontSee('Posting')
        ->assertSeeHtml('w-56')
        ->assertSeeHtml('whitespace-nowrap')
        ->call('confirmConvertToSalesOrder', $canvas->id)
        ->assertSet('showConvertModal', true)
        ->call('convertToSalesOrder')
        ->assertDispatched('toast', message: 'Penjualan kanvas berhasil dijadikan Sales Order.', type: 'success');

    $salesOrder = SalesOrderModel::with('items')->sole();

    Livewire::test(SalesOrder::class)
        ->assertSee($salesOrder->order_no)
        ->assertSee($canvas->canvas_no);

    expect($canvas->fresh()->status)->toBe('sales_order')
        ->and(strlen($canvas->fresh()->status))->toBeLessThanOrEqual(20)
        ->and($salesOrder->sales_canvas_id)->toBe($canvas->id)
        ->and($salesOrder->customer_id)->toBe($canvas->customer_id)
        ->and($salesOrder->grand_total)->toBe(21090)
        ->and($salesOrder->status)->toBe('draft')
        ->and($salesOrder->items)->toHaveCount(1)
        ->and($salesOrder->items->first()->line_total)->toBe(19000)
        ->and(StockBalance::where('warehouse_id', $data['warehouse']->id)->where('product_id', $data['product']->id)->value('quantity'))->toBe(50);

    expect(method_exists(SalesCanvas::class, 'post'))->toBeFalse()
        ->and(method_exists(SalesCanvas::class, 'confirmPost'))->toBeFalse();

    Livewire::test(SalesCanvas::class)
        ->call('openDetail', $canvas->id)
        ->assertSet('showDetailModal', true)
        ->assertSee($canvas->canvas_no);
});

it('prevents a salesman from viewing or editing another salesman canvas sale', function () {
    $owner = salesCanvasFixture();
    $other = salesCanvasFixture();
    $canvas = SalesCanvasModel::create([
        'canvas_no' => 'SC-220726-001',
        'date' => '2026-07-22',
        'salesman_id' => $owner['salesman']->id,
        'customer_id' => $owner['customer']->id,
        'created_by' => $owner['user']->id,
    ]);
    $this->actingAs($other['user']);

    Livewire::test(SalesCanvas::class)
        ->assertDontSee($canvas->canvas_no)
        ->call('openEdit', $canvas->id)
        ->assertForbidden();
});

it('does not open the input form for an account without an active salesman profile', function () {
    $role = Role::create(['name' => 'Staff', 'permissions' => ['sales.transaction.salesCanvas']]);
    $user = User::factory()->for($role)->create();
    $this->actingAs($user);

    Livewire::test(SalesCanvas::class)
        ->call('openCreate')
        ->assertSet('showModal', false)
        ->assertDispatched('toast', message: 'Hanya salesman aktif yang dapat membuat penjualan kanvas.', type: 'error');
});

it('allows a super admin without a salesman profile to open the form and choose a salesman', function () {
    $role = Role::create(['name' => 'Super Admin', 'permissions' => []]);
    $user = User::factory()->for($role)->create();
    $this->actingAs($user);

    Livewire::test(SalesCanvas::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->assertSeeHtml('wire:model.live="salesmanId"')
        ->assertSee('Pilih salesman yang menjalankan transaksi ini.');
});

it('filters sales canvases and sales orders by an inclusive transaction date range', function () {
    $data = salesCanvasFixture();
    $this->actingAs($data['user']);

    foreach ([
        ['date' => '2026-07-01', 'suffix' => 'BEFORE', 'canvas_status' => 'draft', 'order_status' => 'draft'],
        ['date' => '2026-07-10', 'suffix' => 'IN-RANGE', 'canvas_status' => 'draft', 'order_status' => 'draft'],
        ['date' => '2026-07-20', 'suffix' => 'AFTER', 'canvas_status' => 'sales_order', 'order_status' => 'confirmed'],
    ] as $record) {
        SalesCanvasModel::create([
            'canvas_no' => 'SC-DATE-'.$record['suffix'],
            'date' => $record['date'],
            'salesman_id' => $data['salesman']->id,
            'customer_id' => $data['customer']->id,
            'status' => $record['canvas_status'],
            'created_by' => $data['user']->id,
        ]);

        SalesOrderModel::create([
            'order_no' => 'SO-DATE-'.$record['suffix'],
            'date' => $record['date'],
            'customer_id' => $data['customer']->id,
            'status' => $record['order_status'],
            'created_by' => $data['user']->id,
        ]);
    }

    Livewire::test(SalesCanvas::class)
        ->assertSee('Rentang tanggal')
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('SC-DATE-IN-RANGE')
        ->assertDontSee('SC-DATE-BEFORE')
        ->assertDontSee('SC-DATE-AFTER')
        ->call('resetFilters')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSee('SC-DATE-BEFORE')
        ->assertSee('SC-DATE-AFTER')
        ->set('statusFilter', 'sales_order')
        ->assertSee('SC-DATE-AFTER')
        ->assertDontSee('SC-DATE-BEFORE')
        ->assertDontSee('SC-DATE-IN-RANGE')
        ->call('resetFilters')
        ->assertSet('statusFilter', '');

    Livewire::test(SalesOrder::class)
        ->assertSee('Rentang tanggal')
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('SO-DATE-IN-RANGE')
        ->assertDontSee('SO-DATE-BEFORE')
        ->assertDontSee('SO-DATE-AFTER')
        ->call('resetFilters')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSee('SO-DATE-BEFORE')
        ->assertSee('SO-DATE-AFTER')
        ->set('statusFilter', 'confirmed')
        ->assertSee('SO-DATE-AFTER')
        ->assertDontSee('SO-DATE-BEFORE')
        ->assertDontSee('SO-DATE-IN-RANGE')
        ->call('resetFilters')
        ->assertSet('statusFilter', '')
        ->assertDontSee('Sales Order hasil konversi dari Penjualan Kanvas.');
});

it('creates a sales order manually without a sales canvas reference', function () {
    $data = salesCanvasFixture();
    $this->actingAs($data['user']);

    Livewire::test(SalesOrder::class)
        ->assertSee('Tambah Sales Order')
        ->assertDontSee('Salesman')
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->call('addProduct', $data['product']->id)
        ->set('date', '2026-07-22')
        ->set('customerId', $data['customer']->id)
        ->set('customerAddressId', $data['address']->id)
        ->set('tax', true)
        ->set('items.0.qty', 2)
        ->set('items.0.discount_amount', 1000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Sales Order berhasil dibuat.', type: 'success');

    $order = SalesOrderModel::with('items')->sole();

    expect($order->sales_canvas_id)->toBeNull()
        ->and($order->pre_order_id)->toBeNull()
        ->and($order->customer_id)->toBe($data['customer']->id)
        ->and($order->status)->toBe('draft')
        ->and($order->items)->toHaveCount(1)
        ->and($order->grand_total)->toBe(21090);

    Livewire::test(SalesOrder::class)
        ->assertSee('Ubah')
        ->assertSee('Hapus')
        ->call('openEdit', $order->id)
        ->assertSet('editingId', $order->id)
        ->set('notes', 'Sales Order diperbarui')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Sales Order berhasil diperbarui.', type: 'success');

    expect($order->fresh()->notes)->toBe('Sales Order diperbarui')
        ->and(SalesOrderModel::count())->toBe(1);
});

it('allows only one confirmed source reference when creating a sales order', function () {
    $data = salesCanvasFixture();
    $this->actingAs($data['user']);

    $confirmedCanvas = SalesCanvasModel::create([
        'canvas_no' => 'SC-REF-CONFIRMED',
        'date' => '2026-07-22',
        'salesman_id' => $data['salesman']->id,
        'customer_id' => $data['customer']->id,
        'customer_address_id' => $data['address']->id,
        'is_taxed' => true,
        'notes' => 'Catatan Sales Kanvas',
        'status' => SalesCanvasModel::STATUS_CONFIRMED,
        'created_by' => $data['user']->id,
    ]);
    $confirmedCanvas->items()->create([
        'product_id' => $data['product']->id,
        'warehouse_id' => $data['warehouse']->id,
        'unit_id' => $data['unit']->id,
        'qty' => 2,
        'conversion' => 2,
        'unit_price' => 10000,
        'discount_amount' => 1000,
        'line_total' => 19000,
    ]);
    SalesCanvasModel::create([
        'canvas_no' => 'SC-REF-DRAFT',
        'date' => '2026-07-22',
        'salesman_id' => $data['salesman']->id,
        'customer_id' => $data['customer']->id,
        'status' => SalesCanvasModel::STATUS_DRAFT,
        'created_by' => $data['user']->id,
    ]);
    $confirmedPreOrder = PreOrder::create([
        'pre_order_no' => 'PO-REF-CONFIRMED',
        'date' => '2026-07-22',
        'customer_id' => $data['customer']->id,
        'customer_address_id' => $data['address']->id,
        'is_taxed' => false,
        'notes' => 'Catatan Pre Order',
        'status' => PreOrder::STATUS_CONFIRMED,
        'created_by' => $data['user']->id,
    ]);
    $confirmedPreOrder->items()->create([
        'product_id' => $data['product']->id,
        'warehouse_id' => $data['warehouse']->id,
        'unit_id' => $data['unit']->id,
        'qty' => 3,
        'conversion' => 2,
        'unit_price' => 10000,
        'discount_amount' => 2000,
        'line_total' => 28000,
    ]);
    PreOrder::create([
        'pre_order_no' => 'PO-REF-DRAFT',
        'date' => '2026-07-22',
        'customer_id' => $data['customer']->id,
        'status' => PreOrder::STATUS_DRAFT,
        'created_by' => $data['user']->id,
    ]);

    Livewire::test(SalesOrder::class)
        ->call('openCreate')
        ->assertSee('Sumber Sales Order')
        ->assertDontSee($confirmedCanvas->canvas_no)
        ->assertDontSee($confirmedPreOrder->pre_order_no)
        ->set('sourceType', 'sales_canvas')
        ->assertSee($confirmedCanvas->canvas_no)
        ->assertDontSee('SC-REF-DRAFT')
        ->assertSeeHtml('wire:model.live="salesCanvasId"')
        ->assertDontSee($confirmedPreOrder->pre_order_no)
        ->set('salesCanvasId', $confirmedCanvas->id)
        ->assertSet('customerId', $data['customer']->id)
        ->assertSet('customerAddressId', $data['address']->id)
        ->assertSet('tax', true)
        ->assertSet('notes', 'Catatan Sales Kanvas')
        ->assertSet('items.0.product_id', $data['product']->id)
        ->assertSet('items.0.qty', 2)
        ->set('sourceType', 'pre_order')
        ->assertSet('salesCanvasId', null)
        ->assertSet('items', [])
        ->assertDontSee($confirmedCanvas->canvas_no)
        ->assertSee($confirmedPreOrder->pre_order_no)
        ->assertDontSee('PO-REF-DRAFT')
        ->set('preOrderId', $confirmedPreOrder->id)
        ->assertSet('items.0.product_id', $data['product']->id)
        ->assertSet('items.0.qty', 3)
        ->assertSet('customerId', $data['customer']->id)
        ->assertSet('customerAddressId', $data['address']->id)
        ->assertSet('tax', false)
        ->assertSet('notes', 'Catatan Pre Order')
        ->assertSet('items.0.discount_amount', 2000)
        ->set('date', '2026-07-22')
        ->assertSeeHtml('wire:model.live="preOrderId"')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Sales Order berhasil dibuat.', type: 'success');

    $order = SalesOrderModel::sole();

    expect($order->sales_canvas_id)->toBeNull()
        ->and($order->pre_order_id)->toBe($confirmedPreOrder->id)
        ->and($confirmedCanvas->fresh()->status)->toBe(SalesCanvasModel::STATUS_CONFIRMED)
        ->and($confirmedPreOrder->fresh()->status)->toBe(PreOrder::STATUS_SALES_ORDER)
        ->and($order->items()->sole()->qty)->toBe(3);
});

it('allows only a super admin to delete and restore a sales order', function () {
    $data = salesCanvasFixture();
    $order = SalesOrderModel::create([
        'order_no' => 'SO-M-220726-001',
        'date' => '2026-07-22',
        'customer_id' => $data['customer']->id,
        'status' => 'draft',
        'created_by' => $data['user']->id,
    ]);

    $this->actingAs($data['user']);
    Livewire::test(SalesOrder::class)
        ->call('confirmDelete', $order->id)
        ->call('delete')
        ->assertDispatched('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');
    expect($order->fresh()->trashed())->toBeFalse();

    $adminRole = Role::create(['name' => 'Super Admin', 'permissions' => []]);
    $admin = User::factory()->for($adminRole)->create();
    $this->actingAs($admin);

    Livewire::test(SalesOrder::class)
        ->call('confirmDelete', $order->id)
        ->call('delete')
        ->assertDispatched('toast', message: 'Sales Order berhasil dihapus.', type: 'success')
        ->set('showTrashed', true)
        ->assertSee('Terhapus')
        ->call('restore', $order->id)
        ->assertDispatched('toast', message: 'Sales Order berhasil dipulihkan.', type: 'success');

    expect($order->fresh()->trashed())->toBeFalse();
});
