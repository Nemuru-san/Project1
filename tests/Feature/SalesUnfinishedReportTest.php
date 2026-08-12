<?php

use App\Livewire\Sales\Report\UnfinishedSalesOrder;
use App\Livewire\Sales\Report\UnpaidSalesInvoice;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->customer = Customer::create(['code' => 'CUS-REPORT-S', 'name' => 'Pelanggan Laporan Penjualan', 'is_active' => true, 'created_by' => $this->user->id]);
    $category = ProductCategory::create(['code' => 'CAT-REPORT-S', 'name' => 'Kategori Laporan']);
    $this->unit = ProductUnit::create(['code' => 'PCS-RS', 'name' => 'Pcs']);
    $this->warehouse = Warehouse::create(['name' => 'Gudang Laporan Penjualan', 'desc' => '-', 'address' => '-']);
    $this->product = Product::create(['name' => 'Produk Laporan Penjualan', 'sku' => 'SKU-REPORT-S', 'category_id' => $category->id, 'base_unit_id' => $this->unit->id, 'created_by' => (string) $this->user->id]);
});

function salesReportOrder($test, string $number, int $qty = 10): SalesOrder
{
    $order = SalesOrder::create(['order_no' => $number, 'date' => '2026-08-01', 'customer_id' => $test->customer->id, 'subtotal' => 100000, 'grand_total' => 100000, 'amount_due' => 100000, 'status' => 'verified', 'created_by' => $test->user->id]);
    $order->forceFill(['verified_at' => now(), 'verified_by' => $test->user->id])->save();
    $order->items()->create(['product_id' => $test->product->id, 'warehouse_id' => $test->warehouse->id, 'unit_id' => $test->unit->id, 'qty' => $qty, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => $qty * 10000]);

    return $order;
}

function salesReportDelivery($test, SalesOrder $order, string $number, int $qty, string $status): DeliveryOrder
{
    $delivery = DeliveryOrder::create(['delivery_no' => $number, 'delivery_date' => '2026-08-02', 'sales_order_id' => $order->id, 'customer_id' => $test->customer->id, 'status' => $status, 'created_by' => $test->user->id]);
    $item = $order->items->first();
    $delivery->items()->create(['sales_order_item_id' => $item->id, 'product_id' => $test->product->id, 'warehouse_id' => $test->warehouse->id, 'unit_id' => $test->unit->id, 'conversion' => 1, 'qty_order' => $item->qty, 'qty_delivered' => $qty, 'qty_outstanding' => $item->qty - $qty, 'qty_base' => $qty]);

    return $delivery;
}

it('shows only confirmed sales orders with quantities not yet shipped', function () {
    $partial = salesReportOrder($this, 'SO-REPORT-PARTIAL');
    salesReportDelivery($this, $partial, 'SJ-REPORT-SHIPPED', 4, DeliveryOrder::STATUS_SHIPPED);
    salesReportDelivery($this, $partial, 'SJ-REPORT-DRAFT', 3, DeliveryOrder::STATUS_DRAFT);

    $pending = salesReportOrder($this, 'SO-REPORT-PENDING', 5);
    $completed = salesReportOrder($this, 'SO-REPORT-COMPLETE', 3);
    salesReportDelivery($this, $completed, 'SJ-REPORT-COMPLETE', 3, DeliveryOrder::STATUS_SHIPPED);

    Livewire::test(UnfinishedSalesOrder::class)
        ->assertSee('SO-REPORT-PARTIAL')->assertSee('SO-REPORT-PENDING')->assertDontSee('SO-REPORT-COMPLETE')
        ->assertViewHas('summary', fn (array $summary) => $summary === ['count' => 2, 'ordered' => 15, 'shipped' => 4, 'outstanding' => 11])
        ->set('deliveryFilter', 'partial')->assertSee('SO-REPORT-PARTIAL')->assertDontSee('SO-REPORT-PENDING');
});

it('shows only confirmed unpaid sales invoices and calculates receivable aging summaries', function () {
    $order = salesReportOrder($this, 'SO-INVOICE-REPORT');
    SalesInvoice::create(['invoice_no' => 'FP-OVERDUE', 'invoice_date' => today()->subDays(40), 'due_date' => today()->subDays(10), 'sales_order_id' => $order->id, 'customer_id' => $this->customer->id, 'subtotal' => 100000, 'grand_total' => 100000, 'paid_amount' => 40000, 'amount_due' => 60000, 'status' => SalesInvoice::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $this->user->id, 'created_by' => $this->user->id]);
    $order2 = salesReportOrder($this, 'SO-INVOICE-DRAFT');
    SalesInvoice::create(['invoice_no' => 'FP-DRAFT-HIDDEN', 'invoice_date' => today(), 'sales_order_id' => $order2->id, 'customer_id' => $this->customer->id, 'grand_total' => 50000, 'amount_due' => 50000, 'status' => SalesInvoice::STATUS_DRAFT, 'created_by' => $this->user->id]);
    $order3 = salesReportOrder($this, 'SO-INVOICE-PAID');
    SalesInvoice::create(['invoice_no' => 'FP-PAID-HIDDEN', 'invoice_date' => today(), 'sales_order_id' => $order3->id, 'customer_id' => $this->customer->id, 'grand_total' => 50000, 'paid_amount' => 50000, 'amount_due' => 0, 'status' => SalesInvoice::STATUS_CONFIRMED, 'confirmed_at' => now(), 'created_by' => $this->user->id]);

    Livewire::test(UnpaidSalesInvoice::class)
        ->assertSee('FP-OVERDUE')->assertDontSee('FP-DRAFT-HIDDEN')->assertDontSee('FP-PAID-HIDDEN')
        ->assertSee('10 hari')
        ->assertViewHas('summary', fn (array $summary) => $summary['count'] === 1 && $summary['outstanding'] === 60000 && $summary['overdue'] === 60000)
        ->set('paymentStatusFilter', 'partial')->assertSee('FP-OVERDUE')
        ->set('dueFilter', 'overdue')->assertSee('FP-OVERDUE');
});

it('exposes both authenticated sales report routes', function () {
    $this->get(route('sales.report.po-outstanding'))->assertOk()->assertSee('SO Belum Selesai');
    $this->get(route('sales.report.invoice-outstanding'))->assertOk()->assertSee('Faktur Penjualan Belum Lunas');
});
