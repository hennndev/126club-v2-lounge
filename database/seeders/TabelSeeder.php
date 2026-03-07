<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Tabel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TabelSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $areas = Area::all();

        if ($areas->isEmpty()) {
            $this->command->warn('No areas found. Please seed areas first.');

            return;
        }

        $tables = [
            // VIP Section
            [
                'area_code' => 'VIP',
                'tables' => [
                    ['table_number' => 'VIP Platinum Suite', 'capacity' => 12, 'minimum_charge' => 15.0, 'status' => 'available'],
                    ['table_number' => 'VIP Gold Suite', 'capacity' => 10, 'minimum_charge' => 12.0, 'status' => 'available'],
                    ['table_number' => 'Executive Room A', 'capacity' => 8, 'minimum_charge' => 8.0, 'status' => 'available'],
                    ['table_number' => 'Executive Room B', 'capacity' => 8, 'minimum_charge' => 8.0, 'status' => 'available'],
                ],
            ],
            // Main Bar
            [
                'area_code' => 'BAR',
                'tables' => [
                    ['table_number' => 'Bar Counter 1', 'capacity' => 6, 'minimum_charge' => 3.0, 'status' => 'available'],
                    ['table_number' => 'Bar High Table 1', 'capacity' => 4, 'minimum_charge' => 2.5, 'status' => 'available'],
                    ['table_number' => 'Bar High Table 2', 'capacity' => 4, 'minimum_charge' => 2.5, 'status' => 'available'],
                ],
            ],
            // Dance Floor
            [
                'area_code' => 'DANCE',
                'tables' => [
                    ['table_number' => 'Dance Floor VIP 1', 'capacity' => 10, 'minimum_charge' => 7.0, 'status' => 'available'],
                    ['table_number' => 'Dance Floor VIP 2', 'capacity' => 10, 'minimum_charge' => 7.0, 'status' => 'available'],
                    ['table_number' => 'Dance Booth 1', 'capacity' => 4, 'minimum_charge' => 3.5, 'status' => 'available'],
                ],
            ],
            // Lounge Area
            [
                'area_code' => 'LOUNGE',
                'tables' => [
                    ['table_number' => 'Lounge Sofa 1', 'capacity' => 6, 'minimum_charge' => 4.0, 'status' => 'available'],
                    ['table_number' => 'Lounge Sofa 2', 'capacity' => 6, 'minimum_charge' => 4.0, 'status' => 'available'],
                    ['table_number' => 'Corner Booth', 'capacity' => 4, 'minimum_charge' => 3.0, 'status' => 'available'],
                    ['table_number' => 'Window Table', 'capacity' => 4, 'minimum_charge' => 3.0, 'status' => 'available'],
                ],
            ],
            // Outdoor Patio
            [
                'area_code' => 'OUTDOOR',
                'tables' => [
                    ['table_number' => 'Patio Table 1', 'capacity' => 6, 'minimum_charge' => 3.5, 'status' => 'available'],
                    ['table_number' => 'Patio Table 2', 'capacity' => 6, 'minimum_charge' => 3.5, 'status' => 'available'],
                    ['table_number' => 'Garden Gazebo', 'capacity' => 8, 'minimum_charge' => 5.5, 'status' => 'available'],
                ],
            ],
        ];

        foreach ($tables as $areaData) {
            $area = $areas->where('code', $areaData['area_code'])->first();

            if (! $area) {
                continue;
            }

            foreach ($areaData['tables'] as $tableData) {
                Tabel::create([
                    'area_id' => $area->id,
                    'table_number' => $tableData['table_number'],
                    'qr_code' => 'QR-'.strtoupper(Str::random(12)),
                    'capacity' => $tableData['capacity'],
                    'minimum_charge' => $tableData['minimum_charge'],
                    'status' => $tableData['status'],
                    'is_active' => true,
                    'notes' => null,
                ]);
            }
        }

        $this->command->info('Tables seeded successfully!');
    }
}
