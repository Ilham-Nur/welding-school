<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageItem extends Model
{
    protected $fillable = ['code', 'name', 'storage_unit_id', 'minimum_stock', 'is_active', 'notes', 'created_by', 'updated_by'];

    protected $with = ['unit'];

    public static function internalCode(int $id): string
    {
        return 'ATP-CNS-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return ['minimum_stock' => 'decimal:3', 'is_active' => 'boolean'];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(StorageStock::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(StorageUnit::class, 'storage_unit_id');
    }

    public function transactionLines(): HasMany
    {
        return $this->hasMany(StorageTransactionLine::class);
    }

    public function unitIsLocked(): bool
    {
        return $this->exists && ($this->stocks()->exists() || $this->transactionLines()->exists());
    }
}
