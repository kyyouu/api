<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun Owner
        User::create([
            'name'     => 'Owner',
            'email'    => 'owner@mom.com',
            'password' => Hash::make('password123'),
            'role'     => 'owner',
        ]);

        // Akun Admin
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@mom.com',
            'password' => Hash::make('password123'),
            'role'     => 'admin',
        ]);

        $this->command->info('✅ User seeder berhasil! Login dengan:');
        $this->command->info('   Owner → owner@mom.com / password123');
        $this->command->info('   Admin → admin@mom.com / password123');
    }
}