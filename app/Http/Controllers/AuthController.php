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
            'user_type' => 'required|in:guide,client',
            'documents' => 'required_if:user_type,guide|array',
            'documents.*.type' => 'required_with:documents|in:id_card,passport,certificate,insurance,license',
            'documents.*.file' => 'required_with:documents|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ]);

        $status = $request->user_type === 'guide' ? 'inactive' : 'active';

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'status' => $status,
        ]);

        if ($user->user_type === 'guide') {
            $guide = Guide::create(['user_id' => $user->id]);

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
