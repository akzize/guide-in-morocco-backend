<?php

use App\Models\User;
use App\Models\Guide;
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

    // 1. Register Guide
    $response = $this->postJson('/api/register', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'guide@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'user_type' => 'guide',
        'documents' => [
            [
                'type' => 'id_card',
                'file' => $documentFile,
            ]
        ]
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('message', 'Account created successfully. An admin will review your information and get back to you once the account is activated.')
             ->assertJsonMissing(['access_token']);

    $user = User::where('email', 'guide@example.com')->first();
    expect($user->status)->toBe('inactive');

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
