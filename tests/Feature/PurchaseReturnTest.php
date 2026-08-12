<?php

use App\Livewire\Purchasing\ReturnTransaction\PurchaseReturn as PurchaseReturnComponent;
use App\Livewire\Purchasing\ReturnTransaction\PurchaseReturnInvoice as ReturnInvoiceComponent;
use App\Models\ChartOfAccount;
use App\Models\GoodsReceive;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnInvoice;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function purchaseReturnFixture(array $permissions): array
{
    $role = Role::create(['name' => 'Purchase Return '.uniqid(), 'permissions' => $permissions]);
    $user = User::factory()->for($role)->create();
    test()->actingAs($user);
    $supplier = Supplier::create(['code' => 'SUP-RET-'.uniqid(), 'name' => 'Supplier Retur', 'address' => 'Alamat', 'contact' => 'Kontak', 'created_by' => (string) $user->id]);
    $category = ProductCategory::create(['code' => 'CAT-RET-'.uniqid(), 'name' => 'Kategori Retur']);
    $unit = ProductUnit::create(['code' => 'PCS-'.uniqid(), 'name' => 'Pcs']);
    $product = Product::create(['name' => 'Produk Retur', 'sku' => 'SKU-RET-'.uniqid(), 'category_id' => $category->id, 'created_by' => (string) $user->id]);
    $price = ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 10000]);
    $warehouse = Warehouse::create(['name' => 'Gudang Retur '.uniqid(), 'desc' => 'Gudang', 'address' => 'Alamat']);
    $po = PurchaseOrder::create(['code' => 'PO-RET-'.uniqid(), 'date' => today(), 'supplier_id' => $supplier->id, 'user_id' => $user->id, 'total_price' => 100000, 'tax' => true, 'ppn' => 11000, 'gross' => 100000, 'nett' => 111000, 'status' => PurchaseOrder::STATUS_RECEIVED]);
    $poItem = $po->items()->create(['product_id' => $product->id, 'price_id' => $price->id, 'unit_id' => $unit->id, 'qty' => 10, 'price' => 10000, 'conversion' => 1, 'qty_base' => 10, 'total_harga' => 100000, 'disc' => 0]);
    $gr = GoodsReceive::create(['code' => 'GR-RET-'.uniqid(), 'date' => today(), 'supplier_id' => $supplier->id, 'purchase_order_id' => $po->id, 'status' => GoodsReceive::STATUS_RECEIVED, 'created_by' => $user->id]);
    $grItem = $gr->items()->create(['purchase_order_item_id' => $poItem->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'conversion' => 1, 'qty_order' => 10, 'qty_received' => 10, 'qty_outstanding' => 0, 'qty_base' => 10]);
    $stock = StockBalance::create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 10]);

    return compact('user', 'supplier', 'unit', 'product', 'warehouse', 'po', 'poItem', 'gr', 'grItem', 'stock');
}

it('creates a purchase return draft and only reduces stock after confirmation', function () {
    $data = purchaseReturnFixture(['purchases.return.purchase-return', 'purchases.return.purchase-return.confirm']);

    Livewire::test(PurchaseReturnComponent::class)
        ->call('openCreate')
        ->set('goodsReceiveId', $data['gr']->id)
        ->set('items.0.qty', 3)
        ->set('items.0.reason', 'Barang rusak')
        ->call('save')
        ->assertHasNoErrors();

    $return = PurchaseReturn::firstOrFail();
    expect($return->status)->toBe(PurchaseReturn::STATUS_DRAFT)
        ->and($return->items->first()->qty)->toBe(3)
        ->and($data['stock']->fresh()->quantity)->toBe(10);

    Livewire::test(PurchaseReturnComponent::class)
        ->call('confirmReturn', $return->id)
        ->call('confirm')
        ->assertHasNoErrors();

    expect($return->fresh()->status)->toBe(PurchaseReturn::STATUS_CONFIRMED)
        ->and($data['stock']->fresh()->quantity)->toBe(7);
});

