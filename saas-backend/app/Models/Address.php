<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'provinsi_id',
        'kota_id',
        'kelurahan_id',
        'kecamatan_id',
        'kode_pos',
        'text',
        'description',
        'latitude',
        'longitude',
        'full_name',
        'receiver_name',
        'receiver_phone'
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7'
    ];

    public function addressable()
    {
        return $this->morphTo();
    }

    public function persons()
    {
        return $this->belongsToMany(Person::class, 'person_addresses');
    }

    public function fullText(): Attribute
    {
        return Attribute::make(
            // TODO Fix dulu ketika insert dari kecamatan_id dan ambil langsung dari tabelnya sendiri
            get: fn () => implode(', ', array_filter([
                $this->text,
                $this->kecamatan->name,
                $this->kecamatan->kelurahan->name ?? null,
                $this->kecamatan->kota->name ?? null,
                $this->kecamatan->provinsi->name ?? null,
            ])),
        );
    }
}
