<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

class EnsureEmailIsVerifiedWhenRequired extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! config('auth.email_verification_required', true)) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
