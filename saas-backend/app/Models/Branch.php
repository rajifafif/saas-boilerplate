<?php

namespace App\Models;

use App\Models\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Branch extends Model
{
    use HasFactory, HasUlids, SoftDeletes, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'phone',
        'email',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BranchScope);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }


    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function navigation(): MorphOne
    {
        return $this->morphOne(Navigation::class, 'navigateable');
    }

    public function configs(): MorphMany
    {
        return $this->morphMany(Configuration::class, 'configurable');
    }
}
