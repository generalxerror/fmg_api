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
        $token = $request->bearerToken();

        try {
            $googleUser = Socialite::driver('google')->userFromToken($token);

            if ($googleUser->getEmail()) {
                $user = User::where('google_id', $googleUser->id)->first();

                $request->setUserResolver(function () use ($user) {
                    return new UserResource($user);
                });

                $auth_token = $this->refreshGoogleToken($user);

                $response = $next($request);

                if($response instanceof JsonResponse) {
                    $current_data = $response->getData();
                    $current_data->auth_token = $auth_token;
                    $response->setData($current_data);
                }

                return $response;
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error_msg' => 'Could not authenticate user'
            ], 401);
        }
    }

    public function refreshGoogleToken($user)
    {
        //Checking if the token has expired
        if (Carbon::now()->gt(Carbon::parse($user->token_expires_at))) {
            $url  = "https://www.googleapis.com/oauth2/v4/token";
            $data = [
                "client_id"     => config('services.google.client_id'),
                "client_secret" => config('services.google.client_secret'),
                "refresh_token" => $user->google_refresh_token,
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

            $user->google_token     = isset($result['access_token']) ? $result['access_token'] : null;
            $user->token_expires_at = isset($result['expires_in']) ? Carbon::now()->addSeconds($result['expires_in']) : Carbon::now();
            $user->save();

            return $result['access_token'];
        }

        return null;
    }
}
