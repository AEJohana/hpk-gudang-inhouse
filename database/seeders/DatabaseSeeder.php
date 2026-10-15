<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
            MasterDataSeeder::class,
            ComponentSeeder::class,
            TransactionSeeder::class,
            SettingSeeder::class,
            MachineCenterSeeder::class,
        ]);

        if (file_exists(base_path('Data acuan/Form Work Request - Data komponen.csv'))) {
            $this->command?->info('Mengimpor 613 master komponen dari file acuan...');
            \Illuminate\Support\Facades\Artisan::call('import:csv-komponen');
        }
    }
}
