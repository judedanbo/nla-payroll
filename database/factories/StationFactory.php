<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Station>
 */
class StationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'name' => fake()->city().' NLA Office',
            'code' => strtoupper(fake()->unique()->lexify('STN-???')),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(4.5, 11.0), // Ghana latitude range
            'longitude' => fake()->longitude(-3.5, 1.5), // Ghana longitude range
            'gps_boundary' => null,
            'expected_headcount' => fake()->numberBetween(5, 50),
            'contact_person' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBoundary(): static
    {
        return $this->state(function (array $attributes) {
            // Create a small square boundary around the station
            $lat = $attributes['latitude'];
            $lng = $attributes['longitude'];
            $offset = 0.01; // ~1km

            return [
                'gps_boundary' => [
                    ['lat' => $lat + $offset, 'lng' => $lng - $offset],
                    ['lat' => $lat + $offset, 'lng' => $lng + $offset],
                    ['lat' => $lat - $offset, 'lng' => $lng + $offset],
                    ['lat' => $lat - $offset, 'lng' => $lng - $offset],
                ],
            ];
        });
    }
}
