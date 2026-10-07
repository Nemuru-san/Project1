<?php

use App\Livewire\Users\ActivityLog as ActivityLogComponent;
use App\Livewire\Users\CompanyProfile as CompanyProfileComponent;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\CompanyProfile;
use Livewire\Livewire;

function auditUser(string $role = 'Super Admin', array $permissions = ['*']): User
{
    return User::factory()->for(Role::create(['name' => $role, 'permissions' => $permissions]))->create();
}

function salesInvoiceFor(User $creator, ?int $salesmanId, string $number): SalesInvoice
{
    $customer = Customer::factory()->create();
    $order = SalesOrder::create([
        'order_no' => 'SO-'.$number, 'date' => now()->toDateString(), 'customer_id' => $customer->id, 'salesman_id' => $salesmanId,
        'subtotal' => 50000, 'discount_total' => 0, 'tax_amount' => 0, 'grand_total' => 50000, 'dp_amount' => 0, 'amount_due' => 50000,
        'status' => 'completed', 'verified_at' => now(), 'created_by' => $creator->id,
    ]);

    return SalesInvoice::create([
        'invoice_no' => $number, 'invoice_date' => now()->toDateString(), 'sales_order_id' => $order->id, 'customer_id' => $customer->id,
        'subtotal' => 50000, 'discount_total' => 0, 'tax_amount' => 0, 'grand_total' => 50000, 'dp_amount' => 0, 'paid_amount' => 0,
        'amount_due' => 50000, 'status' => SalesInvoice::STATUS_CONFIRMED, 'created_by' => $creator->id,
    ]);
}

it('records who created and changed data in the activity log', function () {
    $admin = auditUser();
    $this->actingAs($admin);

    $supplier = Supplier::create(['code' => 'SUP-LOG', 'name' => 'Pemasok Log', 'address' => 'A', 'contact' => '1', 'created_by' => 'test']);
    $supplier->update(['name' => 'Pemasok Log Baru']);

    $updated = ActivityLog::where('subject_type', 'Supplier')->where('event', 'updated')->firstOrFail();
    expect(ActivityLog::where('subject_type', 'Supplier')->where('event', 'created')->value('subject_label'))->toBe('SUP-LOG')
        ->and($updated->user_id)->toBe($admin->id)
        ->and($updated->changes['name'])->toBe(['old' => 'Pemasok Log', 'new' => 'Pemasok Log Baru']);

    Livewire::test(ActivityLogComponent::class)
        ->set('subjectFilter', 'Supplier')
        ->assertSee('SUP-LOG')
        ->call('openDetail', $updated->id)
        ->assertSee('Pemasok Log Baru');

    $this->get(route('user.action.activity-log'))->assertOk()->assertSee('Log Aktivitas');
});

it('saves the company profile from the app and uses it on printed documents', function () {
    $admin = auditUser();
    $this->actingAs($admin);

    Livewire::test(CompanyProfileComponent::class)
        ->set('profile.name', 'PT Contoh Plastik')
        ->set('profile.city', 'Medan')
        ->set('profile.bank.account_number', '123-456')
        ->call('save')
        ->assertHasNoErrors();

    CompanyProfile::flush();
    expect(CompanyProfile::get()['name'])->toBe('PT Contoh Plastik')
        ->and(CompanyProfile::get()['bank']['account_number'])->toBe('123-456');

    $invoice = salesInvoiceFor($admin, null, 'FP-PROFILE-1');
    $this->get(route('sales.transaction.salesInvoice.view', $invoice->id))
        ->assertOk()
        ->assertSee('PT Contoh Plastik');

    Livewire::test(CompanyProfileComponent::class)->set('profile.name', '')->call('save')->assertHasErrors(['profile.name' => 'required']);
});

