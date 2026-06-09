<?php

namespace App\Models;

use App\Models\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemProcess extends Model
{
    use SoftDeletes, HasAuditColumns;

    protected $fillable = [
        'process_name', 'reference_id', 'status',
        'error_message', 'pending_at', 'processing_at',
        'completed_at', 'failed_at', 'canceled_at'
    ];

    public function setStatus(string $status, ?string $errorMessage = null)
    {
        $this->status = $status;

        switch ($status) {
            case 'pending':
                $this->pending_at = now();
                break;
            case 'processing':
                $this->processing_at = now();
                break;
            case 'completed':
                $this->completed_at = now();
                break;
            case 'failed':
                $this->failed_at = now();
                $this->error_message = $errorMessage;
                break;
            case 'canceled':
                $this->canceled_at = now();
                break;
        }

        $this->save();
    }
}

