<?php

use App\Models\User;
use App\Models\Guide;
use App\Models\City;
use App\Models\Language;
use App\Mail\GuideAccountActivated;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guide registration to activation flow', function () {
    Storage::fake('public');
    Mail::fake();

    $profilePhoto = UploadedFile::fake()->image('profile.jpg')->size(200);
    $professionalCard = UploadedFile::fake()->create('professional_card.pdf', 200, 'application/pdf');
    $identityDocument = UploadedFile::fake()->image('id_card.jpg')->size(200);
    $activityPhoto1 = UploadedFile::fake()->image('visit_1.jpg')->size(250);
    $activityPhoto2 = UploadedFile::fake()->image('visit_2.jpg')->size(250);

    $mainCity = City::create(['name' => 'Marrakech', 'region' => 'Marrakech-Safi']);
    $exerciseCity = City::create(['name' => 'Fes', 'region' => 'Fes-Meknes']);

    $principalLanguage = Language::create(['name' => 'French', 'code' => 'fr']);
    $spokenLanguage = Language::create(['name' => 'English', 'code' => 'en']);

    // 1. Register Guide
    $response = $this->postJson('/api/register', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'guide@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+212600000000',
        'whatsapp' => '+212600000000',
        'guide_type' => 'city_circuits',
        'agrement_number' => 'AG-12345',
        'agrement_date' => '2020-01-01',
        'agrement_authority' => 'Ministry of Tourism',
        'bio' => 'Guide certifié basé à Marrakech, passionné de patrimoine marocain.',
        'professional_experience' => '10 years guiding city tours and certified cultural heritage training.',
        'hourly_rate_from' => 250,
        'daily_rate' => 1600,
        'user_type' => 'guide',
        'main_city_id' => $mainCity->id,
        'operation_city_ids' => [
            $mainCity->id,
            $exerciseCity->id,
        ],
        'principal_language_id' => $principalLanguage->id,
        'spoken_language_ids' => [
            $principalLanguage->id,
            $spokenLanguage->id,
        ],
        'language_levels' => [
            $principalLanguage->id => 'native',
            $spokenLanguage->id => 'fluent',
        ],
        'profile_photo' => $profilePhoto,
        'professional_card' => $professionalCard,
        'identity_document_type' => 'id_card',
        'identity_document' => $identityDocument,
        'activity_images' => [$activityPhoto1, $activityPhoto2],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Account created successfully. An admin will review your information and get back to you once the account is activated.')
        ->assertJsonMissing(['access_token']);

    $user = User::where('email', 'guide@example.com')->first();
    expect($user->status)->toBe('inactive');
    expect($user->phone)->toBe('+212600000000');
    expect($user->profile_image_url)->not->toBeNull();

    $guide = Guide::where('user_id', $user->id)->first();
    expect($guide->whatsapp)->toBe('+212600000000');
    expect($guide->guide_type)->toBe('city_circuits');
    expect($guide->agrement_number)->toBe('AG-12345');
    expect($guide->bio)->toContain('Guide certifié basé à Marrakech');
    expect($guide->professional_experience)->toContain('10 years guiding city tours');
    expect((float) $guide->hourly_rate_from)->toBe(250.0);
    expect((float) $guide->daily_rate)->toBe(1600.0);

    expect($guide->cities()->count())->toBe(2);
    expect((bool) $guide->cities()->where('city_id', $mainCity->id)->first()->pivot->is_main)->toBeTrue();
    expect($guide->languages()->count())->toBe(2);
    expect((bool) $guide->languages()->where('language_id', $principalLanguage->id)->first()->pivot->is_principal)->toBeTrue();
    expect(\App\Models\GuideDocument::where('guide_id', $guide->id)->where('document_type', 'license')->count())->toBe(1);
    expect(\App\Models\GuideDocument::where('guide_id', $guide->id)->where('document_type', 'id_card')->count())->toBe(1);
    expect(\App\Models\GuideDocument::where('guide_id', $guide->id)->where('document_type', 'activity_proof')->count())->toBe(2);

    // 2. Try Login - should fail
    $loginResponse = $this->postJson('/api/login', [
        'email' => 'guide@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(403)
        ->assertJsonPath('message', 'Your account is pending admin validation.');

    // 3. Admin activates the guide
    $admin = User::create([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
        'user_type' => 'admin',
        'status' => 'active'
    ]);

    $guide = Guide::where('user_id', $user->id)->first();

    $token = $admin->createToken('admin')->plainTextToken;
    $activationResponse = $this->postJson("/api/admin/guides/{$guide->id}/activate", [], [
        'Authorization' => 'Bearer ' . $token
    ]);

    $activationResponse->assertStatus(200)
        ->assertJsonPath('message', 'Guide account activated successfully.');

    expect($guide->fresh()->certificate_status)->toBe('approved');
    expect($user->fresh()->status)->toBe('active');

    // Assert mail was sent
    Mail::assertSent(GuideAccountActivated::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });

    // 4. Try Login again - should succeed
    \Illuminate\Support\Facades\Auth::shouldUse('web');
    $loginResponse2 = $this->postJson('/api/login', [
        'email' => 'guide@example.com',
        'password' => 'password123',
    ]);

    $loginResponse2->assertStatus(200)
        ->assertJsonStructure(['user', 'access_token', 'token_type']);
});

