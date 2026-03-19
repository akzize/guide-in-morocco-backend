<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use App\Models\Client;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

     // create toggleStatus method to toggle the status of a user (client ) between active and inactive
  public function toggleStatus(Request $request, Client $client)
{
    // Check admin
    if ($request->user()->user_type !== 'admin') {
        return response()->json([
            'message' => 'Unauthorized. Admin access required.'
        ], 403);
    }

    $user = $client->user;

    // Toggle status
    $newStatus = $user->status === 'active' ? 'inactive' : 'active';

    $user->update([
        'status' => $newStatus,
    ]);

    return response()->json([
        'message' => $newStatus === 'active'
            ? 'Client account activated successfully.'
            : 'Client account locked successfully.',
        'status' => $newStatus,
        'client' => $client->load('user'),
    ]);
}
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdminUserRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AdminUser $adminUser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AdminUser $adminUser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminUserRequest $request, AdminUser $adminUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdminUser $adminUser)
    {
        //
    }
}
