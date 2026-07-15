<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'CUST-'.fake()->unique()->numerify('#####'),
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'tax_number' => null,
            'notes' => null,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
