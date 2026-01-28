<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // prevents duplicates
            [
                'username' => 'Admin',
                'password' => Hash::make('1234567890'), // 🔐 HASHED
                'role_id'  => 1,
            ]
        );
        $this->call(AdminUserSeeder::class);
    }
}
