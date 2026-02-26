<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'ar', 'name' => 'Arabic'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'es', 'name' => 'Spanish'],
            ['code' => 'de', 'name' => 'German'],
            ['code' => 'it', 'name' => 'Italian'],
            ['code' => 'pt', 'name' => 'Portuguese'],
            ['code' => 'ja', 'name' => 'Japanese'],
            ['code' => 'zh', 'name' => 'Chinese'],
            ['code' => 'ru', 'name' => 'Russian'],
            ['code' => 'nl', 'name' => 'Dutch'],
        ];

        foreach ($languages as $l) {
            Language::firstOrCreate(['code' => $l['code']], $l);
        }
    }
}
