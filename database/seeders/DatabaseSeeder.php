<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@iams.local'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );

        // Sample assets
        $assets = [
            ['category' => 'laptop',    'name' => 'Laptop HP EliteBook 840 G9',         'brand' => 'HP',       'model' => 'EliteBook 840 G9',  'serial_number' => 'SN-LPT-001', 'location' => 'Ruang IT Lt.2'],
            ['category' => 'laptop',    'name' => 'Laptop Dell Latitude 5530',           'brand' => 'Dell',     'model' => 'Latitude 5530',     'serial_number' => 'SN-LPT-002', 'location' => 'Ruang IT Lt.2'],
            ['category' => 'laptop',    'name' => 'Laptop Lenovo ThinkPad X1 Carbon',    'brand' => 'Lenovo',   'model' => 'ThinkPad X1',       'serial_number' => 'SN-LPT-003', 'location' => 'Ruang IT Lt.2'],
            ['category' => 'projector', 'name' => 'Proyektor Epson EB-X51',              'brand' => 'Epson',    'model' => 'EB-X51',            'serial_number' => 'SN-PRJ-001', 'location' => 'Ruang Rapat A'],
            ['category' => 'projector', 'name' => 'Proyektor BenQ MH733',                'brand' => 'BenQ',     'model' => 'MH733',             'serial_number' => 'SN-PRJ-002', 'location' => 'Ruang Rapat B'],
            ['category' => 'camera',    'name' => 'Kamera Canon EOS M50 Mark II',        'brand' => 'Canon',    'model' => 'EOS M50 Mark II',   'serial_number' => 'SN-CAM-001', 'location' => 'Almari Studio'],
            ['category' => 'camera',    'name' => 'Kamera Sony ZV-E10',                  'brand' => 'Sony',     'model' => 'ZV-E10',            'serial_number' => 'SN-CAM-002', 'location' => 'Almari Studio'],
            ['category' => 'network',   'name' => 'Router TP-Link Archer AX55',          'brand' => 'TP-Link',  'model' => 'Archer AX55',       'serial_number' => 'SN-NET-001', 'location' => 'Gudang IT'],
            ['category' => 'network',   'name' => 'Switch Cisco SG350-10',               'brand' => 'Cisco',    'model' => 'SG350-10',          'serial_number' => 'SN-NET-002', 'location' => 'Gudang IT'],
            ['category' => 'network',   'name' => 'Access Point Ubiquiti UniFi U6 Lite', 'brand' => 'Ubiquiti', 'model' => 'UniFi U6 Lite',     'serial_number' => 'SN-NET-003', 'location' => 'Gudang IT'],
        ];

        foreach ($assets as $data) {
            if (!Asset::where('serial_number', $data['serial_number'])->exists()) {
                Asset::create([
                    'asset_code' => Asset::generateCode($data['category']),
                    'name'       => $data['name'],
                    'category'   => $data['category'],
                    'brand'      => $data['brand'],
                    'model'      => $data['model'],
                    'serial_number' => $data['serial_number'],
                    'condition'  => 'good',
                    'status'     => 'available',
                    'location'   => $data['location'],
                ]);
            }
        }

        $this->command->info('✅ Seeder berhasil! Login: admin@iams.local / password');
    }
}
