<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Kelurahan extends Model
{
   use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'id',
        'provinsi_id',
        'kota_id',
        'kecamatan_id',
        'name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id', 'id');
    }

    public function kota()
    {
        return $this->belongsTo(Kota::class, 'kota_id', 'id');
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id', 'id');
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray()
    {
        $indexedData = array_merge($this->toArray(), [
            'id' => (string) $this->id,
            'created_at' => $this->created_at->timestamp,

            'kelurahan_id' => (string) $this->id,
            'kelurahan' => $this->name,
            'kecamatan_id' => (string) $this->kecamatan_id,
            'kecamatan' => $this->kecamatan->name,
            'kota_id' => (string) $this->kota_id,
            'kota' => $this->kota->name,
            'provinsi_id' => (string) $this->provinsi_id,
            'provinsi' => $this->provinsi->name,
        ]);
        // $arr[] = $indexedData['kelurahan'];
        // $arr[] = $indexedData['kecamatan'];
        // $arr[] = $indexedData['kota'];
        // $arr[] = $indexedData['provinsi'];

        $indexedData['full_name'] = $this->full_name;

        return $indexedData;
    }

    /**
     * The Typesense schema to be created.
     *
     * @return array
     */
    public static function getCollectionSchema(): array {
        return [
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                ],
                [
                    'name' => 'name',
                    'type' => 'string',
                ],
                [
                    'name' => 'kelurahan_id',
                    'type' => 'string',
                ],
                [
                    'name' => 'kelurahan',
                    'type' => 'string',
                ],
                [
                    'name' => 'kecamatan_id',
                    'type' => 'string',
                ],
                [
                    'name' => 'kecamatan',
                    'type' => 'string',
                ],
                [
                    'name' => 'kota_id',
                    'type' => 'string',
                ],
                [
                    'name' => 'kota',
                    'type' => 'string',
                ],
                [
                    'name' => 'provinsi_id',
                    'type' => 'string',
                ],
                [
                    'name' => 'provinsi',
                    'type' => 'string',
                ],
                [
                    'name' => 'full_name',
                    'type' => 'string',
                    'sort' => true
                ],
                [
                    'name' => 'created_at',
                    'type' => 'int64',
                ],
            ],
            'default_sorting_field' => 'full_name',
        ];
    }

     /**
     * The fields to be queried against. See https://typesense.org/docs/0.24.0/api/search.html.
     *
     * @return array
     */
    public function typesenseQueryBy(): array {
        return [
            'full_name',
        ];
    }

    /**
     * Modify the query used to retrieve models when making all of the models searchable.
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['kecamatan', 'kota', 'provinsi']);
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'kelurahan';
    }

    protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => implode(', ', array_filter([
            $this->name ?? null,
            $this->kecamatan->name ?? null,
            $this->kota->name ?? null,
            $this->provinsi->name ?? null,
        ])),
    );
}
}
