<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Symfony\Component\Clock\Clock;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Middleware
 *
 * Parses the JWT token from the Authorization header and extracts
 * organization context (org_id, role) for stateless operation.
 *
 * Sets request attributes:
 * - jwt_user_id: The authenticated user's ID
 * - jwt_org_id: Current organization ID from token
 * - jwt_role: User's role in the current organization
 * - jwt_token_type: 'access' or 'refresh'
 */
class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthorized!'], 401);
        }

        $parser = new Parser(new JoseEncoder());
        $signingKey = InMemory::plainText(config('app.key'));
        $clock = new Clock();
        $validAt = new StrictValidAt($clock);
        $signedWith = new SignedWith(new Sha256(), $signingKey);
        $jwt = new JwtFacade($parser);

        try {
            $parsedToken = $jwt->parse($token, $signedWith, $validAt);
        } catch (RequiredConstraintsViolated $e) {
            // Check if it's an expiration error
            if (str_contains($e->getMessage(), 'expired')) {
                return response()->json([
                    'message' => 'Token has expired',
                    'error' => 'token_expired',
                ], 401);
            }
            return response()->json(['message' => 'Invalid Token: ' . $e->getMessage()], 498);
        }

        // Extract claims
        $claims = $parsedToken->claims();
        $jti = $claims->get('jti');
        $uid = $claims->get('uid');
        $tokenType = $claims->get('type', 'access');
        $orgId = $claims->get('org_id');
        $role = $claims->get('role');

        // For access tokens, verify Sanctum token exists
        if ($tokenType === 'access') {
            $sanctumToken = PersonalAccessToken::findToken($jti);
            if (!$sanctumToken) {
                return response()->json(['message' => 'Token does not exist or has been revoked.'], 498);
            }
        }

        // Find and authenticate user
        $user = User::find($uid);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 498);
        }

        // Log in the user for the current request
        Auth::login($user);

        // Set request attributes for downstream use (STATELESS ORG CONTEXT!)
        $request->attributes->set('jwt_user_id', $uid);
        $request->attributes->set('jwt_token_type', $tokenType);
        $request->attributes->set('jwt_org_id', $orgId);
        $request->attributes->set('jwt_role', $role);

        // Also set in container for global access
        app()->instance('jwt.org_id', $orgId);
        app()->instance('jwt.role', $role);
        app()->instance('jwt.user_id', $uid);

        // Allow header override for org_id (will be validated in TenantMiddleware)
        $headerOrgId = $request->header('X-Organization-ID');
        if ($headerOrgId && $headerOrgId !== $orgId) {
            $request->attributes->set('jwt_org_id_override', $headerOrgId);
        }

        // Legacy: also merge uid into request
        $request->merge(['uid' => $uid]);

        return $next($request);
    }
}

