<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports are always scoped to a single company, so they only make sense
 * for customer users. DynoPOS admins (no company_id) manage everything
 * cross-tenant via /admin instead.
 */
class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->company_id, 403, 'Laporan ini hanya untuk akaun customer.');

        return $next($request);
    }
}
