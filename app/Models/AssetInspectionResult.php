<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInspectionResult extends Model
{
    protected $fillable = [
        'asset_inspection_id',
        'checklist_item_id',
        'item_label',
        'is_ok',
    ];

    protected function casts(): array
    {
        return [
            'is_ok' => 'boolean',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(AssetInspection::class, 'asset_inspection_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(AssetChecklistItem::class, 'checklist_item_id');
    }
}
