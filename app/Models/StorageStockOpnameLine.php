<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageStockOpnameLine extends Model
{
    protected $fillable = ['stock_opname_id', 'storage_item_id', 'system_quantity', 'counted_quantity', 'difference', 'notes'];

    protected function casts(): array
    {
        return ['system_quantity' => 'decimal:3', 'counted_quantity' => 'decimal:3', 'difference' => 'decimal:3'];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StorageStockOpname::class, 'stock_opname_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StorageItem::class, 'storage_item_id');
    }
}
