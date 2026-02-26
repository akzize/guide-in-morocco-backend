<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Marrakech', 'region' => 'Marrakech-Safi', 'latitude' => 31.6295, 'longitude' => -7.9811],
            ['name' => 'Casablanca', 'region' => 'Casablanca-Settat', 'latitude' => 33.5731, 'longitude' => -7.5898],
            ['name' => 'Rabat', 'region' => 'Rabat-Sale-Kenitra', 'latitude' => 34.0209, 'longitude' => -6.8416],
            ['name' => 'Fes', 'region' => 'Fes-Meknes', 'latitude' => 34.0331, 'longitude' => -5.0003],
            ['name' => 'Tangier', 'region' => 'Tanger-Tetouan-Al Hoceima', 'latitude' => 35.7595, 'longitude' => -5.8340],
            ['name' => 'Agadir', 'region' => 'Souss-Massa', 'latitude' => 30.4278, 'longitude' => -9.5981],
            ['name' => 'Meknes', 'region' => 'Fes-Meknes', 'latitude' => 33.8926, 'longitude' => -5.5532],
            ['name' => 'Oujda', 'region' => 'Oriental', 'latitude' => 34.6853, 'longitude' => -1.9114],
            ['name' => 'Kenitra', 'region' => 'Rabat-Sale-Kenitra', 'latitude' => 34.2541, 'longitude' => -6.5890],
            ['name' => 'Tetouan', 'region' => 'Tanger-Tetouan-Al Hoceima', 'latitude' => 35.5785, 'longitude' => -5.3684],
            ['name' => 'Essaouira', 'region' => 'Marrakech-Safi', 'latitude' => 31.5085, 'longitude' => -9.7595],
            ['name' => 'Chefchaouen', 'region' => 'Tanger-Tetouan-Al Hoceima', 'latitude' => 35.1714, 'longitude' => -5.2697],
            ['name' => 'Ouarzazate', 'region' => 'Draa-Tafilalet', 'latitude' => 30.9189, 'longitude' => -6.8934],
        ];

        foreach ($cities as $c) {
            City::firstOrCreate(['name' => $c['name']], $c);
        }
    }
}
