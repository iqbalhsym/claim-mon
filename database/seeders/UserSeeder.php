<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lokal Super Admin
        \App\Models\User::updateOrCreate(
            ['username' => 'adminarya'],
            [
                'name' => 'Admin Arya',
                'email' => 'adminarya@gmail.com',
                'role' => 'administrator',
                'password' => bcrypt('admin123'),
            ]
        );

        // Administrator LDAP
        \App\Models\User::updateOrCreate(
            ['username' => 'mohammad.hud'],
            [
                'name' => 'Mohammad Hud',
                'role' => 'administrator',
            ]
        );
    }
}