test('guide registration accepts missing optional pricing fields', function () {
    Storage::fake('public');

    $profilePhoto = UploadedFile::fake()->image('profile.jpg')->size(200);
    $professionalCard = UploadedFile::fake()->create('professional_card.pdf', 200, 'application/pdf');
    $identityDocument = UploadedFile::fake()->image('id_card.jpg')->size(200);

    $mainCity = City::create(['name' => 'Rabat', 'region' => 'Rabat-Sale-Kenitra']);
    $principalLanguage = Language::create(['name' => 'Arabic', 'code' => 'ar']);

    $response = $this->postJson('/api/register', [
        'first_name' => 'Sara',
        'last_name' => 'El Idrissi',
        'email' => 'guide-rates-optional@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+212611111111',
        'guide_type' => 'natural_spaces',
        'agrement_number' => 'AG-77777',
        'agrement_date' => '2021-05-10',
        'agrement_authority' => 'Delegation Tourisme',
        'professional_experience' => 'Mountain guide and eco-tour coordinator.',
        'user_type' => 'guide',
        'main_city_id' => $mainCity->id,
        'operation_city_ids' => [$mainCity->id],
        'principal_language_id' => $principalLanguage->id,
        'spoken_language_ids' => [$principalLanguage->id],
        'profile_photo' => $profilePhoto,
        'professional_card' => $professionalCard,
        'identity_document_type' => 'passport',
        'identity_document' => $identityDocument,
    ]);

    $response->assertStatus(201);

    $user = User::where('email', 'guide-rates-optional@example.com')->first();
    $guide = Guide::where('user_id', $user->id)->first();

    expect($guide->hourly_rate_from)->toBeNull();
    expect($guide->daily_rate)->toBeNull();
});

test('admin can decline a guide account and login remains forbidden', function () {
    Storage::fake('public');

    $profilePhoto = UploadedFile::fake()->image('profile.jpg')->size(200);
    $professionalCard = UploadedFile::fake()->create('professional_card.pdf', 200, 'application/pdf');
    $identityDocument = UploadedFile::fake()->image('id_card.jpg')->size(200);

    $mainCity = City::create(['name' => 'Agadir', 'region' => 'Souss-Massa']);
    $principalLanguage = Language::create(['name' => 'Spanish', 'code' => 'es']);

    $registerResponse = $this->postJson('/api/register', [
        'first_name' => 'Youssef',
        'last_name' => 'Bennani',
        'email' => 'declined-guide@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+212622222222',
        'guide_type' => 'city_circuits',
        'agrement_number' => 'AG-88888',
        'agrement_date' => '2019-02-02',
        'agrement_authority' => 'Ministry of Tourism',
        'professional_experience' => 'Guide local spécialisé en circuits patrimoniaux.',
        'user_type' => 'guide',
        'main_city_id' => $mainCity->id,
        'operation_city_ids' => [$mainCity->id],
        'principal_language_id' => $principalLanguage->id,
        'spoken_language_ids' => [$principalLanguage->id],
        'profile_photo' => $profilePhoto,
        'professional_card' => $professionalCard,
        'identity_document_type' => 'id_card',
        'identity_document' => $identityDocument,
    ]);

    $registerResponse->assertStatus(201);

    $guideUser = User::where('email', 'declined-guide@example.com')->first();
    $guide = Guide::where('user_id', $guideUser->id)->first();

    $admin = User::create([
        'first_name' => 'Admin',
        'last_name' => 'Reviewer',
        'email' => 'admin-review@example.com',
        'password' => bcrypt('password123'),
        'user_type' => 'admin',
        'status' => 'active',
    ]);

    $adminToken = $admin->createToken('admin-review')->plainTextToken;
    $declineResponse = $this->postJson("/api/admin/guides/{$guide->id}/decline", [
        'reason' => 'Documents are incomplete and require correction.',
    ], [
        'Authorization' => 'Bearer ' . $adminToken,
    ]);

    $declineResponse->assertStatus(200)
        ->assertJsonPath('message', 'Guide account declined successfully.');

    expect($guide->fresh()->certificate_status)->toBe('rejected');
    expect($guide->fresh()->verification_notes)->toBe('Documents are incomplete and require correction.');
    expect($guideUser->fresh()->status)->toBe('inactive');

    $loginResponse = $this->postJson('/api/login', [
        'email' => 'declined-guide@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(403)
        ->assertJsonPath('message', 'Your guide account was declined by admin.');
});
