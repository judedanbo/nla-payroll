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
        $this->command->info('🌱 Seeding NLA Payroll Database...');
        $this->command->newLine();

        // Step 1: Create foundation organizational data
        $this->command->info('📋 Step 1: Creating organizational structure...');
        $this->call([
            DepartmentsAndUnitsSeeder::class,
            JobGradesAndTitlesSeeder::class,
        ]);
        $this->command->newLine();

        // Step 2: Create geographic data
        $this->command->info('🗺️  Step 2: Creating regions and stations...');
        $this->call(RegionsAndStationsSeeder::class);
        $this->command->newLine();

        // Step 3: Create banking data
        $this->command->info('🏦 Step 3: Creating banks...');
        $this->call(BanksSeeder::class);
        $this->command->newLine();

        // Step 4: Create staff test data
        $this->command->info('👥 Step 4: Creating staff and bank details...');
        $this->call(StaffSeeder::class);
        $this->command->newLine();

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();

        // Display summary
        $this->command->info('📊 Summary:');
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Departments', \App\Models\Department::count()],
                ['Units', \App\Models\Unit::count()],
                ['Job Grades', \App\Models\JobGrade::count()],
                ['Job Titles', \App\Models\JobTitle::count()],
                ['Regions', \App\Models\Region::count()],
                ['Stations', \App\Models\Station::count()],
                ['Banks', \App\Models\Bank::count()],
                ['Staff', \App\Models\Staff::count()],
                ['Bank Details', \App\Models\BankDetail::count()],
            ]
        );
    }
}
