<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@madnutz.com.br'],
            [
                'name'     => 'Admin MadNutz',
                'password' => Hash::make('madnutz@2026'),
                'is_admin' => true,
            ]
        );
    }
}
