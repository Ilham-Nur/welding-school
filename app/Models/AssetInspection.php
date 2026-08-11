<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetInspection extends Model
{
    protected $fillable = [
        'asset_id',
        'inspector_id',
        'inspected_at',
        'condition',
        'status',
        'next_inspection_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'next_inspection_at' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssetInspectionResult::class)->orderBy('id');
    }
}
