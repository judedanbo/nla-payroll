<?php

namespace Database\Seeders;

use App\Models\JobGrade;
use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class JobGradesAndTitlesSeeder extends Seeder
{
    public function run(): void
    {
        // Create Job Grades with salary ranges (in GHS)
        $grades = [
            [
                'name' => 'Executive',

                'description' => 'Executive management level',
                'min_salary' => 15000,
                'max_salary' => 30000,
                'titles' => [
                    ['name' => 'Director General', 'description' => 'Chief Executive Officer'],
                    ['name' => 'Deputy Director General', 'description' => 'Deputy CEO'],
                    ['name' => 'Executive Director', 'description' => 'Executive level director'],
                ],
            ],
            [
                'name' => 'Senior Management',

                'description' => 'Senior management positions',
                'min_salary' => 10000,
                'max_salary' => 20000,
                'titles' => [
                    ['name' => 'Director of Operations', 'description' => 'Operations director'],
                    ['name' => 'Director of Finance', 'description' => 'Finance director'],
                    ['name' => 'Director of IT', 'description' => 'IT director'],
                    ['name' => 'Director of HR', 'description' => 'HR director'],
                    ['name' => 'Regional Manager', 'description' => 'Regional operations manager'],
                ],
            ],
            [
                'name' => 'Management',

                'description' => 'Middle management positions',
                'min_salary' => 6000,
                'max_salary' => 12000,
                'titles' => [
                    ['name' => 'Department Manager', 'description' => 'Department head'],
                    ['name' => 'Unit Manager', 'description' => 'Unit head'],
                    ['name' => 'Project Manager', 'description' => 'Project management'],
                    ['name' => 'Branch Manager', 'description' => 'Branch operations manager'],
                    ['name' => 'IT Manager', 'description' => 'IT team manager'],
                ],
            ],
            [
                'name' => 'Senior Officer',

                'description' => 'Senior professional positions',
                'min_salary' => 4000,
                'max_salary' => 8000,
                'titles' => [
                    ['name' => 'Senior Accountant', 'description' => 'Senior accounting role'],
                    ['name' => 'Senior Auditor', 'description' => 'Senior audit role'],
                    ['name' => 'Senior IT Specialist', 'description' => 'Senior IT professional'],
                    ['name' => 'Senior Operations Officer', 'description' => 'Senior operations role'],
                    ['name' => 'Senior HR Officer', 'description' => 'Senior HR professional'],
                ],
            ],
            [
                'name' => 'Officer',

                'description' => 'Professional officer positions',
                'min_salary' => 2500,
                'max_salary' => 5000,
                'titles' => [
                    ['name' => 'Accountant', 'description' => 'Accounting professional'],
                    ['name' => 'Auditor', 'description' => 'Internal auditor'],
                    ['name' => 'IT Officer', 'description' => 'IT professional'],
                    ['name' => 'Operations Officer', 'description' => 'Operations role'],
                    ['name' => 'HR Officer', 'description' => 'HR professional'],
                    ['name' => 'Marketing Officer', 'description' => 'Marketing professional'],
                    ['name' => 'Compliance Officer', 'description' => 'Compliance professional'],
                ],
            ],
            [
                'name' => 'Assistant Officer',

                'description' => 'Assistant professional positions',
                'min_salary' => 1800,
                'max_salary' => 3500,
                'titles' => [
                    ['name' => 'Assistant Accountant', 'description' => 'Accounting assistant'],
                    ['name' => 'IT Support Officer', 'description' => 'IT support'],
                    ['name' => 'Administrative Officer', 'description' => 'Administrative support'],
                    ['name' => 'Customer Service Officer', 'description' => 'Customer service'],
                    ['name' => 'Data Entry Officer', 'description' => 'Data entry'],
                ],
            ],
            [
                'name' => 'Support Staff',

                'description' => 'Support and clerical positions',
                'min_salary' => 1200,
                'max_salary' => 2500,
                'titles' => [
                    ['name' => 'Secretary', 'description' => 'Administrative secretary'],
                    ['name' => 'Receptionist', 'description' => 'Front desk reception'],
                    ['name' => 'Office Assistant', 'description' => 'Office support'],
                    ['name' => 'Driver', 'description' => 'Company driver'],
                    ['name' => 'Cleaner', 'description' => 'Facility cleaning'],
                    ['name' => 'Security Guard', 'description' => 'Security services'],
                    ['name' => 'Messenger', 'description' => 'Courier and delivery'],
                ],
            ],
        ];

        foreach ($grades as $gradeData) {
            $titles = $gradeData['titles'] ?? [];
            unset($gradeData['titles']);

            $grade = JobGrade::create($gradeData);

            foreach ($titles as $titleData) {
                $grade->jobTitles()->create($titleData);
            }
        }

        $this->command->info('Created '.JobGrade::count().' job grades and '.JobTitle::count().' job titles');
    }
}
