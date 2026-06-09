<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->person->user_id,
            'name_prefix' => $this->person->name_prefix,
            'name' => $this->person->name,
            'name_suffix' => $this->person->name_suffix,
            'gender' => $this->person->gender,
            'birth_date' => $this->person->birth_date,
            'birth_place' => $this->person->birth_place,
            'default_address_id' => $this->person->default_address_id,
            'email' => $this->person->email,
            'phone' => $this->person->phone,
            'project_id' => $this->person->project_id,
            'status' => empty($this->deleted_at) ? 'active' : 'inactive'
        ];
    }
}
