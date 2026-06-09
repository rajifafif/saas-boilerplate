<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Register a new user.
     * 
     * Two flows:
     * 1. Main domain (no org_slug): Create new organization + user as owner
     * 2. Subdomain (with org_slug): Join existing organization as member
     */
    public function register(Request $request)
    {
        // Determine registration type
        $organizationSlug = $request->input('organization_slug')
            ?? $request->header('X-Organization-Slug');

        $isJoiningOrg = !empty($organizationSlug);

        // Validate based on registration type
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'privacyPolicies' => 'accepted',
        ];

        // If creating new org, require org details
        if (!$isJoiningOrg) {
            $rules['organization_name'] = 'required|string|max:255';
            $rules['organization_type'] = 'sometimes|string|in:studio,gym,clinic,other';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();
        try {
            // 1. Create the user
            $user = User::create([
                'email' => $validated['email'],
                'email_verified_at' => null,
                'password' => Hash::make($validated['password']),
                'auth_provider' => 'password',
                'origin_data' => json_encode([
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'registration_type' => $isJoiningOrg ? 'member' : 'owner',
                    'organization_slug' => $organizationSlug,
                ]),
            ]);

            // 2. Create person profile
            $user->person()->create([
                'id' => $user->id,
                'name' => $validated['name'],
            ]);

            // 3. Handle organization assignment
            if ($isJoiningOrg) {
                // MEMBER REGISTRATION: Join existing org
                $result = $this->joinExistingOrganization($user, $organizationSlug);

                if (!$result['success']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $result['error'],
                    ], 422);
                }

                $organization = $result['organization'];
                $role = 'member';
            } else {
                // OWNER REGISTRATION: Create new org
                $organization = $this->createNewOrganization(
                    $user,
                    $validated['organization_name'],
                    $request->input('organization_type', 'studio')
                );
                $role = 'owner';
            }

            // 4. Create member record (if joining)
            if ($isJoiningOrg && $user->person) {
                $user->person->member()->create([
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'level_id' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => $isJoiningOrg
                    ? "Welcome to {$organization->name}!"
                    : 'Registration successful. Your studio has been created.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->person?->name,
                ],
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                ],
                'role' => $role,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Join an existing organization as a member.
     */
    private function joinExistingOrganization(User $user, string $slug): array
    {
        $organization = Organization::where('slug', $slug)->first();

        if (!$organization) {
            return [
                'success' => false,
                'error' => 'Organization not found.',
            ];
        }

        // Check if org allows public registration
        if (isset($organization->allow_public_registration) && !$organization->allow_public_registration) {
            return [
                'success' => false,
                'error' => 'This organization does not accept public registrations.',
            ];
        }

        // Attach user to organization as member
        $organization->users()->attach($user->id, [
            'role' => 'member',
            'is_default' => true,
            'joined_at' => now(),
        ]);

        return [
            'success' => true,
            'organization' => $organization,
        ];
    }

    /**
     * Create a new organization with the user as owner.
     */
    private function createNewOrganization(User $user, string $name, string $type): Organization
    {
        $orgService = new OrganizationService();

        // Generate unique slug
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Create organization (service handles branch, navigation, etc.)
        $organization = $orgService->createOrganization([
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
        ], $user);

        return $organization;
    }
}

