<?php

use App\Livewire\Users\RoleUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('offers every implemented ERP module in the role form with valid route keys', function () {
    $component = new RoleUser;
    $permissions = collect($component->permissionGroups)->flatMap(
        fn (array $group) => array_keys($group)
    );

    $expected = [
        'purchases.transaction.purchase-order.approve',
        'purchases.transaction.purchase-order.delete',
        'purchases.transaction.good-receive.receive',
        'purchases.transaction.good-receive.delete',
        'purchases.transaction.purchase-invoice.post',
        'purchases.transaction.purchase-invoice.delete',
        'inventory.transaction.transfer-stock.approve',
        'inventory.transaction.transfer-stock.delete',
        'inventory.transaction.adjustment-in.approve',
        'inventory.transaction.adjustment-in.delete',
        'inventory.transaction.adjustment-out.approve',
        'inventory.transaction.adjustment-out.delete',
        'purchases.report.unfinished-purchase-order',
        'purchases.report.unfinished-purchase-invoice',
        'purchases.return.purchase-return',
        'purchases.return.purchase-return-invoice',
        'sales.master.salesman',
        'sales.transaction.salesCanvas',
        'sales.transaction.salesPreOrder',
        'sales.transaction.salesOrder',
        'sales.transaction.delivery-order',
        'sales.transaction.sales-invoice',
        'sales.report.po-outstanding',
        'sales.report.invoice-outstanding',
        'finance.master.chart-of-accounts',
        'finance.master.bank-accounts',
        'finance.master.payment-terms',
        'finance.transaction.ar-payment',
        'finance.transaction.ar-dp-payment',
        'finance.report.journal-entry',
    ];

    $permissionsWithoutRoutes = [
        'sales.transaction.delivery-order',
        'sales.transaction.sales-invoice',
        'purchases.return.purchase-return',
        'purchases.return.purchase-return-invoice',
        'sales.report.po-outstanding',
        'sales.report.invoice-outstanding',
        'finance.master.payment-terms',
        'finance.transaction.ar-payment',
    ];

    $modulePermissions = $permissions->reject(fn (string $permission) => str_ends_with($permission, '.confirm')
        || str_ends_with($permission, '.convert')
        || str_ends_with($permission, '.approve')
        || str_ends_with($permission, '.receive')
        || str_ends_with($permission, '.post')
        || str_ends_with($permission, '.delete'));

    foreach ($modulePermissions->diff($permissionsWithoutRoutes) as $permission) {
        expect(Route::has($permission))->toBeTrue("Permission {$permission} tidak memiliki route.");
    }

    foreach ($expected as $permission) {
        expect($permissions->all())->toContain($permission);
    }
});

it('renders the newly added permission groups in the role form', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(RoleUser::class)
        ->call('openCreate')
        ->assertSee('Tenaga Penjualan')
        ->assertSee('Penjualan Kanvas')
        ->assertSee('Pesanan Awal')
        ->assertSee('Surat Jalan')
        ->assertSee('Faktur Penjualan')
        ->assertSee('Retur Pembelian')
        ->assertSee('Faktur Retur Pembelian')
        ->assertSee('Pembayaran Piutang')
        ->assertSee('Termin Pembayaran')
        ->assertSee('Daftar Akun')
        ->assertSee('Rekening Bank')
        ->assertSee('Entri Jurnal')
        ->assertSee('PO Belum Selesai');
});

it('converts valid legacy permissions when editing a role', function () {
    $this->actingAs(User::factory()->create());

    $role = Role::create([
        'name' => 'Role Lama',
        'permissions' => [
            'sales.transaction.sales-order',
            'purchases.report.po-outstanding',
            'sales.report.po-outstanding',
        ],
    ]);

    Livewire::test(RoleUser::class)
        ->call('openEdit', $role->id)
        ->assertSet('selectedPermissions', [
            'sales.transaction.salesOrder',
            'purchases.report.unfinished-purchase-order',
            'sales.report.po-outstanding',
        ]);
});
it('offers granular confirmation conversion and deletion permissions for sales workflows', function () {
    $component = new RoleUser;
    $permissions = collect($component->permissionGroups)->flatMap(fn (array $group) => array_keys($group));

    expect($permissions->all())->toContain(
        'sales.transaction.salesCanvas.confirm',
        'sales.transaction.salesCanvas.convert',
        'sales.transaction.salesCanvas.delete',
        'sales.transaction.salesPreOrder.confirm',
        'sales.transaction.salesPreOrder.convert',
        'sales.transaction.salesPreOrder.delete',
    );

    $role = Role::create([
        'name' => 'Owner Test',
        'permissions' => ['sales.transaction.salesCanvas.confirm'],
    ]);
    $user = User::factory()->for($role)->create();

    expect($user->canAccessModule('sales.transaction.salesCanvas'))->toBeTrue()
        ->and($user->canPerform('sales.transaction.salesCanvas', 'confirm'))->toBeTrue()
        ->and($user->canPerform('sales.transaction.salesCanvas', 'convert'))->toBeFalse();
});
it('groups action permissions with their parent module and keeps selections consistent', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(RoleUser::class)->call('openCreate');
    $html = $component->html();

    expect($html)
        ->toContain('Pesanan Pembelian', 'Setujui / Ubah Status', 'Penerimaan Barang', 'Terima / Ubah Status')
        ->not->toContain('Pesanan Pembelian - Otorisasi');

    $component
        ->call('togglePermission', 'purchases.transaction.purchase-order.approve')
        ->assertSet('selectedPermissions', [
            'purchases.transaction.purchase-order',
            'purchases.transaction.purchase-order.approve',
        ])
        ->call('togglePermission', 'purchases.transaction.purchase-order')
        ->assertSet('selectedPermissions', []);
});
