<?php

use App\Livewire\Inventory\InventoryTransaction\AdjustmentIn;
use App\Livewire\Inventory\InventoryTransaction\AdjustmentOut;
use App\Livewire\Inventory\InventoryTransaction\StockOpname as StockOpnameComponent;
use App\Livewire\Inventory\InventoryTransaction\TransferStock;
use App\Livewire\Inventory\Report\StockValuation;
use App\Livewire\Sales\Transaction\DeliveryOrder as DeliveryOrderComponent;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryCostService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->for(Role::create(['name' => 'Owner', 'permissions' => ['*']]))->create();
    $this->actingAs($this->user);

    foreach ([['1200', 'Persediaan', 'Asset', 'Debit'], ['5100', 'HPP', 'COGS', 'Debit'], ['6400', 'Selisih Persediaan', 'Expense', 'Debit']] as [$code, $name, $type, $normal]) {
        ChartOfAccount::updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'normal_balance' => $normal, 'is_postable' => true, 'is_active' => true]);
    }

    $this->unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $category = ProductCategory::create(['code' => 'CST', 'name' => 'Costing']);
    $this->warehouse = Warehouse::create(['name' => 'Gudang Utama', 'desc' => '-', 'address' => '-']);
    $this->product = Product::create(['name' => 'Barang HPP', 'sku' => 'SKU-HPP', 'category_id' => $category->id, 'base_unit_id' => $this->unit->id, 'created_by' => 'test', 'is_active' => true]);
});

function journalLines(string $sourceType, int $sourceId): array
{
    $journal = JournalEntry::with('lines.chartOfAccount')->where('source_type', $sourceType)->where('source_id', $sourceId)->firstOrFail();

    return [
        'status' => $journal->status,
        'lines' => $journal->lines->map(fn ($line) => [$line->chartOfAccount->code, (int) $line->debit, (int) $line->credit])->all(),
    ];
}

it('moves the average cost on every receipt and keeps it when goods leave', function () {
    $costs = app(InventoryCostService::class);

    $costs->receive($this->product->id, 10, 1000);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);

    // 10 × 1.000 + 10 × 1.600 = 26.000 / 20 = 1.300
    $costs->receive($this->product->id, 10, 1600);
    StockBalance::where('product_id', $this->product->id)->increment('quantity', 10);
    expect($this->product->fresh()->average_cost)->toBe(1300.0);

    // Membatalkan penerimaan kedua mengembalikan rata-rata ke biaya awal.
    $costs->reverseReceipt($this->product->id, 10, 1600);
    expect($this->product->fresh()->average_cost)->toBe(1000.0);
});

it('posts cost of goods sold when a delivery order ships and reverses it when cancelled', function () {
    $this->product->update(['average_cost' => 2500]);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);
    $customer = Customer::factory()->create();
    $address = CustomerAddress::factory()->for($customer)->create();
    $order = SalesOrder::create([
        'order_no' => 'SO-HPP-1', 'date' => now()->toDateString(), 'customer_id' => $customer->id, 'customer_address_id' => $address->id,
        'subtotal' => 40000, 'discount_total' => 0, 'tax_amount' => 0, 'grand_total' => 40000, 'dp_amount' => 0, 'amount_due' => 40000,
        'status' => 'verified', 'verified_at' => now(), 'created_by' => $this->user->id,
    ]);
    $orderItem = $order->items()->create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'unit_id' => $this->unit->id, 'qty' => 4, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => 40000]);
    $delivery = DeliveryOrder::create([
        'delivery_no' => 'SJ-HPP-1', 'delivery_date' => now()->toDateString(), 'sales_order_id' => $order->id,
        'customer_id' => $customer->id, 'customer_address_id' => $address->id, 'status' => DeliveryOrder::STATUS_DRAFT, 'created_by' => $this->user->id,
    ]);
    $delivery->items()->create(['sales_order_item_id' => $orderItem->id, 'product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'unit_id' => $this->unit->id, 'conversion' => 1, 'qty_order' => 4, 'qty_delivered' => 4, 'qty_outstanding' => 0, 'qty_base' => 4]);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openConfirmShipment', $delivery->id)
        ->call('confirmShipment');

    expect(journalLines(JournalEntry::SOURCE_DELIVERY_ORDER, $delivery->id))->toBe([
        'status' => JournalEntry::STATUS_POSTED,
        'lines' => [['5100', 10000, 0], ['1200', 0, 10000]],
    ])->and((float) $delivery->items()->value('unit_cost'))->toBe(2500.0);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCancelShipment', $delivery->id)
        ->call('cancelShipment');

    expect(journalLines(JournalEntry::SOURCE_DELIVERY_ORDER, $delivery->id)['status'])->toBe(JournalEntry::STATUS_CANCELLED)
        ->and(StockBalance::where('product_id', $this->product->id)->value('quantity'))->toBe(10)
        ->and($this->product->fresh()->average_cost)->toBe(2500.0);
});

