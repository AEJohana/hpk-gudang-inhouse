<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['code' => '1', 'name' => 'Dept Laser Cutting', 'alias' => 'Laser Cutting'],
            ['code' => '2', 'name' => 'Dept M1', 'alias' => 'Machining M1'],
            ['code' => '3', 'name' => 'Dept UNP', 'alias' => 'Bending UNP'],
            ['code' => '4', 'name' => 'Dept Sharing', 'alias' => 'Sharing'],
            ['code' => '5', 'name' => 'Dept Band Saw', 'alias' => 'Band Saw Potong'],
            ['code' => '6', 'name' => 'Dept Aksesoris', 'alias' => 'Aksesoris Fabrikasi'],
            ['code' => '7', 'name' => 'Dept Press', 'alias' => 'Mesin Press Plat'],
            ['code' => '8', 'name' => 'Dept Hydraulic', 'alias' => 'Hidrolik & Tabung'],
            ['code' => '9', 'name' => 'Dept CNC', 'alias' => 'Milling & Bubut CNC'],
            ['code' => '10', 'name' => 'Dept Sharing & Bending', 'alias' => 'Sharing & Bending (SB)'],
            ['code' => '11', 'name' => 'Dept Gudang', 'alias' => 'Gudang & Logistik Inhouse'],
            ['code' => '12', 'name' => 'Dept SPO', 'alias' => 'SPO Karoseri'],
            ['code' => '13', 'name' => 'Dept Robot', 'alias' => 'Robot Welding'],
            ['code' => '14', 'name' => 'Dept Uji 1', 'alias' => 'Stasiun Uji 1'],
            ['code' => '15', 'name' => 'Dept Uji 2', 'alias' => 'Stasiun Uji 2'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'alias' => $dept['alias'],
                    'is_active' => true,
                ]
            );
        }
    }
}
