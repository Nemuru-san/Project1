<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $accounts = [
                ['code' => '1000', 'name' => 'Assets', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => null, 'is_postable' => false],
                ['code' => '1100', 'name' => 'Cash and Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => '1000', 'is_postable' => false],
                ['code' => '1110', 'name' => 'Cash', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => '1100', 'is_postable' => true],
                ['code' => '1120', 'name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => '1100', 'is_postable' => true],
                ['code' => '1200', 'name' => 'Inventory', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => '1000', 'is_postable' => true],
                ['code' => '1300', 'name' => 'Account Receivable', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => '1000', 'is_postable' => true],
                ['code' => '1400', 'name' => 'Tax In', 'type' => 'Asset', 'normal_balance' => 'Debit', 'parent_code' => '1000', 'is_postable' => true],

                ['code' => '2000', 'name' => 'Liabilities', 'type' => 'Liability', 'normal_balance' => 'Credit', 'parent_code' => null, 'is_postable' => false],
                ['code' => '2100', 'name' => 'Account Payable', 'type' => 'Liability', 'normal_balance' => 'Credit', 'parent_code' => '2000', 'is_postable' => true],
                ['code' => '2200', 'name' => 'Tax Out', 'type' => 'Liability', 'normal_balance' => 'Credit', 'parent_code' => '2000', 'is_postable' => true],

                ['code' => '3000', 'name' => 'Equity', 'type' => 'Equity', 'normal_balance' => 'Credit', 'parent_code' => null, 'is_postable' => false],
                ['code' => '3100', 'name' => 'Owner Capital', 'type' => 'Equity', 'normal_balance' => 'Credit', 'parent_code' => '3000', 'is_postable' => true],
                ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'Equity', 'normal_balance' => 'Credit', 'parent_code' => '3000', 'is_postable' => true],

                ['code' => '4000', 'name' => 'Revenue', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'parent_code' => null, 'is_postable' => false],
                ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'parent_code' => '4000', 'is_postable' => true],
                ['code' => '4200', 'name' => 'Sales Discount', 'type' => 'Revenue', 'normal_balance' => 'Debit', 'parent_code' => '4000', 'is_postable' => true],
                ['code' => '4300', 'name' => 'Sales Return', 'type' => 'Revenue', 'normal_balance' => 'Debit', 'parent_code' => '4000', 'is_postable' => true],

                ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'COGS', 'normal_balance' => 'Debit', 'parent_code' => null, 'is_postable' => false],
                ['code' => '5100', 'name' => 'COGS Product', 'type' => 'COGS', 'normal_balance' => 'Debit', 'parent_code' => '5000', 'is_postable' => true],

                ['code' => '6000', 'name' => 'Expenses', 'type' => 'Expense', 'normal_balance' => 'Debit', 'parent_code' => null, 'is_postable' => false],
                ['code' => '6100', 'name' => 'Purchase Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'parent_code' => '6000', 'is_postable' => true],
                ['code' => '6200', 'name' => 'Operational Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'parent_code' => '6000', 'is_postable' => true],
                ['code' => '6300', 'name' => 'Bank Administration Expense', 'type' => 'Expense', 'normal_balance' => 'Debit', 'parent_code' => '6000', 'is_postable' => true],
            ];

            foreach ($accounts as $account) {
                ChartOfAccount::updateOrCreate(
                    ['code' => $account['code']],
                    [
                        'name' => $account['name'],
                        'type' => $account['type'],
                        'normal_balance' => $account['normal_balance'],
                        'parent_id' => null,
                        'is_postable' => $account['is_postable'],
                        'is_active' => true,
                    ]
                );
            }

            foreach ($accounts as $account) {
                if (!$account['parent_code']) {
                    continue;
                }

                $parent = ChartOfAccount::where('code', $account['parent_code'])->first();
                $child = ChartOfAccount::where('code', $account['code'])->first();

                if ($parent && $child) {
                    $child->update([
                        'parent_id' => $parent->id,
                    ]);
                }
            }
        });
    }
}
