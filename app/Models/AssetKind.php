<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetKind extends Model
{
    protected $fillable = [
        'category_code',
        'code',
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function numberSequence(): HasOne
    {
        return $this->hasOne(AssetKindNumberSequence::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function categoryLabel(): string
    {
        return Asset::CATEGORIES[$this->category_code] ?? $this->category_code;
    }

    public function codeFor(int $number): string
    {
        return sprintf('ATP-%s-%s-%03d', $this->category_code, $this->code, $number);
    }
}
