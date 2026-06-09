<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userData = parent::toArray($request);

        $person = $this->person;
        if ($person) {
            $userData = array_merge($person->toArray(), $userData);
            $userData['person_id'] = $this->person->id;

            // Map fields for frontend
            $userData['name'] = $person->name; // Use person's name as source of truth
            $userData['phone'] = $person->phone;
            $userData['birth_date'] = $person->birth_date;
            $userData['emergency_name'] = $person->emergency_contact_name;
            $userData['emergency_phone'] = $person->emergency_contact_phone;
            $userData['emergency_relation'] = $person->emergency_contact_relation;
            $userData['address'] = $person->defaultAddress?->text;
        }

        $userData['code'] = $userData['code'] ?? '';
        unset($userData['user_id']);

        // Roles
        $userData['roles'] = $this?->roles ?? null;
        $userData['roles_names'] = $this?->roles->pluck('name');

        $userData['avatar'] = $this->person?->getMedia('avatars')->sortByDesc('created_at')->first()?->getUrl();

        return $userData;
    }
}
