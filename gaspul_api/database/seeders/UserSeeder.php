<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'nip' => '199001012020011001',
            'role' => 'ADMIN',
            'password' => Hash::make('password'),
        ]);

        // ASN User
        User::create([
            'name' => 'ASN User',
            'email' => 'asn@example.com',
            'nip' => '199002012020011002',
            'role' => 'ASN',
            'password' => Hash::make('password'),
        ]);

        // Atasan User
        User::create([
            'name' => 'Atasan User',
            'email' => 'atasan@example.com',
            'nip' => '199003012020011003',
            'role' => 'ATASAN',
            'password' => Hash::make('password'),
        ]);

        $this->command->info('Demo users created successfully!');
    }
}
