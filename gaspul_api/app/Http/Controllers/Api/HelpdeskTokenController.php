<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase I.2 — Helpdesk SSO Token Endpoint
 *
 * Returns a short-lived Sanctum token for the currently authenticated ASN
 * to be used as a one-time SSO credential when redirecting to e_SARAku Helpdesk.
 *
 * Only role=ASN may call this endpoint (enforced by 'role:ASN' middleware on route).
 *
 * The token is named 'helpdesk-sso' to distinguish it from regular API tokens.
 * Prior helpdesk-sso tokens for this user are revoked before issuing a new one
 * to prevent token accumulation.
 *
 * Phase J-05 — Production Hardening: token now expires after 5 minutes
 * (HELPDESK_SSO_TOKEN_TTL_MINUTES), independent of the global Sanctum
 * 'expiration' config (which stays null so other API tokens — e.g.
 * 'auth_token' used by mobile/API clients — are unaffected). The token is
 * also single-use: AuthController::me() deletes it immediately after the
 * helpdesk consumes it (see PHASE_J_PRODUCTION_HARDENING_REPORT.md J-05).
 */
class HelpdeskTokenController extends Controller
{
    private const TOKEN_TTL_MINUTES = 5;

    public function issue(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke any previous helpdesk-sso tokens to prevent accumulation
        $user->tokens()->where('name', 'helpdesk-sso')->delete();

        // Issue a fresh token, expiring in 5 minutes regardless of whether it's used
        $token = $user->createToken(
            'helpdesk-sso',
            ['*'],
            now()->addMinutes(self::TOKEN_TTL_MINUTES),
        )->plainTextToken;

        return response()->json([
            'token'        => $token,
            'helpdesk_url' => rtrim(config('services.helpdesk.url'), '/'),
        ]);
    }
}
