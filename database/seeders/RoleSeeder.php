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

        User::whereNull('role_id')->update([
            'role_id' => $superAdmin->id,
        ]);
    }
}
