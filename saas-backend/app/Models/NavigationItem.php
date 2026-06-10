<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'parent_id',
        'type',
        'title',
        'slug',
        'route',
        'icon',
        'permission_name',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('type', 'page')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('type', 'action')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }

    public function allChildren(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}
