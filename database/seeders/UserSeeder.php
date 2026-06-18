<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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

        User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('1234567890'),
                'role_id' => $superAdmin->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
                'role_id' => $superAdmin->id,
            ]
        );
    }
}