it('posts a purchase return invoice and reduces supplier debt with a balanced journal', function () {
    $data = purchaseReturnFixture([
        'purchases.return.purchase-return-invoice',
        'purchases.return.purchase-return-invoice.post',
    ]);

    $return = PurchaseReturn::create([
        'return_no' => 'PR/TEST/0001',
        'return_date' => today(),
        'supplier_id' => $data['supplier']->id,
        'goods_receive_id' => $data['gr']->id,
        'purchase_order_id' => $data['po']->id,
        'status' => PurchaseReturn::STATUS_CONFIRMED,
        'confirmed_at' => now(),
        'confirmed_by' => $data['user']->id,
        'created_by' => $data['user']->id,
    ]);
    $return->items()->create([
        'goods_receive_item_id' => $data['grItem']->id,
        'purchase_order_item_id' => $data['poItem']->id,
        'product_id' => $data['product']->id,
        'warehouse_id' => $data['warehouse']->id,
        'unit_id' => $data['unit']->id,
        'conversion' => 1,
        'qty' => 3,
        'qty_base' => 3,
        'unit_price' => 10000,
        'subtotal' => 30000,
        'reason' => 'Rusak',
    ]);
    $purchaseInvoice = PurchaseInvoice::create([
        'code' => 'PIV-RET-001',
        'date' => today(),
        'supplier_id' => $data['supplier']->id,
        'purchase_order_id' => $data['po']->id,
        'sub_total' => 100000,
        'tax' => true,
        'tax_amount' => 11000,
        'grand_total' => 111000,
        'paid_amount' => 0,
        'remaining_amount' => 111000,
        'status' => PurchaseInvoice::STATUS_POSTED,
        'payment_status' => PurchaseInvoice::PAYMENT_UNPAID,
        'created_by' => $data['user']->id,
    ]);
    foreach ([['1200', 'Persediaan', 'Asset', 'Debit'], ['1400', 'Pajak Masukan', 'Asset', 'Debit'], ['2100', 'Utang Usaha', 'Liability', 'Credit']] as [$code, $name, $type, $normal]) {
        ChartOfAccount::create(['code' => $code, 'name' => $name, 'type' => $type, 'normal_balance' => $normal, 'is_active' => true, 'is_postable' => true]);
    }

    Livewire::test(ReturnInvoiceComponent::class)
        ->call('openCreate')
        ->set('purchaseReturnId', $return->id)
        ->call('save')
        ->assertHasNoErrors();

    $returnInvoice = PurchaseReturnInvoice::firstOrFail();
    expect($returnInvoice->grand_total)->toBe(33300)
        ->and($returnInvoice->status)->toBe(PurchaseReturnInvoice::STATUS_DRAFT);

    Livewire::test(ReturnInvoiceComponent::class)
        ->call('confirmPost', $returnInvoice->id)
        ->call('post')
        ->assertHasNoErrors();

    $journal = JournalEntry::where('source_type', JournalEntry::SOURCE_PURCHASE_RETURN_INVOICE)->firstOrFail();
    expect($returnInvoice->fresh()->status)->toBe(PurchaseReturnInvoice::STATUS_POSTED)
        ->and($purchaseInvoice->fresh()->remaining_amount)->toBe(77700)
        ->and((int) $journal->lines->sum('debit'))->toBe(33300)
        ->and((int) $journal->lines->sum('credit'))->toBe(33300);
});

it('exposes both authenticated purchase return pages', function () {
    $data = purchaseReturnFixture(['*']);
    $this->actingAs($data['user'])->get(route('purchases.return.purchase-return'))->assertOk()->assertSee('Retur Pembelian');
    $this->get(route('purchases.return.purchase-return-invoice'))->assertOk()->assertSee('Faktur Retur Pembelian');
});
