<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BanksSeeder extends Seeder
{
    public function run(): void
    {
        // Major banks in Ghana
        $banks = [
            ['name' => 'GCB Bank Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Ecobank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Standard Chartered Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Absa Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Zenith Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Stanbic Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Consolidated Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Fidelity Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'United Bank for Africa Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Access Bank Ghana Plc', 'branch_name' => 'Head Office'],
            ['name' => 'Guaranty Trust Bank (Ghana) Limited', 'branch_name' => 'Head Office'],
            ['name' => 'CalBank Plc', 'branch_name' => 'Head Office'],
            ['name' => 'First National Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Société Générale Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Agricultural Development Bank Limited', 'branch_name' => 'Head Office'],
            ['name' => 'National Investment Bank Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Prudential Bank Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Republic Bank Ghana Limited', 'branch_name' => 'Head Office'],
            ['name' => 'Universal Merchant Bank Limited', 'branch_name' => 'Head Office'],
            ['name' => 'OmniBSIC Bank Ghana Limited', 'branch_name' => 'Head Office'],
        ];

        foreach ($banks as $bank) {
            Bank::create([
                'name' => $bank['name'],
                'branch_name' => $bank['branch_name'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Created '.Bank::count().' banks');
    }
}
