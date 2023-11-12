<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SlackRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = 'v0';

        $secret     = config('services.slack.signing_secret');
        $body       = $request->getContent();
        $timestamp  = $request->header('X-Slack-Request-Timestamp');

        if (Carbon::now()->diffInMinutes(Carbon::createFromTimestamp($timestamp)) > 5) {
            throw new Exception("Invalid timstamp, gap too big");
        }

        $sig_basestring     = "{$version}:{$timestamp}:{$body}";
        $hash               = hash_hmac('sha256', $sig_basestring, $secret);
        $local_signature    = "{$version}={$hash}";
        $remote_signature   = $request->header('X-Slack-Signature');

        if ($remote_signature !== $local_signature) {
            throw new Exception("Invalid signature");
        }

        return $next($request);
    }
}
