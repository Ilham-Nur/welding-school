<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageUnit extends Model
{
    protected $fillable = ['symbol', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StorageItem::class);
    }

    public function label(): string
    {
        return filled($this->name) ? "{$this->symbol} | {$this->name}" : $this->symbol;
    }
}
