<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'training_application_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
    ];

    public function trainingApplication(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
