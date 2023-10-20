<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect() {
        // return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return Socialite::driver('google')
            ->stateless()
            ->with(['access_type' => 'offline', "prompt" => "consent select_account"])
            ->redirect();
    }

    public function callback() {
        $googleUser = Socialite::driver('google')->stateless()->user();

        User::updateOrCreate([
            'google_id' => $googleUser->id
        ], [
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'avatar' => $googleUser->avatar,
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken,
            'token_expires_at' => Carbon::now()->addSeconds($googleUser->expiresIn)
        ]);

        return redirect(config('fmg.frontend_url').'?code='.$googleUser->token);
    }
}
