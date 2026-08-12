<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['permissions' => ['*']]
        );

        $salesmanPermissions = [
            'dashboard',
            'sales.master.customer',
            'sales.transaction.salesCanvas',
            'sales.transaction.salesPreOrder',
            'sales.transaction.salesOrder',
            'sales.transaction.delivery-order',
            'sales.transaction.sales-invoice',
            'sales.report.po-outstanding',
            'sales.report.invoice-outstanding',
        ];

        $salesman = Role::withTrashed()->firstOrCreate(
            ['name' => 'Salesman'],
            ['permissions' => $salesmanPermissions]
        );

        $salesman->restore();
        $salesman->permissions = array_values(array_unique([
            ...($salesman->permissions ?? []),
            ...$salesmanPermissions,
        ]));
        $salesman->save();

        $ownerPermissions = [
            'dashboard',
            'purchases.transaction.purchase-order',
            'purchases.transaction.purchase-order.approve',
            'purchases.transaction.purchase-order.delete',
            'purchases.transaction.good-receive',
            'purchases.transaction.good-receive.receive',
            'purchases.transaction.good-receive.delete',
            'purchases.transaction.purchase-invoice',
            'purchases.transaction.purchase-invoice.post',
            'purchases.transaction.purchase-invoice.delete',
            'purchases.report.unfinished-purchase-order',
            'purchases.report.unfinished-purchase-invoice',
            'inventory.transaction.transfer-stock',
            'inventory.transaction.transfer-stock.approve',
            'inventory.transaction.transfer-stock.delete',
            'inventory.transaction.adjustment-in',
            'inventory.transaction.adjustment-in.approve',
            'inventory.transaction.adjustment-in.delete',
            'inventory.transaction.adjustment-out',
            'inventory.transaction.adjustment-out.approve',
            'inventory.transaction.adjustment-out.delete',
            'sales.transaction.salesCanvas',
            'sales.transaction.salesCanvas.confirm',
            'sales.transaction.salesCanvas.convert',
            'sales.transaction.salesCanvas.delete',
            'sales.transaction.salesPreOrder',
            'sales.transaction.salesPreOrder.confirm',
            'sales.transaction.salesPreOrder.convert',
            'sales.transaction.salesPreOrder.delete',
            'sales.transaction.salesOrder',
            'sales.transaction.salesOrder.verify',
            'sales.transaction.delivery-order',
            'sales.transaction.sales-invoice',
            'sales.transaction.sales-invoice.confirm',
            'sales.report.po-outstanding',
            'sales.report.invoice-outstanding',
            'finance.transaction.ar-payment',
        ];

        $owner = Role::withTrashed()->firstOrCreate(
            ['name' => 'Owner'],
            ['permissions' => $ownerPermissions]
        );
        $owner->restore();
        $owner->permissions = array_values(array_unique([
            ...($owner->permissions ?? []),
            ...$ownerPermissions,
        ]));
        $owner->save();

        User::whereNull('role_id')->update([
            'role_id' => $superAdmin->id,
        ]);
    }
}
