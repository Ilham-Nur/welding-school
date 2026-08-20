<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['name', 'parent_id', 'is_storage', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['is_storage' => 'boolean', 'is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(StorageStock::class);
    }

    public function fullName(): string
    {
        $names = [$this->name];
        $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();
        $guard = 0;

        while ($parent && $guard++ < 8) {
            array_unshift($names, $parent->name);
            $parent = $parent->parent()->first();
        }

        return implode(' / ', $names);
    }
}
