<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\CustomerAddress> */
class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'code' => 'ADDR-'.fake()->unique()->numerify('###'),
            'label' => fake()->randomElement(['Kantor Pusat', 'Gudang', 'Cabang']),
            'address_type' => fake()->randomElement(['billing', 'shipping', 'both']),
            'address' => fake()->address(),
            'province' => fake()->state(),
            'city' => fake()->city(),
            'district' => null,
            'postal_code' => fake()->postcode(),
            'country' => 'Indonesia',
            'is_primary' => false,
        ];
    }
}
