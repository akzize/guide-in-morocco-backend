<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\Guide;
use App\Models\Client;
use App\Models\GuideDocument;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $this->normalizeGuideRegistrationPayload($request);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required_if:user_type,guide|string|max:20',
            'user_type' => 'required|in:guide,client',

            'whatsapp' => 'nullable|string|max:20',
            'guide_type' => 'required_if:user_type,guide|in:city_circuits,natural_spaces',
            'agrement_number' => 'required_if:user_type,guide|string|max:255',
            'agrement_date' => 'required_if:user_type,guide|date',
            'agrement_authority' => 'required_if:user_type,guide|string|max:255',
            'professional_experience' => 'required_if:user_type,guide|string',
            'bio' => 'nullable|string|max:1000',
            'hourly_rate_from' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'tarif_horaire' => 'nullable|numeric|min:0',
            'tarif_journalier' => 'nullable|numeric|min:0',

            'main_city_id' => 'required_if:user_type,guide|exists:cities,id',
            'operation_city_ids' => [
                'required_if:user_type,guide',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->user_type !== 'guide' || !is_array($value)) {
                        return;
                    }

                    $mainCityId = (int) $request->input('main_city_id');
                    $cityIds = collect($value)->map(fn($id) => (int) $id);

                    if (!$cityIds->contains($mainCityId)) {
                        $fail('The selected main city must be included in operation_city_ids.');
                    }
                },
            ],
            'operation_city_ids.*' => 'required_with:operation_city_ids|distinct|exists:cities,id',

            'principal_language_id' => 'required_if:user_type,guide|exists:languages,id',
            'spoken_language_ids' => [
                'required_if:user_type,guide',
                'array',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->user_type !== 'guide' || !is_array($value)) {
                        return;
                    }

                    $principalLanguageId = (int) $request->input('principal_language_id');
                    $languageIds = collect($value)->map(fn($id) => (int) $id);

                    if (!$languageIds->contains($principalLanguageId)) {
                        $fail('The selected principal language must be included in spoken_language_ids.');
                    }
                },
            ],
            'spoken_language_ids.*' => 'required_with:spoken_language_ids|distinct|exists:languages,id',
            'language_levels' => 'nullable|array',
            'language_levels.*' => 'nullable|in:basic,intermediate,fluent,native',

            'profile_photo' => 'required_if:user_type,guide|file|image|mimes:jpeg,png,jpg|max:5120',
            'professional_card' => 'required_if:user_type,guide|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'identity_document_type' => 'required_if:user_type,guide|in:id_card,passport',
            'identity_document' => 'required_if:user_type,guide|file|mimes:pdf,jpeg,png,jpg|max:5120',

            'activity_images' => 'nullable|array|min:1|max:10',
            'activity_images.*' => 'required_with:activity_images|file|mimes:jpeg,png,jpg|max:5120',
        ]);

        $status = $request->user_type === 'guide' ? 'inactive' : 'active';

        $profilePhotoPath = null;
        if ($request->user_type === 'guide' && $request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('guide_profile_photos', 'public');
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'profile_image_url' => $profilePhotoPath ? '/storage/' . $profilePhotoPath : null,
            'user_type' => $request->user_type,
            'status' => $status,
        ]);

        if ($user->user_type === 'guide') {
            $guide = Guide::create([
                'user_id' => $user->id,
                'whatsapp' => $request->whatsapp,
                'guide_type' => $request->guide_type,
                'agrement_number' => $request->agrement_number,
                'agrement_date' => $request->agrement_date,
                'agrement_authority' => $request->agrement_authority,
                'bio' => $request->bio,
                'professional_experience' => $request->professional_experience,
                'hourly_rate_from' => $request->hourly_rate_from,
                'daily_rate' => $request->daily_rate,
            ]);

            $mainCityId = (int) $request->main_city_id;
            $operationCityIds = collect($request->operation_city_ids)
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $syncCities = [];
            foreach ($operationCityIds as $cityId) {
                $syncCities[$cityId] = ['is_main' => $cityId === $mainCityId];
            }
            $guide->cities()->sync($syncCities);

            $principalLanguageId = (int) $request->principal_language_id;
            $spokenLanguageIds = collect($request->spoken_language_ids)
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            $languageLevels = collect($request->input('language_levels', []));
            $syncLanguages = [];
            foreach ($spokenLanguageIds as $languageId) {
                $syncLanguages[$languageId] = [
                    'is_principal' => $languageId === $principalLanguageId,
                    'proficiency_level' => $languageLevels->get((string) $languageId, $languageLevels->get($languageId, 'fluent')),
                ];
            }
            $guide->languages()->sync($syncLanguages);

            $this->storeGuideDocument($guide, $request->file('professional_card'), 'license');
            $this->storeGuideDocument($guide, $request->file('identity_document'), $request->identity_document_type);

            if ($request->hasFile('activity_images')) {
                foreach ($request->file('activity_images') as $image) {
                    $this->storeGuideDocument($guide, $image, 'activity_proof');
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

        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->status === 'inactive') {
            Auth::guard('web')->logout();
            $user->tokens()->delete();

            $message = 'Your account is pending admin validation.';
            if ($user->user_type === 'guide' && $user->guide && $user->guide->certificate_status === 'rejected') {
                $message = 'Your guide account was declined by admin.';
            }

            return response()->json([
                'message' => $message,
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
        optional($request->user()?->currentAccessToken())->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    private function normalizeGuideRegistrationPayload(Request $request): void
    {
        if ($request->input('user_type') !== 'guide') {
            return;
        }

        if (!$request->filled('professional_experience') && $request->filled('bio')) {
            $request->merge(['professional_experience' => $request->input('bio')]);
        }

        if (!$request->filled('hourly_rate_from') && $request->filled('tarif_horaire')) {
            $request->merge(['hourly_rate_from' => $request->input('tarif_horaire')]);
        }

        if (!$request->filled('daily_rate') && $request->filled('tarif_journalier')) {
            $request->merge(['daily_rate' => $request->input('tarif_journalier')]);
        }

        if ($request->filled('guide_type')) {
            $request->merge([
                'guide_type' => $this->normalizeGuideType($request->input('guide_type')),
            ]);
        }

        if (!$request->filled('main_city_id') && $request->filled('cities')) {
            $cities = collect($request->input('cities', []));
            $mainCity = $cities->first(fn($city) => filter_var(data_get($city, 'is_main', false), FILTER_VALIDATE_BOOLEAN));

            $request->merge([
                'main_city_id' => data_get($mainCity, 'id'),
                'operation_city_ids' => $cities->pluck('id')->filter()->values()->all(),
            ]);
        }

        if (!$request->filled('principal_language_id') && $request->filled('languages')) {
            $languages = collect($request->input('languages', []));
            $principalLanguage = $languages->first(fn($language) => filter_var(data_get($language, 'is_principal', false), FILTER_VALIDATE_BOOLEAN));
            $languageLevels = [];

            foreach ($languages as $language) {
                $languageId = data_get($language, 'id');
                $level = data_get($language, 'proficiency_level');

                if ($languageId && $level) {
                    $languageLevels[$languageId] = $level;
                }
            }

            $request->merge([
                'principal_language_id' => data_get($principalLanguage, 'id'),
                'spoken_language_ids' => $languages->pluck('id')->filter()->values()->all(),
                'language_levels' => $languageLevels,
            ]);
        }

        if (!$request->hasFile('profile_photo') && $request->hasFile('activity_images')) {
            $activityImages = $request->file('activity_images', []);
            if (is_array($activityImages) && isset($activityImages[0]) && $activityImages[0] instanceof UploadedFile) {
                $request->files->set('profile_photo', $activityImages[0]);
            }
        }

        if (!$request->hasFile('professional_card') || !$request->hasFile('identity_document')) {
            foreach ((array) $request->input('documents', []) as $index => $document) {
                $documentType = data_get($document, 'type');
                $documentFile = $request->file("documents.{$index}.file");

                if (!$documentFile instanceof UploadedFile) {
                    continue;
                }

                if (($documentType === 'license' || $documentType === 'professional_card') && !$request->hasFile('professional_card')) {
                    $request->files->set('professional_card', $documentFile);
                }

                if (($documentType === 'id_card' || $documentType === 'passport') && !$request->hasFile('identity_document')) {
                    $request->files->set('identity_document', $documentFile);
                    if (!$request->filled('identity_document_type')) {
                        $request->merge(['identity_document_type' => $documentType]);
                    }
                }
            }
        }
    }

    private function normalizeGuideType(?string $guideType): ?string
    {
        if (!$guideType) {
            return $guideType;
        }

        $normalized = strtolower(trim($guideType));

        return match ($normalized) {
            'city_circuits',
            'city_circuit',
            'guide_des_villes_et_circuits_touristiques',
            'guide_des_villes_et_circuits_touristique' => 'city_circuits',
            'natural_spaces',
            'nature_spaces',
            'guide_des_espaces_naturels',
            'guide_des_espaces_naturelles' => 'natural_spaces',
            default => $guideType,
        };
    }

    private function storeGuideDocument(Guide $guide, UploadedFile $file, string $documentType): void
    {
        $path = $file->store('guide_documents', 'public');

        GuideDocument::create([
            'guide_id' => $guide->id,
            'document_type' => $documentType,
            'file_url' => '/storage/' . $path,
            'file_size' => $file->getSize(),
            'verification_status' => 'pending',
        ]);
    }
}
