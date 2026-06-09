<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelurahanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kelurahan_id' => (string) $this->id,
            'kecamatan_id' => (string) $this->kecamatan_id,
            'kota_id' => (string) $this->kota_id,
            'provinsi_id' => (string) $this->provinsi_id,
            'kelurahan' => $this->name,
            'kecamatan' => $this->kecamatan->name,
            'kota' => $this->kota->name,
            'provinsi' => $this->provinsi->name,
            'full_name' => $this->full_name
        ];
    }
}
