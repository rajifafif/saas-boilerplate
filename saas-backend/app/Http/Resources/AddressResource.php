<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'kelurahan_id' => (string) $this->kelurahan_id,
            'kecamatan_id' => (string) $this->kecamatan_id,
            'kota_id' => (string) $this->kota_id,
            'provinsi_id' => (string) $this->provinsi_id,
            'kelurahan' => $this->kelurahan?->name ?? '',
            'kecamatan' => $this->kecamatan?->name ?? '',
            'kota' => $this->kota?->name ?? '',
            'provinsi' => $this->provinsi?->name ?? '',
            'full_name' => $this->full_name ?? '',
            'kode_pos' => $this->kode_pos,
            'text' => $this->text,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
