<?php

use App\Livewire\Purchasing\Transaction\GoodsReceive as GoodsReceiveComponent;
use App\Livewire\Purchasing\Transaction\PurchaseInvoice as PurchaseInvoiceComponent;
use App\Livewire\Purchasing\Transaction\PurchaseOrder as PurchaseOrderComponent;
use App\Livewire\Sales\Transaction\DeliveryOrder as DeliveryOrderComponent;
use App\Livewire\Sales\Transaction\SalesInvoice as SalesInvoiceComponent;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceive;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

function revisiUser(string $roleName): User
{
    $role = Role::create(['name' => $roleName, 'permissions' => ['*']]);

    return User::factory()->for($role)->create();
}

/**
 * PO 10 pcs dengan Penerimaan Barang sesuai $receivedQtys (masing-masing Received).
 *
 * @return array{0: PurchaseOrder, 1: \Illuminate\Support\Collection<int, GoodsReceive>}
 */
function purchaseOrderWithReceipts(User $user, array $receivedQtys, string $status = PurchaseOrder::STATUS_PARTIALLY_RECEIVED, string $receiptPrefix = 'GR-00'): array
{
    $supplier = Supplier::create(['code' => 'SUP-'.uniqid(), 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    $category = ProductCategory::create(['code' => 'CAT-'.uniqid(), 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS-'.uniqid(), 'name' => 'Pcs']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-'.uniqid(), 'category_id' => $category->id, 'created_by' => (string) $user->id]);
    $price = ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 10000]);
    $warehouse = Warehouse::create(['name' => 'WH-'.uniqid(), 'desc' => 'Gudang', 'address' => 'X']);

    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-'.uniqid(), 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'user_id' => $user->id, 'total_price' => 100000, 'tax' => false, 'ppn' => 0,
        'gross' => 100000, 'nett' => 100000, 'status' => $status,
    ]);
    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => $product->id, 'price_id' => $price->id, 'unit_id' => $unit->id, 'qty' => 10,
        'price' => 10000, 'conversion' => 1, 'qty_base' => 10, 'total_harga' => 100000, 'disc' => 0,
    ]);

    $goodsReceives = collect($receivedQtys)->values()->map(function (int $qty, int $index) use ($receiptPrefix, $purchaseOrder, $purchaseOrderItem, $supplier, $product, $warehouse, $unit, $user) {
        $goodsReceive = GoodsReceive::create([
            'code' => $receiptPrefix.($index + 1), 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder->id, 'status' => GoodsReceive::STATUS_RECEIVED, 'created_by' => $user->id,
        ]);
        $goodsReceive->items()->create([
            'purchase_order_item_id' => $purchaseOrderItem->id, 'product_id' => $product->id,
            'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'conversion' => 1,
            'qty_order' => 10, 'qty_received' => $qty, 'qty_outstanding' => 10 - $qty, 'qty_base' => $qty,
        ]);

        return $goodsReceive;
    });

    return [$purchaseOrder, $goodsReceives];
}

/**
 * SO 10 pcs terkonfirmasi dengan Surat Jalan sesuai $deliveredQtys (masing-masing Dikirim).
 *
 * @return array{0: SalesOrder, 1: \Illuminate\Support\Collection<int, DeliveryOrder>}
 */
