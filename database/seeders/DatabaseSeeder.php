<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Lookup Data
        $currencies = [
            ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'د.م.'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
        ];
        foreach ($currencies as $c) \App\Models\Currency::firstOrCreate($c);

        $this->call([
            CitySeeder::class,
            LanguageSeeder::class,
        ]);

        $specialties = [
            ['name' => 'History'],
            ['name' => 'Culture'],
            ['name' => 'Gastronomy'],
            ['name' => 'Photography'],
            ['name' => 'Shopping'],
        ];
        foreach ($specialties as $s) \App\Models\Specialty::firstOrCreate($s);

        $tourTypes = [
            ['name' => 'Walking Tour', 'description' => 'Explore the city on foot.'],
            ['name' => 'Day Trip', 'description' => 'Excursion outside the city.'],
            ['name' => 'Food Tour', 'description' => 'Taste local cuisine.'],
        ];
        foreach ($tourTypes as $t) \App\Models\TourType::firstOrCreate($t);

        $difficulties = [
            ['level' => 'Easy', 'description' => 'Suitable for all fitness levels.'],
            ['level' => 'Medium', 'description' => 'Requires some walking and stairs.'],
            ['level' => 'Hard', 'description' => 'Physically demanding.'],
        ];
        foreach ($difficulties as $d) \App\Models\DifficultyLevel::firstOrCreate($d);

        // Core Users
        $adminOptions = ['first_name'=>'Admin','last_name'=>'User','email'=>'admin@example.com','password'=>bcrypt('password'),'user_type'=>'admin','status'=>'active'];
        $admin = \App\Models\User::firstOrCreate(['email' => 'admin@example.com'], $adminOptions);
        \App\Models\AdminUser::firstOrCreate(['user_id' => $admin->id], ['role' => 'super_admin']);

        $guideOptions = ['first_name'=>'Ahmed','last_name'=>'Benali','email'=>'guide@example.com','password'=>bcrypt('password'),'user_type'=>'guide','status'=>'active'];
        $guideUser = \App\Models\User::firstOrCreate(['email' => 'guide@example.com'], $guideOptions);
        $guide = \App\Models\Guide::firstOrCreate(['user_id' => $guideUser->id], [
            'location' => 'Marrakech',
            'bio' => 'Experienced guide in the old Medina.',
            'rating' => 4.8,
            'certificate_status' => 'approved',
            'years_experience' => 10,
            'hourly_rate_from' => 20.00,
        ]);

        $clientOptions = ['first_name'=>'John','last_name'=>'Doe','email'=>'client@example.com','password'=>bcrypt('password'),'user_type'=>'client','status'=>'active'];
        $clientUser = \App\Models\User::firstOrCreate(['email' => 'client@example.com'], $clientOptions);
        \App\Models\Client::firstOrCreate(['user_id' => $clientUser->id], [
            'nationality' => 'USA',
            'preferred_language' => 'en'
        ]);

        // Seed Tours if none exist
        if (\App\Models\Tour::count() == 0) {
            $marrakech = \App\Models\City::where('name', 'Marrakech')->first();
            $walking = \App\Models\TourType::where('name', 'Walking Tour')->first();
            $easy = \App\Models\DifficultyLevel::where('level', 'Easy')->first();
            $mad = \App\Models\Currency::where('code', 'MAD')->first();

            \App\Models\Tour::create([
                'guide_id' => $guide->id,
                'title' => 'Marrakech Medina Walking Tour',
                'description' => 'Discover the secrets of the vibrant Medina with an expert local guide.',
                'city_id' => $marrakech->id,
                'tour_type_id' => $walking->id,
                'difficulty_level_id' => $easy->id,
                'duration_in_hours' => 3.5,
                'duration_formatted' => '3h 30m',
                'price' => 250.00,
                'currency_id' => $mad->id,
                'max_persons' => 10,
                'min_persons' => 1,
                'status' => 'published',
            ]);
        }
    }
}
