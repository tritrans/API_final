<?php

namespace Database\Seeders;

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
        // Create super admin user
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'receive_notifications' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // Create admin user
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'receive_notifications' => true,
        ]);
        $admin->assignRole('admin');

        // Create manager user
        $manager = User::firstOrCreate([
            'email' => 'manager@example.com',
        ], [
            'name' => 'Manager User',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'receive_notifications' => true,
        ]);
        $manager->assignRole('manager');

        // Create cashier user
        $cashier = User::firstOrCreate([
            'email' => 'cashier@example.com',
        ], [
            'name' => 'Cashier User',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'receive_notifications' => true,
        ]);
        $cashier->assignRole('cashier');

        // Create test user
        $user = User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'receive_notifications' => true,
        ]);
        $user->assignRole('user');
    }
}
