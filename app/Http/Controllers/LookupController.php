<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function index()
    {
        return response()->json([
            'cities' => \App\Models\City::all(),
            'languages' => \App\Models\Language::all(),
            'specialties' => \App\Models\Specialty::all(),
            'tour_types' => \App\Models\TourType::all(),
            'difficulty_levels' => \App\Models\DifficultyLevel::all(),
            'currencies' => \App\Models\Currency::all(),
        ]);
    }
}
