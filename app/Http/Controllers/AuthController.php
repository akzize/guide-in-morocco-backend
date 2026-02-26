<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Guide;
use App\Models\Client;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required_if:user_type,guide|string|max:20',
            'user_type' => 'required|in:guide,client',
            
            // Guide Specific fields
            'whatsapp' => 'nullable|string|max:20',
            'agrement_number' => 'required_if:user_type,guide|string|max:255',
            'agrement_date' => 'required_if:user_type,guide|date',
            'agrement_authority' => 'required_if:user_type,guide|string|max:255',
            'bio' => 'nullable|string',
            
            'cities' => 'required_if:user_type,guide|array',
            'cities.*.id' => 'required_with:cities|exists:cities,id',
            'cities.*.is_main' => 'required_with:cities|boolean',
            
            'languages' => 'required_if:user_type,guide|array',
            'languages.*.id' => 'required_with:languages|exists:languages,id',
            'languages.*.is_principal' => 'required_with:languages|boolean',
            'languages.*.proficiency_level' => 'required_with:languages|in:basic,intermediate,fluent,native',
            
            'documents' => 'required_if:user_type,guide|array',
            'documents.*.type' => 'required_with:documents|in:id_card,passport,certificate,insurance,license',
            'documents.*.file' => 'required_with:documents|file|mimes:pdf,jpeg,png,jpg|max:5120',
            
            'activity_images' => 'required_if:user_type,guide|array|min:3|max:10',
            'activity_images.*' => 'required_with:activity_images|file|mimes:jpeg,png,jpg|max:5120',
        ]);

        $status = $request->user_type === 'guide' ? 'inactive' : 'active';

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'user_type' => $request->user_type,
            'status' => $status,
        ]);

        if ($user->user_type === 'guide') {
            $guide = Guide::create([
                'user_id' => $user->id,
                'whatsapp' => $request->whatsapp,
                'agrement_number' => $request->agrement_number,
                'agrement_date' => $request->agrement_date,
                'agrement_authority' => $request->agrement_authority,
                'bio' => $request->bio,
            ]);

            if ($request->has('cities')) {
                $syncCities = [];
                foreach ($request->cities as $city) {
                    $syncCities[$city['id']] = ['is_main' => $city['is_main']];
                }
                $guide->cities()->sync($syncCities);
            }

            if ($request->has('languages')) {
                $syncLanguages = [];
                foreach ($request->languages as $lang) {
                    $syncLanguages[$lang['id']] = [
                        'is_principal' => $lang['is_principal'],
                        'proficiency_level' => $lang['proficiency_level']
                    ];
                }
                $guide->languages()->sync($syncLanguages);
            }

            if ($request->has('documents')) {
                foreach ($request->documents as $doc) {
                    $path = $doc['file']->store('guide_documents', 'public');
                    \App\Models\GuideDocument::create([
                        'guide_id' => $guide->id,
                        'document_type' => $doc['type'],
                        'file_url' => '/storage/' . $path,
                        'file_size' => $doc['file']->getSize(),
                        'verification_status' => 'pending',
                    ]);
                }
            }

            if ($request->hasFile('activity_images')) {
                foreach ($request->file('activity_images') as $image) {
                    $path = $image->store('guide_documents', 'public');
                    \App\Models\GuideDocument::create([
                        'guide_id' => $guide->id,
                        'document_type' => 'activity_proof',
                        'file_url' => '/storage/' . $path,
                        'file_size' => $image->getSize(),
                        'verification_status' => 'pending',
                    ]);
                }
            }

            return response()->json([
                'user' => $user,
                'message' => 'Account created successfully. An admin will review your information and get back to you once the account is activated.',
            ], 201);
            
        } elseif ($user->user_type === 'client') {
            Client::create(['user_id' => $user->id]);
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\ClientRegistered($user));
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->status === 'inactive') {
            Auth::logout();
            $user->tokens()->delete();
            return response()->json([
                'message' => 'Your account is pending admin validation.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
