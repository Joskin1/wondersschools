<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\DesktopDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDesktopDevice
{
    /**
     * Handle an incoming request from a desktop hardware client.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing or malformed Authorization bearer header.',
            ], 401);
        }

        $bearerToken = trim(substr($header, 7));
        $device = DesktopDevice::findByBearerToken($bearerToken);

        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or revoked desktop device token.',
            ], 401);
        }

        // Record active sync metadata
        $device->recordSync($request->ip());
        $request->attributes->set('desktop_device', $device);

        return $next($request);
    }
}
