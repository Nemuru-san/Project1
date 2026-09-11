<?php

use App\Livewire\Purchasing\Transaction\PurchaseInvoice as PurchaseInvoiceComponent;
use App\Livewire\Sales\Transaction\SalesInvoice as SalesInvoiceComponent;
use App\Models\APPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
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
use Livewire\Livewire;

function partialInvoicingUser(string $roleName): User
{
    $role = Role::create(['name' => $roleName, 'permissions' => ['*']]);

    return User::factory()->for($role)->create();
}

it('bisa membuat faktur pembelian lanjutan untuk Penerimaan Barang kedua', function () {
    $user = partialInvoicingUser('Purchasing Partial');
    $this->actingAs($user);

    $supplier = Supplier::create(['code' => 'SUP-001', 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    $category = ProductCategory::create(['code' => 'CAT', 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-001', 'category_id' => $category->id, 'created_by' => (string) $user->id]);
    $price = ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 10000]);
    $warehouse = Warehouse::create(['name' => 'WH-001', 'desc' => 'Gudang', 'address' => 'X']);

    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-001', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'user_id' => $user->id, 'total_price' => 100000, 'tax' => false, 'ppn' => 0,
        'gross' => 100000, 'nett' => 100000, 'status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
    ]);
    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => $product->id, 'price_id' => $price->id, 'unit_id' => $unit->id, 'qty' => 10,
        'price' => 10000, 'conversion' => 1, 'qty_base' => 10, 'total_harga' => 100000, 'disc' => 0,
    ]);

    $goodsReceives = collect([['GR-001', 4], ['GR-002', 6]])->map(function (array $row) use ($purchaseOrder, $purchaseOrderItem, $supplier, $product, $warehouse, $unit, $user) {
        [$code, $qty] = $row;

        $goodsReceive = GoodsReceive::create([
            'code' => $code, 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder->id, 'status' => GoodsReceive::STATUS_RECEIVED, 'created_by' => $user->id,
        ]);
        $goodsReceive->items()->create([
            'purchase_order_item_id' => $purchaseOrderItem->id, 'product_id' => $product->id,
            'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'conversion' => 1,
            'qty_order' => 10, 'qty_received' => $qty, 'qty_outstanding' => 10 - $qty, 'qty_base' => $qty,
        ]);

        return $goodsReceive;
    });

    // Faktur pertama hanya menagih GR-001.
    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id)
        ->set('selectedGoodsReceiveIds', [(string) $goodsReceives[0]->id])
        ->call('save')
        ->assertHasNoErrors();

    // PO tetap tersedia karena GR-002 belum ditagih.
    $second = Livewire::test(PurchaseInvoiceComponent::class)->call('openCreate');
    expect($second->viewData('purchaseOrders')->pluck('code')->all())->toContain('PO-001');

    $second->set('purchase_order_id', $purchaseOrder->id);
    expect($second->viewData('goodsReceives')->pluck('code')->all())->toBe(['GR-002']);

    $second->set('selectedGoodsReceiveIds', [(string) $goodsReceives[1]->id])
        ->call('save')
        ->assertHasNoErrors();

    $invoices = PurchaseInvoice::with('goodsReceives', 'items')->orderBy('id')->get();

    expect($invoices)->toHaveCount(2)
        ->and($invoices[0]->goodsReceives->pluck('code')->all())->toBe(['GR-001'])
        ->and((int) $invoices[0]->items->sum('qty'))->toBe(4)
        ->and($invoices[1]->goodsReceives->pluck('code')->all())->toBe(['GR-002'])
        ->and((int) $invoices[1]->items->sum('qty'))->toBe(6);
});

