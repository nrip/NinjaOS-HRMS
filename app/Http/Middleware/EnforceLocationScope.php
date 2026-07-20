<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;

/**
 * EnforceLocationScope Middleware
 * 
 * Extracts the location_id from the authenticated user's JWT token
 * and sets it in the TenantContext singleton.
 * 
 * This middleware MUST be applied to all authenticated routes to ensure
 * that the LocationScope global scope has a valid location_id to filter by.
 * 
 * For Super Admin users, location_id is set to null (allowing cross-location queries).
 * All such cross-location queries are logged for audit purposes.
 */
class EnforceLocationScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);

        if ($request->user()) {
            $user = $request->user();
            $locationId = $user->location_id;
            $userId = $user->id;

            // Set the tenant context
            $tenantContext->setLocationId($locationId);
            $tenantContext->setUserId($userId);

            // If the user is Super Admin, log this cross-location access attempt
            if ($user->hasRole('super_admin')) {
                Log::channel('audit')->info('Super Admin cross-location access', [
                    'user_id' => $userId,
                    'user_email' => $user->email,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ]);
            }
        }

        return $next($request);
    }
}
