<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Unit;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test unit if not exists
        $unit = Unit::firstOrCreate(
            ['kode_unit' => 'TEST-01'],
            [
                'nama_unit' => 'Unit Testing',
                'status' => 'AKTIF',
            ]
        );

        // Create ADMIN user
        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin Testing',
                'nip' => '199001012020011001',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'unit_id' => $unit->id,
                'status' => 'AKTIF',
            ]
        );

        // Create ATASAN user
        User::firstOrCreate(
            ['email' => 'atasan@test.com'],
            [
                'name' => 'Atasan Testing',
                'nip' => '199002022020022001',
                'password' => Hash::make('password'),
                'role' => 'ATASAN',
                'unit_id' => $unit->id,
                'status' => 'AKTIF',
            ]
        );

        // Create ASN user
        User::firstOrCreate(
            ['email' => 'asn@test.com'],
            [
                'name' => 'ASN Testing',
                'nip' => '199003032020033001',
                'password' => Hash::make('password'),
                'role' => 'ASN',
                'unit_id' => $unit->id,
                'status' => 'AKTIF',
            ]
        );

        $this->command->info('✅ Test users created successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('-------------------');
        $this->command->info('ADMIN:  admin@test.com  / password');
        $this->command->info('ATASAN: atasan@test.com / password');
        $this->command->info('ASN:    asn@test.com    / password');
    }
}
