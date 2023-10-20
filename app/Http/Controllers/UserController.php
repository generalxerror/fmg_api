<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function me(Request $request) {
        return $request->user();
    }

    public function logout(Request $request) {
        try {
            $user = User::find($request->user()->id);
            $user->google_token = null;
            $user->save();

            return response()->json([
                'message' => 'Logged out successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error_msg' => 'Something went wrong, try again later.'
            ], 400);
        }
    }
}
