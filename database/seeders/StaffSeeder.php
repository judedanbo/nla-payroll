<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankDetail;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\Staff;
use App\Models\Station;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating test staff data...');

        // Get all necessary data
        $departments = Department::all();
        $units = Unit::all();
        $jobTitles = JobTitle::all();
        $stations = Station::all();
        $banks = Bank::all();

        if ($departments->isEmpty() || $units->isEmpty() || $jobTitles->isEmpty() || $stations->isEmpty()) {
            $this->command->error('Please run DepartmentsAndUnitsSeeder, JobGradesAndTitlesSeeder, and RegionsAndStationsSeeder first!');

            return;
        }

        // Create 50 staff members with realistic distribution
        $staffCount = 50;

        for ($i = 0; $i < $staffCount; $i++) {
            // Get random related records
            $department = $departments->random();
            $unit = $units->where('department_id', $department->id)->random() ?? $units->random();
            $jobTitle = $jobTitles->random();
            $station = $stations->random();

            // Create staff member
            $staff = Staff::create([
                'department_id' => $department->id,
                'unit_id' => $unit->id,
                'job_title_id' => $jobTitle->id,
                'station_id' => $station->id,
                'staff_number' => 'STF-'.str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'full_name' => fake()->name(),
                'date_of_birth' => fake()->dateTimeBetween('-60 years', '-22 years'),
                'gender' => fake()->randomElement(['male', 'female']),
                'marital_status' => fake()->randomElement(['single', 'married', 'divorced']),
                'email' => fake()->optional(0.7)->safeEmail(),
                'phone_primary' => fake()->numerify('+233#########'),
                'phone_secondary' => fake()->optional(0.3)->numerify('+233#########'),
                'address' => fake()->streetAddress(),
                'city' => fake()->city(),
                'region' => $station->region->name,
                'emergency_contact_name' => fake()->name(),
                'emergency_contact_phone' => fake()->numerify('+233#########'),
                'date_of_hire' => fake()->dateTimeBetween('-15 years', '-1 year'),
                'employment_status' => fake()->randomElement(['active', 'active', 'active', 'active', 'on_leave']),
                'employment_type' => fake()->randomElement(['permanent', 'permanent', 'permanent', 'contract']),
                'current_salary' => $jobTitle->jobGrade->min_salary + (($jobTitle->jobGrade->max_salary - $jobTitle->jobGrade->min_salary) / 2),
                'is_verified' => fake()->boolean(40),
                'last_verified_at' => fake()->boolean(40) ? fake()->dateTimeBetween('-6 months', 'now') : null,
                'is_ghost' => fake()->boolean(5), // 5% chance of ghost employee
                'ghost_reason' => fake()->boolean(5) ? 'Not present during physical verification' : null,
                'is_active' => true,
            ]);

            // Create bank details for active staff (1-2 accounts per staff)
            if ($staff->employment_status === 'active') {
                $accountCount = fake()->numberBetween(1, 2);

                for ($j = 0; $j < $accountCount; $j++) {
                    BankDetail::create([
                        'staff_id' => $staff->id,
                        'bank_id' => $banks->random()->id,
                        'account_number' => fake()->numerify('##########'),
                        'account_name' => $staff->full_name,
                        'account_type' => fake()->randomElement(['savings', 'current']),
                        'is_primary' => $j === 0, // First account is primary
                        'is_active' => true,
                        'activated_at' => $staff->date_of_hire,
                        'notes' => $j === 0 ? 'Primary salary account' : 'Secondary account',
                    ]);
                }
            }

            if (($i + 1) % 10 === 0) {
                $this->command->info('Created '.($i + 1).' staff members...');
            }
        }

        $this->command->info('Created '.$staffCount.' staff members with bank details');
        $this->command->info('Verified: '.Staff::where('is_verified', true)->count().' staff');
        $this->command->info('Ghost employees: '.Staff::where('is_ghost', true)->count().' staff');
        $this->command->info('Bank accounts: '.BankDetail::count().' accounts');
    }
}
