<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guide;
use App\Mail\GuideAccountActivated;
use Illuminate\Support\Facades\Mail;

class AdminGuideController extends Controller
{
    public function activate(Request $request, Guide $guide)
    {
        if ($request->user()->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        if ($guide->certificate_status === 'approved') {
            return response()->json(['message' => 'Guide is already activated.'], 400);
        }

        $guide->update([
            'certificate_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        $guide->user->update([
            'status' => 'active',
        ]);

        Mail::to($guide->user->email)->send(new GuideAccountActivated($guide->user));

        return response()->json([
            'message' => 'Guide account activated successfully.',
            'guide' => $guide->load('user'),
        ]);
    }
}