it('tetap bisa memfakturkan Penerimaan Barang sisa setelah faktur pertama dibayar', function () {
    $user = partialInvoicingUser('Purchasing Paid');
    $this->actingAs($user);

    $supplier = Supplier::create(['code' => 'SUP-003', 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    $category = ProductCategory::create(['code' => 'CAT', 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-003', 'category_id' => $category->id, 'created_by' => (string) $user->id]);
    $price = ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 10000]);
    $warehouse = Warehouse::create(['name' => 'WH-003', 'desc' => 'Gudang', 'address' => 'X']);

    // Pembayaran faktur pertama sudah mengubah status PO menjadi Paid.
    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-003', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'user_id' => $user->id, 'total_price' => 100000, 'tax' => false, 'ppn' => 0,
        'gross' => 100000, 'nett' => 100000, 'status' => PurchaseOrder::STATUS_PAID,
    ]);
    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => $product->id, 'price_id' => $price->id, 'unit_id' => $unit->id, 'qty' => 10,
        'price' => 10000, 'conversion' => 1, 'qty_base' => 10, 'total_harga' => 100000, 'disc' => 0,
    ]);

    $unbilled = null;

    foreach ([['GR-003', 4, true], ['GR-004', 6, false]] as [$code, $qty, $billed]) {
        $goodsReceive = GoodsReceive::create([
            'code' => $code, 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
            'purchase_order_id' => $purchaseOrder->id, 'status' => GoodsReceive::STATUS_RECEIVED, 'created_by' => $user->id,
        ]);
        $goodsReceive->items()->create([
            'purchase_order_item_id' => $purchaseOrderItem->id, 'product_id' => $product->id,
            'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'conversion' => 1,
            'qty_order' => 10, 'qty_received' => $qty, 'qty_outstanding' => 10 - $qty, 'qty_base' => $qty,
        ]);

        if ($billed) {
            $invoice = PurchaseInvoice::create([
                'code' => 'PIV-003', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
                'purchase_order_id' => $purchaseOrder->id, 'sub_total' => 40000, 'grand_total' => 40000,
                'paid_amount' => 40000, 'remaining_amount' => 0, 'status' => PurchaseInvoice::STATUS_POSTED,
                'payment_status' => PurchaseInvoice::PAYMENT_PAID, 'created_by' => $user->id,
            ]);
            $invoice->goodsReceives()->sync([$goodsReceive->id]);

            continue;
        }

        $unbilled = $goodsReceive;
    }

    $component = Livewire::test(PurchaseInvoiceComponent::class)->call('openCreate');
    expect($component->viewData('purchaseOrders')->pluck('code')->all())->toContain('PO-003');

    $component->set('purchase_order_id', $purchaseOrder->id);
    expect($component->viewData('goodsReceives')->pluck('code')->all())->toBe(['GR-004']);

    $component->set('selectedGoodsReceiveIds', [(string) $unbilled->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(PurchaseInvoice::count())->toBe(2);
});

it('menandai PO Partial Paid ketika pembayaran belum menutup nilai PO', function () {
    $user = partialInvoicingUser('Finance AP');
    $this->actingAs($user);

    $supplier = Supplier::create(['code' => 'SUP-004', 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);

    $accountsPayable = ChartOfAccount::create([
        'code' => '2100', 'name' => 'Utang Usaha', 'type' => ChartOfAccount::TYPE_LIABILITY,
        'normal_balance' => 'Credit', 'is_postable' => true, 'is_active' => true,
    ]);
    $cashAccount = ChartOfAccount::create([
        'code' => '1100', 'name' => 'Kas', 'type' => ChartOfAccount::TYPE_ASSET,
        'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true,
    ]);
    $bankAccount = BankAccount::create([
        'name' => 'Kas Besar', 'account_type' => 'cash', 'bank_name' => 'Kas', 'account_number' => '-',
        'account_holder' => 'PT A', 'chart_of_account_id' => $cashAccount->id, 'is_active' => true,
    ]);

    // PO senilai 2.000.000, baru satu faktur 400.000 yang dibayar lunas.
    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-004', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'user_id' => $user->id, 'total_price' => 2000000, 'tax' => false, 'ppn' => 0,
        'gross' => 2000000, 'nett' => 2000000, 'status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
    ]);
    $invoice = PurchaseInvoice::create([
        'code' => 'PIV-004', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'purchase_order_id' => $purchaseOrder->id, 'sub_total' => 400000, 'grand_total' => 400000,
        'paid_amount' => 0, 'remaining_amount' => 400000, 'status' => PurchaseInvoice::STATUS_POSTED,
        'payment_status' => PurchaseInvoice::PAYMENT_UNPAID, 'created_by' => $user->id,
    ]);

    $payment = APPayment::create([
        'code' => 'APP-001', 'payment_date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'bank_account_id' => $bankAccount->id, 'total_amount' => 400000, 'payment_method' => 'transfer',
        'status' => APPayment::STATUS_DRAFT, 'created_by' => $user->id,
    ]);
    $payment->details()->create(['purchase_invoice_id' => $invoice->id, 'amount' => 400000]);

    Livewire::test(App\Livewire\Finance\Transaction\APPayment::class)
        ->call('confirmPost', $payment->id)
        ->call('postPayment');

    expect($invoice->fresh()->payment_status)->toBe(PurchaseInvoice::PAYMENT_PAID)
        ->and($purchaseOrder->fresh()->status)->toBe(PurchaseOrder::STATUS_PARTIAL_PAID);

    // Faktur kedua melunasi sisa PO -> baru PO berstatus Paid.
    $second = PurchaseInvoice::create([
        'code' => 'PIV-005', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'purchase_order_id' => $purchaseOrder->id, 'sub_total' => 1600000, 'grand_total' => 1600000,
        'paid_amount' => 1600000, 'remaining_amount' => 0, 'status' => PurchaseInvoice::STATUS_POSTED,
        'payment_status' => PurchaseInvoice::PAYMENT_PAID, 'created_by' => $user->id,
    ]);

    $purchaseOrder->fresh()->refreshPaymentStatus();

    expect($purchaseOrder->fresh()->status)->toBe(PurchaseOrder::STATUS_PAID)
        ->and($second->fresh()->payment_status)->toBe(PurchaseInvoice::PAYMENT_PAID);
});

