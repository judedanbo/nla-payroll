<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class DepartmentsAndUnitsSeeder extends Seeder
{
    public function run(): void
    {
        // Top-level Departments
        $departments = [
            [
                'name' => 'Administration',
                'description' => 'Administrative and executive management',
                'units' => [
                    ['name' => 'Executive Office',  'description' => 'Director General and executive team'],
                    ['name' => 'Human Resources', 'description' => 'HR management and payroll'],
                    ['name' => 'Legal Services',  'description' => 'Legal and compliance'],
                ],
            ],
            [
                'name' => 'Finance & Accounts',

                'description' => 'Financial management and accounting',
                'units' => [
                    ['name' => 'Accounts Payable',  'description' => 'Payment processing'],
                    ['name' => 'Accounts Receivable',  'description' => 'Revenue collection'],
                    ['name' => 'Payroll', 'description' => 'Staff payroll processing'],
                    ['name' => 'Audit', 'description' => 'Internal audit'],
                ],
            ],
            [
                'name' => 'Operations',

                'description' => 'Lottery operations and game management',
                'units' => [
                    ['name' => 'Game Operations', 'description' => 'Lottery game management'],
                    ['name' => 'Draw Management', 'description' => 'Draw coordination'],
                    ['name' => 'Retailer Management', 'description' => 'Retailer relations'],
                    ['name' => 'Regional Operations', 'description' => 'Regional coordination'],
                ],
            ],
            [
                'name' => 'Information Technology',

                'description' => 'IT systems and infrastructure',
                'units' => [
                    ['name' => 'Software Development', 'description' => 'Application development'],
                    ['name' => 'Network & Infrastructure', 'description' => 'Network management'],
                    ['name' => 'IT Support', 'description' => 'Technical support'],
                    ['name' => 'Security',  'description' => 'IT security'],
                ],
            ],
            [
                'name' => 'Marketing & Communications',
                'description' => 'Marketing, PR, and customer engagement',
                'units' => [
                    ['name' => 'Brand Management', 'description' => 'Brand strategy'],
                    ['name' => 'Public Relations', 'description' => 'Media and PR'],
                    ['name' => 'Customer Service', 'description' => 'Customer support'],
                ],
            ],
            [
                'name' => 'Compliance & Risk',

                'description' => 'Regulatory compliance and risk management',
                'units' => [
                    ['name' => 'Regulatory Affairs', 'description' => 'Regulatory compliance'],
                    ['name' => 'Risk Management', 'description' => 'Risk assessment'],
                    ['name' => 'Anti-Money Laundering', 'description' => 'AML compliance'],
                ],
            ],
            [
                'name' => 'Security Services',

                'description' => 'Physical security and facility management',
                'units' => [
                    ['name' => 'Physical Security',  'description' => 'Security operations'],
                    ['name' => 'Facility Management',  'description' => 'Facility operations'],
                ],
            ],
        ];

        foreach ($departments as $deptData) {
            $units = $deptData['units'] ?? [];
            unset($deptData['units']);

            $department = Department::create($deptData);

            foreach ($units as $unitData) {
                $department->units()->create($unitData);
            }
        }

        $this->command->info('Created '.Department::count().' departments and '.Unit::count().' units');
    }
}