function salesOrderWithDeliveries(User $user, array $deliveredQtys, string $deliveryPrefix = 'SJ-00'): array
{
    $suffix = uniqid();
    $customer = Customer::create(['code' => 'CUS-'.$suffix, 'name' => 'Pelanggan', 'is_active' => true, 'created_by' => $user->id]);
    $address = CustomerAddress::create([
        'customer_id' => $customer->id, 'code' => 'ADDR-'.$suffix, 'label' => 'Gudang',
        'address_type' => 'shipping', 'address' => 'Jl. A', 'city' => 'Jakarta', 'is_primary' => true,
    ]);
    $category = ProductCategory::create(['code' => 'CAT-'.$suffix, 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS-'.$suffix, 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'WH-'.$suffix, 'desc' => 'Gudang', 'address' => 'Jl. B']);
    $product = Product::create([
        'name' => 'Produk', 'sku' => 'SKU-'.$suffix, 'category_id' => $category->id,
        'base_unit_id' => $unit->id, 'created_by' => (string) $user->id,
    ]);

    $salesOrder = SalesOrder::create([
        'order_no' => 'SO-'.$suffix, 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'grand_total' => 100000,
        'dp_amount' => 0, 'amount_due' => 100000, 'status' => 'processing', 'created_by' => $user->id,
    ]);
    $salesOrder->forceFill(['verified_at' => now()])->save();
    $salesOrderItem = $salesOrder->items()->create([
        'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id,
        'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'line_total' => 100000,
    ]);

    $deliveryOrders = collect($deliveredQtys)->values()->map(function (int $qty, int $index) use ($deliveryPrefix, $salesOrder, $salesOrderItem, $customer, $address, $product, $warehouse, $unit, $user) {
        $deliveryOrder = DeliveryOrder::create([
            'delivery_no' => $deliveryPrefix.($index + 1), 'delivery_date' => now()->toDateString(),
            'sales_order_id' => $salesOrder->id, 'customer_id' => $customer->id,
            'customer_address_id' => $address->id, 'status' => DeliveryOrder::STATUS_SHIPPED, 'created_by' => $user->id,
        ]);
        $deliveryOrder->items()->create([
            'sales_order_item_id' => $salesOrderItem->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id,
            'unit_id' => $unit->id, 'conversion' => 1, 'qty_order' => 10, 'qty_delivered' => $qty,
            'qty_outstanding' => 10 - $qty, 'qty_base' => $qty,
        ]);

        return $deliveryOrder;
    });

    return [$salesOrder, $deliveryOrders];
}

it('mengubah status Penerimaan Barang menjadi Invoiced saat difakturkan dan kembali Received saat faktur dihapus', function () {
    $user = revisiUser('Purchasing Invoiced');
    $this->actingAs($user);

    [$purchaseOrder, $goodsReceives] = purchaseOrderWithReceipts($user, [4, 6]);

    // Dua GR sekaligus dalam satu faktur.
    $component = Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id);

    expect($component->viewData('goodsReceives')->pluck('code')->all())->toBe(['GR-002', 'GR-001']);

    $component->set('selectedGoodsReceiveIds', $goodsReceives->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('save')
        ->assertHasNoErrors();

    $invoice = PurchaseInvoice::with('items')->firstOrFail();

    expect((int) $invoice->items->sum('qty'))->toBe(10)
        ->and($goodsReceives->map->fresh()->pluck('status')->unique()->all())->toBe([GoodsReceive::STATUS_INVOICED]);

    // GR Invoiced masih dihitung sebagai barang diterima (10 dari 10), jadi PO tidak punya sisa.
    expect($purchaseOrder->fresh()->receivedQty())->toBe(10)
        ->and($purchaseOrder->fresh()->canBeClosed())->toBeFalse();

    // Hapus faktur → GR kembali Received dan bisa difakturkan ulang.
    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('confirmDelete', $invoice->id)
        ->call('delete');

    expect($goodsReceives->map->fresh()->pluck('status')->unique()->all())->toBe([GoodsReceive::STATUS_RECEIVED]);

    $again = Livewire::test(PurchaseInvoiceComponent::class)->call('openCreate')->set('purchase_order_id', $purchaseOrder->id);
    expect($again->viewData('goodsReceives')->pluck('code')->sort()->values()->all())->toBe(['GR-001', 'GR-002']);
});

it('bisa menutup PO yang tidak dikirim penuh, GR yang ada tetap bisa difakturkan', function () {
    $user = revisiUser('Purchasing Close');
    $this->actingAs($user);

    // Pesan 10, hanya diterima 4.
    [$purchaseOrder, $goodsReceives] = purchaseOrderWithReceipts($user, [4]);

    expect($purchaseOrder->canBeClosed())->toBeTrue();

    Livewire::test(PurchaseOrderComponent::class)
        ->call('confirmClose', $purchaseOrder->id)
        ->assertSet('showCloseModal', true)
        ->set('closeNote', 'Pemasok hanya bisa kirim 4 pcs')
        ->call('closeOrder')
        ->assertSet('showCloseModal', false);

    $purchaseOrder->refresh();

    expect($purchaseOrder->isClosed())->toBeTrue()
        ->and($purchaseOrder->closed_by)->toBe($user->id)
        ->and($purchaseOrder->close_note)->toBe('Pemasok hanya bisa kirim 4 pcs')
        ->and($purchaseOrder->canBeClosed())->toBeFalse();

    // Tidak bisa dibuat Penerimaan Barang lagi.
    $receivable = Livewire::test(GoodsReceiveComponent::class)->viewData('purchaseOrders');
    expect($receivable->pluck('id')->all())->not->toContain($purchaseOrder->id);

    // GR yang sudah diterima tetap bisa difakturkan.
    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id)
        ->set('selectedGoodsReceiveIds', [(string) $goodsReceives[0]->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(PurchaseInvoice::count())->toBe(1);
});

it('menolak menutup PO yang sudah diterima penuh atau masih Draf', function () {
    $user = revisiUser('Purchasing Close Reject');
    $this->actingAs($user);

    [$fullyReceived] = purchaseOrderWithReceipts($user, [10], PurchaseOrder::STATUS_RECEIVED);
    [$draft] = purchaseOrderWithReceipts($user, [], PurchaseOrder::STATUS_DRAFT);

    Livewire::test(PurchaseOrderComponent::class)
        ->call('confirmClose', $fullyReceived->id)
        ->assertSet('showCloseModal', false)
        ->call('confirmClose', $draft->id)
        ->assertSet('showCloseModal', false);

    expect($fullyReceived->fresh()->isClosed())->toBeFalse()
        ->and($draft->fresh()->isClosed())->toBeFalse();
});

it('memberi nomor faktur pembelian berurutan per bulan', function () {
    $user = revisiUser('Purchasing Numbering');
    $this->actingAs($user);

    $prefix = 'PIV-'.now()->format('ym').'-';

    // Faktur dari tanggal lain di bulan yang sama tetap satu urutan.
    $supplier = Supplier::create(['code' => 'SUP-NUM', 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    PurchaseInvoice::create([
        'code' => $prefix.'0007', 'date' => now()->startOfMonth()->toDateString(), 'supplier_id' => $supplier->id,
        'sub_total' => 0, 'discount_total' => 0, 'tax' => false, 'tax_amount' => 0, 'grand_total' => 0,
        'paid_amount' => 0, 'remaining_amount' => 0, 'status' => PurchaseInvoice::STATUS_DRAFT,
        'payment_status' => PurchaseInvoice::PAYMENT_UNPAID, 'created_by' => $user->id,
    ]);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->assertSet('code', $prefix.'0008');
});

it('mengubah status Surat Jalan menjadi invoiced saat difakturkan dan kembali shipped saat faktur dihapus', function () {
    $user = revisiUser('Super Admin');
    $this->actingAs($user);

    $customer = Customer::create(['code' => 'CUS-INV', 'name' => 'Pelanggan', 'is_active' => true, 'created_by' => $user->id]);
    $address = CustomerAddress::create([
        'customer_id' => $customer->id, 'code' => 'ADDR-INV', 'label' => 'Gudang',
        'address_type' => 'shipping', 'address' => 'Jl. A', 'city' => 'Jakarta', 'is_primary' => true,
    ]);
    $category = ProductCategory::create(['code' => 'CAT-'.uniqid(), 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS-'.uniqid(), 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'WH', 'desc' => 'Gudang', 'address' => 'Jl. B']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-INV', 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);

    $salesOrder = SalesOrder::create([
        'order_no' => 'SO-INV', 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'grand_total' => 100000,
        'dp_amount' => 0, 'amount_due' => 100000, 'status' => 'processing', 'created_by' => $user->id,
    ]);
    $salesOrder->forceFill(['verified_at' => now()])->save();
    $salesOrderItem = $salesOrder->items()->create([
        'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id,
        'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'line_total' => 100000,
    ]);

    $deliveryOrders = collect([['SJ-001', 4], ['SJ-002', 6]])->map(function (array $row) use ($salesOrder, $salesOrderItem, $customer, $address, $product, $warehouse, $unit, $user) {
        [$deliveryNo, $qty] = $row;
        $deliveryOrder = DeliveryOrder::create([
            'delivery_no' => $deliveryNo, 'delivery_date' => now()->toDateString(), 'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id, 'customer_address_id' => $address->id,
            'status' => DeliveryOrder::STATUS_SHIPPED, 'created_by' => $user->id,
        ]);
        $deliveryOrder->items()->create([
            'sales_order_item_id' => $salesOrderItem->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id,
            'unit_id' => $unit->id, 'conversion' => 1, 'qty_order' => 10, 'qty_delivered' => $qty,
            'qty_outstanding' => 10 - $qty, 'qty_base' => $qty,
        ]);

        return $deliveryOrder;
    });

    // Dua SJ sekaligus dalam satu faktur.
    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $salesOrder->id)
        ->set('selectedDeliveryOrderIds', $deliveryOrders->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('save')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::firstOrFail();

    expect((int) $invoice->grand_total)->toBe(100000)
        ->and($invoice->invoice_no)->toStartWith('FP-'.now()->format('ym').'-')
        ->and($deliveryOrders->map->fresh()->pluck('status')->unique()->all())->toBe([DeliveryOrder::STATUS_INVOICED]);

    // SO sudah tidak muncul lagi di daftar (semua SJ sudah ditagih).
    $next = Livewire::test(SalesInvoiceComponent::class)->call('openCreate');
    expect($next->viewData('salesOrders')->pluck('id')->all())->not->toContain($salesOrder->id);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('confirmDelete', $invoice->id)
        ->call('delete');

    expect($deliveryOrders->map->fresh()->pluck('status')->unique()->all())->toBe([DeliveryOrder::STATUS_SHIPPED]);
});

it('menomori semua dokumen per bulan, bukan per hari', function () {
    $offenders = [];

    foreach (File::allFiles(app_path()) as $file) {
        if (! str_ends_with($file->getFilename(), '.php')) {
            continue;
        }

        // dmy / ymd / my = penomoran harian atau urutan periode yang tidak seragam.
        if (preg_match("/format\('(dmy|ymd|my)'\)/", File::get($file->getPathname()))) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders, 'Masih ada penomoran harian: '.implode(', ', $offenders))->toBeEmpty();
});

it('menunda rincian produk faktur pembelian sampai Penerimaan Barang dipilih', function () {
    $user = revisiUser('Purchasing Deferred Rows');
    $this->actingAs($user);

    [$purchaseOrder, $goodsReceives] = purchaseOrderWithReceipts($user, [4, 6]);

    $component = Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id);

    // Memilih PO hanya mengisi pemasok; rincian produk masih kosong.
    $component->assertSet('itemRows', [])
        ->assertSet('supplier_id', $purchaseOrder->supplier_id);

    $component->set('selectedGoodsReceiveIds', [(string) $goodsReceives->first()->id]);

    expect($component->get('itemRows'))->toHaveCount(1)
        ->and((int) $component->get('itemRows')[0]['qty'])->toBe(4)
        ->and($component->get('itemRows')[0]['gr_codes'])->toBe('GR-001');

    // Batal memilih GR mengosongkan kembali rinciannya.
    $component->set('selectedGoodsReceiveIds', [])->assertSet('itemRows', []);
});

it('tetap menampilkan seluruh PO saat difakturkan tanpa Penerimaan Barang', function () {
    $user = revisiUser('Purchasing Direct Invoice');
    $this->actingAs($user);

    [$purchaseOrder] = purchaseOrderWithReceipts($user, [], PurchaseOrder::STATUS_APPROVED);

    $component = Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id);

    expect($component->get('itemRows'))->toHaveCount(1)
        ->and((int) $component->get('itemRows')[0]['qty'])->toBe(10);
});

it('menyembunyikan PO yang sudah diterima penuh dari pilihan Penerimaan Barang', function () {
    $user = revisiUser('Purchasing Full Receipt');
    $this->actingAs($user);

    [$partialPo] = purchaseOrderWithReceipts($user, [4]);
    // Status Paid meniru kasus nyata: pembayaran menimpa status penerimaan.
    [$fullPo] = purchaseOrderWithReceipts($user, [10], PurchaseOrder::STATUS_PAID, 'GR-FULL-00');

    $codes = Livewire::test(GoodsReceiveComponent::class)
        ->call('openCreate')
        ->viewData('purchaseOrders')
        ->pluck('id')
        ->all();

    expect($codes)->toContain($partialPo->id)
        ->and($codes)->not->toContain($fullPo->id);
});

it('menunda rincian produk faktur penjualan sampai Surat Jalan dipilih', function () {
    $user = revisiUser('Super Admin');
    $this->actingAs($user);

    [$order, $deliveryOrders] = salesOrderWithDeliveries($user, [4, 6]);

    $component = Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $order->id);

    $component->assertSet('items', []);

    $component->set('selectedDeliveryOrderIds', [$deliveryOrders->first()->id]);

    expect($component->get('items'))->toHaveCount(1)
        ->and((int) $component->get('items')[0]['qty'])->toBe(4)
        ->and($component->get('items')[0]['do_codes'])->toBe('SJ-001');
});

it('menyembunyikan SO yang sudah dikirim penuh dari pilihan Surat Jalan', function () {
    $user = revisiUser('Super Admin');
    $this->actingAs($user);

    [$partialOrder] = salesOrderWithDeliveries($user, [4]);
    [$fullOrder] = salesOrderWithDeliveries($user, [10], 'SJ-FULL-00');

    $ids = Livewire::test(DeliveryOrderComponent::class)
        ->call('openCreate')
        ->viewData('salesOrders')
        ->pluck('id')
        ->all();

    expect($ids)->toContain($partialOrder->id)
        ->and($ids)->not->toContain($fullOrder->id);
});
