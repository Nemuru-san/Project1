<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerPic> */
class CustomerPicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'notes' => null,
            'is_primary' => false,
        ];
    }
}
