<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Station;
use Illuminate\Database\Seeder;

class RegionsAndStationsSeeder extends Seeder
{
    public function run(): void
    {
        // Ghana's 16 regions with NLA stations
        $regionsWithStations = [
            [
                'name' => 'Greater Accra Region',
                'description' => 'National capital region',
                'stations' => [
                    ['name' => 'NLA Head Office - Accra'],
                    ['name' => 'Tema NLA Office'],
                    ['name' => 'Madina NLA Office'],
                    ['name' => 'Dansoman NLA Office'],
                ],
            ],
            [
                'name' => 'Ashanti Region',
                'description' => 'Garden city region',
                'stations' => [
                    ['name' => 'Kumasi NLA Office'],
                    ['name' => 'Obuasi NLA Office'],
                    ['name' => 'Ejisu NLA Office'],
                ],
            ],
            [
                'name' => 'Western Region',
                'description' => 'Western region',
                'stations' => [
                    ['name' => 'Sekondi-Takoradi NLA Office'],
                    ['name' => 'Tarkwa NLA Office'],
                ],
            ],
            [
                'name' => 'Western North Region',
                'description' => 'Western north region',
                'stations' => [
                    ['name' => 'Sefwi Wiawso NLA Office'],
                ],
            ],
            [
                'name' => 'Central Region',
                'description' => 'Central region',
                'stations' => [
                    ['name' => 'Cape Coast NLA Office'],
                    ['name' => 'Winneba NLA Office'],
                ],
            ],
            [
                'name' => 'Eastern Region',
                'description' => 'Eastern region',
                'stations' => [
                    ['name' => 'Koforidua NLA Office'],
                    ['name' => 'Akim Oda NLA Office'],
                ],
            ],
            [
                'name' => 'Volta Region',
                'description' => 'Volta region',
                'stations' => [
                    ['name' => 'Ho NLA Office'],
                ],
            ],
            [
                'name' => 'Oti Region',
                'description' => 'Oti region',
                'stations' => [
                    ['name' => 'Dambai NLA Office'],
                ],
            ],
            [
                'name' => 'Northern Region',
                'description' => 'Northern region',
                'stations' => [
                    ['name' => 'Tamale NLA Office'],
                    ['name' => 'Yendi NLA Office'],
                ],
            ],
            [
                'name' => 'Savannah Region',
                'description' => 'Savannah region',
                'stations' => [
                    ['name' => 'Damongo NLA Office'],
                ],
            ],
            [
                'name' => 'North East Region',
                'description' => 'North east region',
                'stations' => [
                    ['name' => 'Nalerigu NLA Office'],
                ],
            ],
            [
                'name' => 'Upper East Region',
                'description' => 'Upper east region',
                'stations' => [
                    ['name' => 'Bolgatanga NLA Office'],
                ],
            ],
            [
                'name' => 'Upper West Region',
                'description' => 'Upper west region',
                'stations' => [
                    ['name' => 'Wa NLA Office'],
                ],
            ],
            [
                'name' => 'Bono Region',
                'description' => 'Bono region',
                'stations' => [
                    ['name' => 'Sunyani NLA Office'],
                ],
            ],
            [
                'name' => 'Bono East Region',
                'description' => 'Bono east region',
                'stations' => [
                    ['name' => 'Techiman NLA Office'],
                ],
            ],
            [
                'name' => 'Ahafo Region',
                'description' => 'Ahafo region',
                'stations' => [
                    ['name' => 'Goaso NLA Office'],
                ],
            ],
        ];

        foreach ($regionsWithStations as $regionData) {
            $stations = $regionData['stations'] ?? [];
            unset($regionData['stations']);

            $region = Region::create($regionData);

            foreach ($stations as $index => $stationData) {
                $region->stations()->create([
                    'name' => $stationData['name'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Created '.Region::count().' regions and '.Station::count().' stations');
    }
}
