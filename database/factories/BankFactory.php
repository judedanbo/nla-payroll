<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
    public function definition(): array
    {
        $ghanaianBanks = [
            ['name' => 'GCB Bank Limited', 'code' => 'GCB'],
            ['name' => 'Ecobank Ghana Limited', 'code' => 'ECO'],
            ['name' => 'Standard Chartered Bank Ghana Limited', 'code' => 'SCB'],
            ['name' => 'Absa Bank Ghana Limited', 'code' => 'ABG'],
            ['name' => 'Zenith Bank Ghana Limited', 'code' => 'ZBG'],
            ['name' => 'Stanbic Bank Ghana Limited', 'code' => 'SBG'],
            ['name' => 'Consolidated Bank Ghana Limited', 'code' => 'CBG'],
            ['name' => 'Fidelity Bank Ghana Limited', 'code' => 'FBG'],
            ['name' => 'United Bank for Africa Ghana Limited', 'code' => 'UBA'],
            ['name' => 'Access Bank Ghana Plc', 'code' => 'ABP'],
        ];

        $bank = fake()->randomElement($ghanaianBanks);

        return [
            'name' => $bank['name'],
            'code' => strtoupper(fake()->unique()->lexify($bank['code'].'-???')),
            'swift_code' => strtoupper(fake()->lexify('????GH??')),
            'branch_name' => fake()->optional()->city().' Branch',
            'branch_code' => fake()->optional()->numerify('###'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
