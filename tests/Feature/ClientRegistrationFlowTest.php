<?php

use App\Models\User;
use App\Models\Client;
use App\Mail\ClientRegistered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('client registration flow', function () {
    Mail::fake();

    // 1. Register Client
    $response = $this->postJson('/api/register', [
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'email' => 'client@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'user_type' => 'client',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['user', 'access_token', 'token_type']);

    $user = User::where('email', 'client@example.com')->first();
    expect($user->status)->toBe('active');
    
    $client = Client::where('user_id', $user->id)->first();
    expect($client)->not->toBeNull();

    // Assert mail was sent
    Mail::assertSent(ClientRegistered::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });

    // 2. Try Login - should succeed block-free
    \Illuminate\Support\Facades\Auth::forgetGuards();
    
    $loginResponse = $this->postJson('/api/login', [
        'email' => 'client@example.com',
        'password' => 'password123',
    ]);
    
    $loginResponse->assertStatus(200)
                  ->assertJsonStructure(['user', 'access_token', 'token_type']);
});
