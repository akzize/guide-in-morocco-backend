<?php

use App\Models\Guide;
use App\Models\GuideDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('guides index includes guide documents', function () {
	$admin = User::create([
		'first_name' => 'Admin',
		'last_name' => 'User',
		'email' => 'index-admin@example.com',
		'password' => 'password123',
		'user_type' => 'admin',
		'status' => 'active',
	]);

	$token = $admin->createToken('test-guides-index')->plainTextToken;

	$user = User::create([
		'first_name' => 'Index',
		'last_name' => 'Guide',
		'email' => 'index-guide@example.com',
		'password' => 'password123',
		'user_type' => 'guide',
		'status' => 'active',
	]);

	$guide = Guide::create([
		'user_id' => $user->id,
		'certificate_status' => 'approved',
	]);

	GuideDocument::create([
		'guide_id' => $guide->id,
		'document_type' => 'license',
		'file_url' => '/storage/guide_documents/license.pdf',
		'file_size' => 128000,
	]);

	$response = get('/api/guides', [
		'Authorization' => 'Bearer ' . $token,
	]);

	$response->assertOk()
		->assertJsonPath('data.0.id', $guide->id)
		->assertJsonPath('data.0.documents.0.document_type', 'license')
		->assertJsonPath('data.0.documents.0.file_url', '/storage/guide_documents/license.pdf');
});

test('guide show includes guide documents', function () {
	$user = User::create([
		'first_name' => 'Show',
		'last_name' => 'Guide',
		'email' => 'show-guide@example.com',
		'password' => 'password123',
		'user_type' => 'guide',
		'status' => 'active',
	]);

	$guide = Guide::create([
		'user_id' => $user->id,
		'certificate_status' => 'approved',
	]);

	GuideDocument::create([
		'guide_id' => $guide->id,
		'document_type' => 'id_card',
		'file_url' => '/storage/guide_documents/id-card.jpg',
		'file_size' => 256000,
	]);

	$response = get("/api/guides/{$guide->id}");

	$response->assertOk()
		->assertJsonPath('data.id', $guide->id)
		->assertJsonPath('data.documents.0.document_type', 'id_card')
		->assertJsonPath('data.documents.0.file_url', '/storage/guide_documents/id-card.jpg');
});
