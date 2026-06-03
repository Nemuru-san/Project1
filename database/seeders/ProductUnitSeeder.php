<?php

namespace Database\Seeders;

use App\Models\ProductUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductUnit::updateOrCreate(
            ['id' => 1],
            [
                'code' => 'PCS',
                'name' => 'Eceran',
            ]
        );
    }
}
