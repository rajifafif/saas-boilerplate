<?php

namespace App\Models\Traits;

trait HasAuditColumns
{
    protected static function bootHasAuditColumns(): void
    {
        static::creating(function ($model) {
            if (!$model->created_by && auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (!$model->updated_by && auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::deleting(function ($model) {
            if (!$model->deleted_by && auth()->check()) {
                $model->deleted_by = auth()->id();
            }
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }

    // for the relationship transaction
    public function delete()
    {
        if (!$this->deleted_by && auth()->check()) {
            $this->deleted_by = auth()->id();
            $this->save();
        }

        return parent::delete();
    }
}
