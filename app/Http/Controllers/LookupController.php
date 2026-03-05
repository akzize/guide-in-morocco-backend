<?php

namespace App\Http\Controllers;

class LookupController extends Controller
{
    public function index()
    {
        return response()->json([
            'cities' => \App\Models\City::all(),
            'languages' => \App\Models\Language::all(),
            'guide_types' => [
                [
                    'value' => 'city_circuits',
                    'label' => 'Guide des villes et circuits touristiques',
                ],
                [
                    'value' => 'natural_spaces',
                    'label' => 'Guide des espaces naturels',
                ],
            ],
            'specialties' => \App\Models\Specialty::all(),
            'tour_types' => \App\Models\TourType::all(),
            'difficulty_levels' => \App\Models\DifficultyLevel::all(),
            'currencies' => \App\Models\Currency::all(),
        ]);
    }
}
