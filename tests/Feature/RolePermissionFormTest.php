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

    foreach ($permissions->diff($permissionsWithoutRoutes) as $permission) {
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
