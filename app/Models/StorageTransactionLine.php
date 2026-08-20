<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageTransactionLine extends Model
{
    protected $fillable = ['storage_transaction_id', 'storage_item_id', 'quantity', 'unit_cost', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:2'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StorageTransaction::class, 'storage_transaction_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StorageItem::class, 'storage_item_id');
    }
}
