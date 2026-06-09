<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

class AuthController extends Controller
{
    /**
     * Access token lifetime in seconds (7 days)
     */
    private const ACCESS_TOKEN_TTL = 60 * 60 * 24 * 7;

    /**
     * Refresh token lifetime in seconds (30 days)
     */
    private const REFRESH_TOKEN_TTL = 60 * 60 * 24 * 30;

    /**
     * Login endpoint - returns access token, refresh token, and user data.
     */
    public function login(Request $request): JsonResponse|array
    {
        try {
            // 1. Firebase Token Login
            if ($request->has('firebase_token')) {
                return $this->handleFirebaseLogin($request);
            }

            // 2. Email/Password Login
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['User not found.']
                ]);
            }

            if (!Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => ['Incorrect password.']
                ]);
            }

            return $this->buildLoginResponse($user);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Login failed',
                'errors' => $e instanceof ValidationException ? $e->errors() : ['server' => [$e->getMessage()]]
            ], 401);
        }
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        try {
            $refreshToken = $request->input('refresh_token');

            // Parse and validate the refresh token
            $parser = new Parser(new JoseEncoder());
            $token = $parser->parse($refreshToken);

            $algorithm = new Sha256();
            $signingKey = InMemory::plainText(config('app.key'));

            $validator = new Validator();
            if (!$validator->validate($token, new SignedWith($algorithm, $signingKey))) {
                return response()->json(['message' => 'Invalid refresh token'], 401);
            }

            // Check expiration
            if ($token->isExpired(new DateTimeImmutable())) {
                return response()->json(['message' => 'Refresh token expired'], 401);
            }

            // Get claims
            $userId = $token->claims()->get('uid');
            $tokenType = $token->claims()->get('type');

            if ($tokenType !== 'refresh') {
                return response()->json(['message' => 'Invalid token type'], 401);
            }

            // Find user
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            // Get current organization from token or default
            $orgId = $token->claims()->get('org_id');
            $organization = null;
            $role = null;

            if ($orgId) {
                $membership = $user->organizations()
                    ->where('organizations.id', $orgId)
                    ->first();

                if ($membership) {
                    $organization = $membership;
                    $role = $membership->pivot->role;
                }
            }

            // Fall back to default org if none in token
            if (!$organization) {
                $defaultOrg = $user->organizations()
                    ->withPivot('role', 'is_default')
                    ->orderByDesc('organization_users.is_default')
                    ->first();

                if ($defaultOrg) {
                    $organization = $defaultOrg;
                    $role = $defaultOrg->pivot->role;
                }
            }

            // Issue new tokens
            $accessToken = $this->issueAccessToken($user, $organization?->id, $role);
            $newRefreshToken = $this->issueRefreshToken($user, $organization?->id);

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => self::ACCESS_TOKEN_TTL,
                'organization' => $organization ? [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'role' => $role,
                ] : null,
                // Legacy support for @sidebase/nuxt-auth
                'token' => $accessToken,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Switch organization - issues new tokens with different org context.
     */
    public function switchOrganization(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        // Verify user belongs to this organization
        $membership = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->first();

        if (!$membership) {
            return response()->json([
                'message' => 'You are not a member of this organization.',
            ], 403);
        }

        $role = $membership->pivot->role;

        // Issue new tokens with new org context
        $accessToken = $this->issueAccessToken($user, $organization->id, $role);
        $refreshToken = $this->issueRefreshToken($user, $organization->id);

        // Get the main (first active) branch for this organization
        $mainBranch = $organization->branches()
            ->where('is_active', true)
            ->first();

        return response()->json([
            'message' => 'Switched to ' . $organization->name,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'data' => [
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'type' => $organization->type,
                ],
                'role' => $role,
                'branch' => $mainBranch ? [
                    'id' => $mainBranch->id,
                    'name' => $mainBranch->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Logout - invalidates the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current Sanctum token if exists
            $user = $request->user();
            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Logout completed']);
        }
    }

    /**
     * Get current authenticated user with organization context.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get org context from request (set by JwtMiddleware)
        $orgId = $request->attributes->get('jwt_org_id');
        $role = $request->attributes->get('jwt_role');

        $organizations = $user->organizations()
            ->withPivot('role', 'is_default', 'joined_at')
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'type' => $org->type,
                    'role' => $org->pivot->role,
                    'is_default' => $org->pivot->is_default,
                ];
            });

        $currentOrg = $orgId ? $organizations->firstWhere('id', $orgId) : null;

        return response()->json([
            'user' => new UserResource($user),
            'organizations' => $organizations,
            'current_organization' => $currentOrg,
            'current_role' => $role,
        ]);
    }

    /**
     * Get current tenant info (legacy support).
     */
    public function currentTenant(Request $request)
    {
        return response()->json([
            'organization_id' => $request->attributes->get('jwt_org_id'),
            'role' => $request->attributes->get('jwt_role'),
        ]);
    }

    /**
     * Build login response with tokens and organization data.
     */
    protected function buildLoginResponse(User $user): array
    {
        // Get user's organizations with pivot data
        $organizations = $user->organizations()
            ->withPivot('role', 'is_default', 'joined_at')
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'type' => $org->type,
                    'role' => $org->pivot->role,
                    'is_default' => $org->pivot->is_default,
                ];
            });

        // Get default organization
        $defaultOrg = $organizations->firstWhere('is_default', true)
            ?? $organizations->first();

        $orgId = $defaultOrg['id'] ?? null;
        $role = $defaultOrg['role'] ?? null;

        // Issue both access and refresh tokens
        $accessToken = $this->issueAccessToken($user, $orgId, $role);
        $refreshToken = $this->issueRefreshToken($user, $orgId);

        return [
            'user' => new UserResource($user),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'organizations' => $organizations,
            'current_organization' => $defaultOrg,

            // Legacy support - also return as 'token' for backward compatibility
            'token' => $accessToken,
        ];
    }

    /**
     * Issue a short-lived access token with organization context.
     */
    public function issueAccessToken(User $user, ?string $orgId = null, ?string $role = null): string
    {
        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $algorithm = new Sha256();
        $signingKey = InMemory::plainText(config('app.key'));

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $timestamp = $now->getTimestamp();

        // Create Sanctum token for API access
        $sanctumToken = $user->createToken('access');

        $token = $tokenBuilder
            ->issuedBy(config('app.url'))
            ->identifiedBy($sanctumToken->plainTextToken)
            ->issuedAt(new DateTimeImmutable("@$timestamp"))
            ->canOnlyBeUsedAfter(new DateTimeImmutable("@$timestamp"))
            ->expiresAt(new DateTimeImmutable("@" . ($timestamp + self::ACCESS_TOKEN_TTL)))
            // User claims
            ->withClaim('uid', $user->id)
            ->withClaim('type', 'access')
            // Organization context claims (stateless!)
            ->withClaim('org_id', $orgId)
            ->withClaim('role', $role)
            ->getToken($algorithm, $signingKey);

        return $token->toString();
    }

    /**
     * Issue a long-lived refresh token.
     */
    public function issueRefreshToken(User $user, ?string $orgId = null): string
    {
        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $algorithm = new Sha256();
        $signingKey = InMemory::plainText(config('app.key'));

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $timestamp = $now->getTimestamp();

        $token = $tokenBuilder
            ->issuedBy(config('app.url'))
            ->identifiedBy(Str::uuid()->toString())
            ->issuedAt(new DateTimeImmutable("@$timestamp"))
            ->canOnlyBeUsedAfter(new DateTimeImmutable("@$timestamp"))
            ->expiresAt(new DateTimeImmutable("@" . ($timestamp + self::REFRESH_TOKEN_TTL)))
            // Minimal claims for refresh token
            ->withClaim('uid', $user->id)
            ->withClaim('type', 'refresh')
            ->withClaim('org_id', $orgId)
            ->getToken($algorithm, $signingKey);

        return $token->toString();
    }

    /**
     * Handle Firebase token login.
     */
    protected function handleFirebaseLogin(Request $request): JsonResponse|array
    {
        $firebaseToken = $request->firebase_token;

        /** @var \Kreait\Firebase\Auth $auth */
        $auth = app('firebase.auth');

        // Verify the token
        $verifiedToken = $auth->verifyIdToken($firebaseToken);

        // Extract user info
        $claims = $verifiedToken->claims()->all();
        $email = $claims['email'] ?? null;
        $name = $claims['name'] ?? null;

        if (!$email) {
            return response()->json([
                'message' => 'Firebase token missing email.',
                'errors' => ['oauth' => ['Email is required in Firebase token.']]
            ], 422);
        }

        // Lookup user by email
        $user = User::where('email', $email)->first();

        // Handle registration if user not found and action=register
        if (!$user && $request->action === 'register') {
            $user = $this->createUserFromFirebase($request, $claims, $email, $name);
        }

        if (!$user) {
            return response()->json([
                'message' => 'Login failed',
                'errors' => ['oauth' => ['This email is not registered.']]
            ], 401);
        }

        return $this->buildLoginResponse($user);
    }

    /**
     * Create user from Firebase registration.
     */
    protected function createUserFromFirebase(Request $request, array $claims, string $email, ?string $name): User
    {
        return DB::transaction(function () use ($request, $claims, $email, $name) {
            $user = User::create([
                'email' => $email,
                'email_verified_at' => now(),
                'password' => null,
                'auth_provider' => 'google',
                'origin_data' => json_encode([
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'firebase_uid' => $claims['sub'] ?? null,
                ]),
            ]);

            $user->assignRole('Member');

            // Create related Person profile
            $person = $user->person()->create([
                'id' => $user->id,
                'name' => $name,
            ]);

            $person->member()->create([
                'id' => $user->id,
                'project_id' => '',
                'user_id' => $user->id,
                'store_id' => null,
                'level_id' => null
            ]);

            // Attach avatar from Firebase if available
            if (isset($claims['picture'])) {
                $this->attachFirebaseAvatar($person, $claims['picture']);
            }

            return $user;
        });
    }

    /**
     * Attach avatar from Firebase photo URL.
     */
    protected function attachFirebaseAvatar($person, string $imageUrl): void
    {
        try {
            $tempImage = \Illuminate\Support\Facades\Http::get($imageUrl);

            if ($tempImage->successful()) {
                $tempFilePath = storage_path('app/temp-avatar-' . uniqid() . '.jpg');
                file_put_contents($tempFilePath, $tempImage->body());

                $person->addMedia($tempFilePath)
                    ->usingFileName('avatar.jpg')
                    ->toMediaCollection('avatars');

                if (file_exists($tempFilePath)) {
                    unlink($tempFilePath);
                }
            }
        } catch (\Throwable $e) {
            // Silently fail avatar attachment
            \Log::warning('Failed to attach Firebase avatar: ' . $e->getMessage());
        }
    }
}

