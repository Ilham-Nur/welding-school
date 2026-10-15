<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchAssignment extends Model
{
    public const ROLES = ['coordinator' => 'Penanggung Jawab', 'lead_trainer' => 'Trainer utama', 'assistant_trainer' => 'Trainer pendamping'];

    protected $fillable = ['training_batch_id', 'user_id', 'role'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
