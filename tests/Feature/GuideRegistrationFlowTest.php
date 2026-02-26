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

    $documentFile = UploadedFile::fake()->image('id_card.jpg');
    $activityImages = [
        UploadedFile::fake()->image('proof1.jpg')->size(100),
        UploadedFile::fake()->image('proof2.jpg')->size(100),
        UploadedFile::fake()->image('proof3.jpg')->size(100),
    ];

    $city = City::create(['name' => 'Marrakech', 'region' => 'Marrakech-Safi']);
    $language = Language::create(['name' => 'English', 'code' => 'en']);

    // 1. Register Guide
    $response = $this->postJson('/api/register', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'guide@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+212600000000',
        'whatsapp' => '+212600000000',
        'agrement_number' => 'AG-12345',
        'agrement_date' => '2020-01-01',
        'agrement_authority' => 'Ministry of Tourism',
        'bio' => 'Experienced guide in Marrakech.',
        'user_type' => 'guide',
        'cities' => [
            ['id' => $city->id, 'is_main' => true]
        ],
        'languages' => [
            ['id' => $language->id, 'is_principal' => true, 'proficiency_level' => 'fluent']
        ],
        'documents' => [
            [
                'type' => 'id_card',
                'file' => $documentFile,
            ]
        ],
        'activity_images' => $activityImages,
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('message', 'Account created successfully. An admin will review your information and get back to you once the account is activated.')
             ->assertJsonMissing(['access_token']);

    $user = User::where('email', 'guide@example.com')->first();
    expect($user->status)->toBe('inactive');
    expect($user->phone)->toBe('+212600000000');

    $guide = Guide::where('user_id', $user->id)->first();
    expect($guide->whatsapp)->toBe('+212600000000');
    expect($guide->agrement_number)->toBe('AG-12345');
    
    expect($guide->cities()->count())->toBe(1);
    expect($guide->languages()->count())->toBe(1);
    expect(\App\Models\GuideDocument::where('guide_id', $guide->id)->where('document_type', 'activity_proof')->count())->toBe(3);

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
