<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoggedIn
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->bearerToken() || !$request->header('X-Rt')) {
            return response()->json([
                'error_msg' => 'Could not authenticate user'
            ], 401);
        }

        $user           = null;
        $new_auth_token = null;

        try {
            // Try with token
            $googleUser = Socialite::driver('google')->userFromToken($request->bearerToken());
            $user = User::where('google_id', $googleUser->id)->first();
        } catch (\Throwable $th) {
            // Try to refresh token
            $refresh_token_result = $this->refreshGoogleToken($request->header('X-Rt'));

            if($refresh_token_result) {
                $new_auth_token = $refresh_token_result['access_token'];
                $googleUser = Socialite::driver('google')->userFromToken($new_auth_token);
                $user       = User::where('google_id', $googleUser->id)->first();

                $user->google_token     = isset($new_auth_token) ? $new_auth_token : null;
                $user->token_expires_at = isset($refresh_token_result['expires_in']) ? Carbon::now()->addSeconds($refresh_token_result['expires_in']) : Carbon::now();
                $user->save();
            }
        }

        if($user) {
            $request->setUserResolver(function () use ($user) {
                return new UserResource($user);
            });

            $response                   = $next($request);
            $current_data               = $response->getData();
            $current_data->auth_token   = $new_auth_token;
            $response->setData($current_data);

            return $response;
        }

        return response()->json([
            'error_msg' => 'Could not authenticate user'
        ], 401);
    }

    public function refreshGoogleToken($rt)
    {
        $url  = "https://www.googleapis.com/oauth2/v4/token";
        $data = [
            "client_id"     => config('services.google.client_id'),
            "client_secret" => config('services.google.client_secret'),
            "refresh_token" => $rt,
            "grant_type"    => 'refresh_token'
        ];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        $err    = curl_error($ch);

        curl_close($ch);

        if ($err) {
            return null;
        }

        $result = json_decode($result, true);
        return $result;
    }
}