it('blocks a salesman from opening another salesman invoice by url', function () {
    $owner = auditUser('Salesman', ['sales.transaction.sales-invoice']);
    $other = auditUser('Salesman Lain', ['sales.transaction.sales-invoice']);
    $ownSalesman = Salesman::create(['code' => 'SM-A', 'name' => 'Sales A', 'user_id' => $owner->id, 'is_active' => true]);
    Salesman::create(['code' => 'SM-B', 'name' => 'Sales B', 'user_id' => $other->id, 'is_active' => true]);
    $invoice = salesInvoiceFor(auditUser('Admin Faktur', ['*']), $ownSalesman->id, 'FP-OWN-1');

    $this->actingAs($other)->get(route('sales.transaction.salesInvoice.print', $invoice->id))->assertForbidden();
    $this->actingAs($owner)->get(route('sales.transaction.salesInvoice.view', $invoice->id))->assertOk();
});

it('prints and exports filtered purchase order reports', function () {
    $this->actingAs(auditUser());
    $supplier = Supplier::create(['code' => 'SUP-RPT', 'name' => 'Pemasok Laporan', 'address' => 'A', 'contact' => '1', 'created_by' => 'test']);
    foreach ([['PO-RPT-IN', '2026-07-10', 100000], ['PO-RPT-OUT', '2026-06-01', 999000]] as [$code, $date, $nett]) {
        PurchaseOrder::create([
            'code' => $code, 'date' => $date, 'supplier_id' => $supplier->id, 'user_id' => auth()->id(),
            'total_price' => $nett, 'tax' => false, 'ppn' => 0, 'gross' => $nett, 'nett' => $nett, 'status' => PurchaseOrder::STATUS_APPROVED,
        ]);
    }

    $this->get(route('purchases.transaction.purchase-order.report', ['date_from' => '2026-07-01', 'date_to' => '2026-07-31']))
        ->assertOk()
        ->assertSee('LAPORAN PESANAN PEMBELIAN')
        ->assertSee('PO-RPT-IN')
        ->assertDontSee('PO-RPT-OUT')
        ->assertSee('100.000');

    $csv = $this->get(route('purchases.transaction.purchase-order.report', ['date_from' => '2026-07-01', 'format' => 'csv']));
    $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv->streamedContent())->toContain('PO-RPT-IN')->not->toContain('PO-RPT-OUT');
});

it('limits sales invoice reports to the salesman own invoices and enforces module access', function () {
    $salesUser = auditUser('Salesman', ['sales.transaction.sales-invoice']);
    $salesman = Salesman::create(['code' => 'SM-RPT', 'name' => 'Sales Laporan', 'user_id' => $salesUser->id, 'is_active' => true]);
    $admin = auditUser('Admin Faktur', ['*']);
    salesInvoiceFor($admin, $salesman->id, 'FP-RPT-MINE');
    salesInvoiceFor($admin, null, 'FP-RPT-OTHER');

    $this->actingAs($salesUser)->get(route('sales.transaction.salesInvoice.report'))
        ->assertOk()
        ->assertSee('FP-RPT-MINE')
        ->assertDontSee('FP-RPT-OTHER');

    $this->actingAs(auditUser('Gudang', ['inventory.report.stock-balance']))
        ->get(route('purchases.transaction.purchase-order.report'))
        ->assertForbidden();
});

it('renders the new pages and shows them in the sidebar', function () {
    $this->actingAs(auditUser());

    foreach ([
        'inventory.transaction.stock-opname' => 'Stok Opname',
        'inventory.report.stock-valuation' => 'Nilai Persediaan',
        'user.action.activity-log' => 'Log Aktivitas',
        'user.setting.company-profile' => 'Profil Perusahaan',
    ] as $route => $title) {
        $this->get(route($route))->assertOk()->assertSee($title);
    }

    $this->get(route('dashboard'))->assertOk()->assertDontSee('Termin Pembayaran');
});

it('grants cancellation through the role permission instead of the role name', function () {
    expect(auditUser('Owner', ['sales.transaction.salesOrder'])->canCancelTransactions())->toBeFalse()
        ->and(auditUser('Kepala Toko', ['transactions.cancel'])->canCancelTransactions())->toBeTrue()
        ->and(collect(config('permissions.groups'))->flatMap(fn ($group) => array_keys($group))->all())
        ->toContain('transactions.cancel', 'inventory.transaction.stock-opname', 'inventory.report.stock-valuation', 'user.setting.company-profile')
        ->not->toContain('finance.master.payment-terms');
});
