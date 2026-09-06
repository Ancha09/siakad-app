<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Gunakan php artisan deploy:admin untuk membuat admin production dengan password pribadi.');
        }

        User::updateOrCreate(
            [
                'login' => 'admin'
            ],
            [
                'name' => 'Administrator',
                'login' => 'admin',
                'role' => 'admin',
                'email' => 'admin@sttmi.local',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
