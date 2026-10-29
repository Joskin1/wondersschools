<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesktopDevice;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Database\Models\Domain;

class DesktopDeviceAuthController extends Controller
{
    /**
     * Pair a new desktop hardware device.
     */
    public function pair(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_domain' => ['nullable', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        // If not already in tenant context (e.g. called from central domain), resolve tenant
        if (! tenancy()->initialized) {
            $tenantDomain = $validated['tenant_domain'] ?? null;
            if ($tenantDomain) {
                $domain = Domain::where('domain', $tenantDomain)->first();
                if (! $domain || ! $domain->tenant) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "School domain '{$tenantDomain}' could not be identified.",
                    ], 404);
                }

                tenancy()->initialize($domain->tenant);
            } elseif (! app()->environment('testing')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The tenant_domain field is required when connecting via central domain.',
                ], 422);
            }
        }

        // Authenticate admin user
        $user = User::query()
            ->where('email', $validated['email'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! in_array($user->role, ['sudo', 'admin'], true) || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials or insufficient permissions to pair desktop attendance hardware.',
            ], 401);
        }

        // Generate token and create device
        $tokenData = DesktopDevice::generateToken();

        DesktopDevice::create([
            'device_name' => $validated['device_name'],
            'device_token_hash' => $tokenData['hash'],
            'device_token_prefix' => $tokenData['prefix'],
            'paired_by' => $user->id,
            'last_ip' => $request->ip(),
            'last_synced_at' => now(),
            'is_active' => true,
        ]);

        // Collect school metadata
        $schoolName = Setting::where('key', 'school_name')->value('value') ?: config('app.name', 'School');
        $logoPath = Setting::where('key', 'school_logo')->value('value');
        $logoUrl = null;
        if ($logoPath) {
            $logoUrl = Storage::disk('public')->exists($logoPath)
                ? Storage::disk('public')->url($logoPath)
                : asset($logoPath);
        }

        $activeSession = Session::where('is_active', true)->first();
        $activeTerm = Term::where('is_active', true)->first();

        return response()->json([
            'status' => 'success',
            'device_token' => $tokenData['plain'],
            'school' => [
                'name' => $schoolName,
                'logo_url' => $logoUrl,
                'active_session' => $activeSession ? [
                    'id' => $activeSession->id,
                    'name' => $activeSession->name,
                ] : null,
                'active_term' => $activeTerm ? [
                    'id' => $activeTerm->id,
                    'name' => $activeTerm->name,
                ] : null,
            ],
        ], 201);
    }
}
