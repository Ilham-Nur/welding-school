<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageStockOpname extends Model
{
    protected $fillable = ['number', 'location_id', 'counted_at', 'status', 'notes', 'created_by', 'completed_by', 'completed_at'];

    protected function casts(): array
    {
        return ['counted_at' => 'date', 'completed_at' => 'datetime'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StorageStockOpnameLine::class, 'stock_opname_id');
    }
}