it('menolak faktur pembelian kedua tanpa Penerimaan Barang', function () {
    $user = partialInvoicingUser('Purchasing Duplicate');
    $this->actingAs($user);

    $supplier = Supplier::create(['code' => 'SUP-002', 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    $category = ProductCategory::create(['code' => 'CAT', 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-002', 'category_id' => $category->id, 'created_by' => (string) $user->id]);
    $price = ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 10000]);

    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-002', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'user_id' => $user->id, 'total_price' => 100000, 'tax' => false, 'ppn' => 0,
        'gross' => 100000, 'nett' => 100000, 'status' => PurchaseOrder::STATUS_APPROVED,
    ]);
    $purchaseOrder->items()->create([
        'product_id' => $product->id, 'price_id' => $price->id, 'unit_id' => $unit->id, 'qty' => 10,
        'price' => 10000, 'conversion' => 1, 'qty_base' => 10, 'total_harga' => 100000, 'disc' => 0,
    ]);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchase_order_id', $purchaseOrder->id)
        ->call('save')
        ->assertHasErrors('purchase_order_id');

    expect(PurchaseInvoice::count())->toBe(1);
});

it('bisa membuat faktur penjualan lanjutan untuk Surat Jalan kedua', function () {
    $user = partialInvoicingUser('Sales Partial');
    $this->actingAs($user);

    $customer = Customer::create(['code' => 'CUS-001', 'name' => 'Pelanggan', 'is_active' => true, 'created_by' => $user->id]);
    $address = CustomerAddress::create([
        'customer_id' => $customer->id, 'code' => 'ADDR-001', 'label' => 'Gudang',
        'address_type' => 'shipping', 'address' => 'Jl. A', 'city' => 'Jakarta', 'is_primary' => true,
    ]);
    $category = ProductCategory::create(['code' => 'CAT', 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'WH', 'desc' => 'Gudang', 'address' => 'Jl. B']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-001', 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);

    $salesOrder = SalesOrder::create([
        'order_no' => 'SO-001', 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'grand_total' => 100000,
        'dp_amount' => 10000, 'amount_due' => 90000, 'status' => 'processing', 'created_by' => $user->id,
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

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $salesOrder->id)
        ->set('selectedDeliveryOrderIds', [(string) $deliveryOrders[0]->id])
        ->call('save')
        ->assertHasNoErrors();

    $second = Livewire::test(SalesInvoiceComponent::class)->call('openCreate');
    expect($second->viewData('salesOrders')->pluck('order_no')->all())->toContain('SO-001');

    $second->set('salesOrderId', $salesOrder->id)->assertHasNoErrors();
    expect($second->viewData('deliveryOrders')->pluck('delivery_no')->all())->toBe(['SJ-002']);

    $second->set('selectedDeliveryOrderIds', [(string) $deliveryOrders[1]->id])
        ->call('save')
        ->assertHasNoErrors();

    $invoices = SalesInvoice::with('deliveryOrders', 'items')->orderBy('id')->get();

    expect($invoices)->toHaveCount(2)
        ->and($invoices[0]->deliveryOrders->pluck('delivery_no')->all())->toBe(['SJ-001'])
        ->and((int) $invoices[0]->grand_total)->toBe(40000)
        ->and($invoices[1]->deliveryOrders->pluck('delivery_no')->all())->toBe(['SJ-002'])
        ->and((int) $invoices[1]->grand_total)->toBe(60000);

    // Uang muka 10.000 hanya boleh terpakai sekali, bukan di tiap faktur.
    expect((int) $invoices[0]->dp_amount)->toBe(10000)
        ->and((int) $invoices[0]->amount_due)->toBe(30000)
        ->and((int) $invoices[1]->dp_amount)->toBe(0)
        ->and((int) $invoices[1]->amount_due)->toBe(60000)
        ->and((int) $invoices->sum('amount_due'))->toBe((int) $salesOrder->grand_total - (int) $salesOrder->dp_amount);
});

it('mengembalikan Surat Jalan ke daftar tersedia saat fakturnya dihapus', function () {
    $user = partialInvoicingUser('Super Admin');
    $this->actingAs($user);

    $customer = Customer::create(['code' => 'CUS-003', 'name' => 'Pelanggan', 'is_active' => true, 'created_by' => $user->id]);
    $address = CustomerAddress::create([
        'customer_id' => $customer->id, 'code' => 'ADDR-003', 'label' => 'Gudang',
        'address_type' => 'shipping', 'address' => 'Jl. A', 'city' => 'Jakarta', 'is_primary' => true,
    ]);
    $category = ProductCategory::create(['code' => 'CAT', 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'WH', 'desc' => 'Gudang', 'address' => 'Jl. B']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-003', 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);

    $salesOrder = SalesOrder::create([
        'order_no' => 'SO-003', 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'grand_total' => 100000,
        'amount_due' => 100000, 'status' => 'processing', 'created_by' => $user->id,
    ]);
    $salesOrder->forceFill(['verified_at' => now()])->save();
    $salesOrderItem = $salesOrder->items()->create([
        'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id,
        'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'line_total' => 100000,
    ]);

    $deliveryOrder = DeliveryOrder::create([
        'delivery_no' => 'SJ-003', 'delivery_date' => now()->toDateString(), 'sales_order_id' => $salesOrder->id,
        'customer_id' => $customer->id, 'customer_address_id' => $address->id,
        'status' => DeliveryOrder::STATUS_SHIPPED, 'created_by' => $user->id,
    ]);
    $deliveryOrder->items()->create([
        'sales_order_item_id' => $salesOrderItem->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id,
        'unit_id' => $unit->id, 'conversion' => 1, 'qty_order' => 10, 'qty_delivered' => 10,
        'qty_outstanding' => 0, 'qty_base' => 10,
    ]);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $salesOrder->id)
        ->set('selectedDeliveryOrderIds', [(string) $deliveryOrder->id])
        ->call('save')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::firstOrFail();

    // Selama faktur masih ada, SJ tidak boleh bisa ditagih ulang.
    $blocked = Livewire::test(SalesInvoiceComponent::class)->call('openCreate')->set('salesOrderId', $salesOrder->id);
    expect($blocked->viewData('deliveryOrders'))->toHaveCount(0);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('confirmDelete', $invoice->id)
        ->call('delete');

    expect(SalesInvoice::count())->toBe(0);

    $after = Livewire::test(SalesInvoiceComponent::class)->call('openCreate')->set('salesOrderId', $salesOrder->id);
    expect($after->viewData('deliveryOrders')->pluck('delivery_no')->all())->toBe(['SJ-003'])
        ->and($after->viewData('salesOrders')->pluck('order_no')->all())->toContain('SO-003');
});

it('menolak faktur penjualan kedua tanpa Surat Jalan', function () {
    $user = partialInvoicingUser('Sales Duplicate');
    $this->actingAs($user);

    $customer = Customer::create(['code' => 'CUS-002', 'name' => 'Pelanggan', 'is_active' => true, 'created_by' => $user->id]);
    $address = CustomerAddress::create([
        'customer_id' => $customer->id, 'code' => 'ADDR-002', 'label' => 'Gudang',
        'address_type' => 'shipping', 'address' => 'Jl. A', 'city' => 'Jakarta', 'is_primary' => true,
    ]);
    $category = ProductCategory::create(['code' => 'CAT', 'name' => 'Kategori']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'WH', 'desc' => 'Gudang', 'address' => 'Jl. B']);
    $product = Product::create(['name' => 'Produk', 'sku' => 'SKU-002', 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);

    $salesOrder = SalesOrder::create([
        'order_no' => 'SO-002', 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'grand_total' => 100000,
        'amount_due' => 100000, 'status' => 'verified', 'created_by' => $user->id,
    ]);
    $salesOrder->forceFill(['verified_at' => now()])->save();
    $salesOrder->items()->create([
        'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id,
        'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'line_total' => 100000,
    ]);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $salesOrder->id)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $salesOrder->id)
        ->call('save')
        ->assertHasErrors('salesOrderId');

    expect(SalesInvoice::count())->toBe(1);
});