it('journals stock adjustments at average cost and reverses them on cancel', function () {
    $this->product->update(['average_cost' => 1500]);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);

    $out = StockAdjustment::create(['adjustment_no' => 'ADO-T-1', 'date' => now()->toDateString(), 'type' => 'out', 'warehouse_id' => $this->warehouse->id, 'status' => 'draft', 'created_by' => $this->user->id]);
    $out->items()->create(['product_id' => $this->product->id, 'unit_id' => $this->unit->id, 'qty' => 4, 'conversion' => 1]);

    Livewire::test(AdjustmentOut::class)->call('confirmApprove', $out->id)->call('approve');

    expect(StockBalance::where('product_id', $this->product->id)->value('quantity'))->toBe(6)
        ->and(journalLines(JournalEntry::SOURCE_STOCK_ADJUSTMENT, $out->id)['lines'])->toBe([['6400', 6000, 0], ['1200', 0, 6000]]);

    Livewire::test(AdjustmentOut::class)->call('confirmCancel', $out->id)->call('cancelAdjustment');

    expect($out->fresh()->status)->toBe('cancelled')
        ->and(StockBalance::where('product_id', $this->product->id)->value('quantity'))->toBe(10)
        ->and(journalLines(JournalEntry::SOURCE_STOCK_ADJUSTMENT, $out->id)['status'])->toBe(JournalEntry::STATUS_CANCELLED);
});

it('refuses to cancel an adjustment in whose stock has already been used', function () {
    $in = StockAdjustment::create(['adjustment_no' => 'ADI-T-1', 'date' => now()->toDateString(), 'type' => 'in', 'warehouse_id' => $this->warehouse->id, 'status' => 'draft', 'created_by' => $this->user->id]);
    $in->items()->create(['product_id' => $this->product->id, 'unit_id' => $this->unit->id, 'qty' => 5, 'conversion' => 1]);

    Livewire::test(AdjustmentIn::class)->call('confirmApprove', $in->id)->call('approve');
    StockBalance::where('product_id', $this->product->id)->decrement('quantity', 3);

    Livewire::test(AdjustmentIn::class)->call('confirmCancel', $in->id)->call('cancelAdjustment');

    expect($in->fresh()->status)->toBe('approved')
        ->and(StockBalance::where('product_id', $this->product->id)->value('quantity'))->toBe(2);
});

it('moves stock back to the source warehouse when an approved transfer is cancelled', function () {
    $target = Warehouse::create(['name' => 'Gudang Cabang', 'desc' => '-', 'address' => '-']);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 2]);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $target->id, 'quantity' => 8]);
    $transfer = StockTransfer::create(['trf_no' => 'TRF-T-1', 'date' => now()->toDateString(), 'warehouse_from_id' => $this->warehouse->id, 'warehouse_to_id' => $target->id, 'status' => 'approved', 'created_by' => $this->user->id]);
    $transfer->items()->create(['product_id' => $this->product->id, 'unit_id' => $this->unit->id, 'stock_available' => 10, 'qty' => 8, 'conversion' => 1]);

    Livewire::test(TransferStock::class)->call('confirmCancel', $transfer->id)->call('cancelTransfer');

    expect($transfer->fresh()->status)->toBe('cancelled')
        ->and(StockBalance::where('warehouse_id', $this->warehouse->id)->value('quantity'))->toBe(10)
        ->and(StockBalance::where('warehouse_id', $target->id)->value('quantity'))->toBe(0);
});

it('books stock opname differences as adjustments and reverses them on cancel', function () {
    $this->product->update(['average_cost' => 1000]);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 10]);

    Livewire::test(StockOpnameComponent::class)
        ->call('openCreate')
        ->set('warehouseId', $this->warehouse->id)
        ->call('loadProducts')
        ->assertSet("items.{$this->product->id}.system_qty", 10)
        ->set("items.{$this->product->id}.physical_qty", 7)
        ->call('save')
        ->assertHasNoErrors();

    $opname = StockOpname::firstOrFail();
    Livewire::test(StockOpnameComponent::class)->call('confirmApprove', $opname->id)->call('approve');

    $adjustment = $opname->adjustments()->firstOrFail();
    expect($opname->fresh()->status)->toBe(StockOpname::STATUS_APPROVED)
        ->and(StockBalance::where('product_id', $this->product->id)->value('quantity'))->toBe(7)
        ->and($adjustment->type)->toBe('out')
        ->and(journalLines(JournalEntry::SOURCE_STOCK_ADJUSTMENT, $adjustment->id)['lines'])->toBe([['6400', 3000, 0], ['1200', 0, 3000]]);

    Livewire::test(StockOpnameComponent::class)->call('confirmCancel', $opname->id)->call('cancelOpname');

    expect($opname->fresh()->status)->toBe(StockOpname::STATUS_CANCELLED)
        ->and(StockBalance::where('product_id', $this->product->id)->value('quantity'))->toBe(10);
});

it('values inventory at quantity times average cost', function () {
    $this->product->update(['average_cost' => 1250.5]);
    StockBalance::create(['product_id' => $this->product->id, 'warehouse_id' => $this->warehouse->id, 'quantity' => 4]);

    Livewire::test(StockValuation::class)
        ->assertViewHas('totalValue', 5002.0)
        ->assertSee('SKU-HPP')
        ->assertSee('Rp 5.002');
});
