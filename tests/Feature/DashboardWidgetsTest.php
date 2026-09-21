<?php

use App\Livewire\Dashboard;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function dashboardInvoice($test, string $number, string $date, int $total, int $due, string $status = SalesInvoice::STATUS_CONFIRMED): SalesInvoice
{
    $order = SalesOrder::create(['order_no' => 'SO-'.$number, 'date' => $date, 'customer_id' => $test->customer->id, 'subtotal' => $total, 'grand_total' => $total, 'amount_due' => $total, 'status' => 'verified', 'created_by' => $test->user->id]);
    $item = $order->items()->create(['product_id' => $test->product->id, 'warehouse_id' => $test->warehouse->id, 'unit_id' => $test->unit->id, 'qty' => 2, 'conversion' => 1, 'unit_price' => $total / 2, 'discount_amount' => 0, 'line_total' => $total]);
    $invoice = SalesInvoice::create(['invoice_no' => $number, 'invoice_date' => $date, 'due_date' => $date, 'sales_order_id' => $order->id, 'customer_id' => $test->customer->id, 'subtotal' => $total, 'grand_total' => $total, 'paid_amount' => $total - $due, 'amount_due' => $due, 'status' => $status, 'created_by' => $test->user->id]);
    $invoice->items()->create(['sales_order_item_id' => $item->id, 'product_id' => $test->product->id, 'warehouse_id' => $test->warehouse->id, 'unit_id' => $test->unit->id, 'qty' => 2, 'conversion' => 1, 'unit_price' => $total / 2, 'discount_amount' => 0, 'line_total' => $total]);

    return $invoice;
}

beforeEach(function () {
    $this->user = User::factory()->superAdmin()->create();
    $this->actingAs($this->user);
    $this->customer = Customer::create(['code' => 'CUS-DASH', 'name' => 'Pelanggan Dashboard', 'is_active' => true, 'created_by' => $this->user->id]);
    $category = ProductCategory::create(['code' => 'CAT-DASH', 'name' => 'Kategori Dashboard']);
    $this->unit = ProductUnit::create(['code' => 'PCS-DASH', 'name' => 'Pcs']);
    $this->warehouse = Warehouse::create(['name' => 'Gudang Dashboard', 'desc' => '-', 'address' => '-']);
    $this->product = Product::create(['name' => 'Produk Dashboard', 'sku' => 'SKU-DASH', 'category_id' => $category->id, 'base_unit_id' => $this->unit->id, 'created_by' => (string) $this->user->id]);
});

it('summarizes confirmed sales, receivables, overdue invoices and top products', function () {
    dashboardInvoice($this, 'FP-NOW-1', today()->toDateString(), 300_000, 100_000);
    dashboardInvoice($this, 'FP-NOW-2', today()->toDateString(), 200_000, 0);
    dashboardInvoice($this, 'FP-OVERDUE', today()->subDays(45)->toDateString(), 100_000, 100_000);
    dashboardInvoice($this, 'FP-DRAFT', today()->toDateString(), 900_000, 900_000, SalesInvoice::STATUS_DRAFT);

    Livewire::test(Dashboard::class)
        ->assertViewHas('salesThisMonth', 500_000)
        ->assertViewHas('receivable', 200_000)
        ->assertViewHas('overdueInvoices', 1)
        ->assertSee('Produk Dashboard')
        ->assertSee('Penjualan 6 bulan terakhir')
        ->assertSee('Perlu tindakan');
});

it('only renders widgets for modules the role can open', function () {
    $role = Role::create(['name' => 'Gudang', 'permissions' => ['inventory.report.stock-balance']]);
    $this->actingAs(User::factory()->for($role)->create());

    Livewire::test(Dashboard::class)
        ->assertViewHas('canSales', false)
        ->assertViewHas('canFinance', false)
        ->assertDontSee('Penjualan bulan ini')
        ->assertDontSee('Laba bersih bulan ini')
        ->assertSee('Selamat datang');
});
