<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageTransaction extends Model
{
    protected $fillable = ['number', 'type', 'transaction_date', 'location_id', 'training_batch_id', 'supplier', 'reference', 'purpose', 'status', 'handled_by', 'created_by', 'posted_at', 'notes'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'posted_at' => 'datetime'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StorageTransactionLine::class);
    }
}
