<?php

use App\Http\Middleware\EnsureModuleAccess;
use App\Models\Role;
use App\Models\User;

it('maps page and print routes to their module permission', function () {
    expect(EnsureModuleAccess::permissionForRoute('sales.transaction.salesInvoice'))->toBe('sales.transaction.sales-invoice')
        ->and(EnsureModuleAccess::permissionForRoute('sales.transaction.salesInvoice.print'))->toBe('sales.transaction.sales-invoice')
        ->and(EnsureModuleAccess::permissionForRoute('sales.transaction.deliveryOrder.view'))->toBe('sales.transaction.delivery-order')
        ->and(EnsureModuleAccess::permissionForRoute('purchases.transaction.purchase-order.print'))->toBe('purchases.transaction.purchase-order')
        ->and(EnsureModuleAccess::permissionForRoute('dashboard'))->toBeNull()
        ->and(EnsureModuleAccess::permissionForRoute('profile.edit'))->toBeNull();
});

it('blocks module pages the role does not have while keeping the dashboard open', function () {
    $role = Role::create(['name' => 'Gudang', 'permissions' => ['inventory.report.stock-balance']]);
    $this->actingAs(User::factory()->for($role)->create());

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('inventory.report.stock-balance'))->assertOk();
    $this->get(route('sales.transaction.salesOrder'))->assertForbidden();
    $this->get(route('finance.report.profit-loss'))->assertForbidden();
});

it('grants module access through action permissions and the wildcard', function () {
    $verifier = Role::create(['name' => 'Verifikator', 'permissions' => ['sales.transaction.salesOrder.verify']]);
    $this->actingAs(User::factory()->for($verifier)->create())
        ->get(route('sales.transaction.salesOrder'))->assertOk();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('finance.report.balance-sheet'))->assertOk();
});

it('hides sidebar menus for modules the role cannot open', function () {
    $role = Role::create(['name' => 'Gudang', 'permissions' => ['inventory.report.stock-balance']]);

    $this->actingAs(User::factory()->for($role)->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Saldo Stok')
        ->assertDontSee('Faktur Penjualan')
        ->assertDontSee('Laba Rugi')
        ->assertDontSee('Peran Pengguna');
});

it('blocks users without a role from every module page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('sales.master.customer'))->assertForbidden();
});
