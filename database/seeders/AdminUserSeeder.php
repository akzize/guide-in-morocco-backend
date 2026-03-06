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
            ['email' => 'admin@guideinmorocco.com'], // avoid duplicates
            [
                'first_name' => 'Admin',
                'last_name' => 'GuideInMorocco',
                'password' => Hash::make('Admin@123456'),
                'phone' => null,
                'profile_image_url' => null,
                'user_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
