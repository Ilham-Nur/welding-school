<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetKindNumberSequence extends Model
{
    protected $primaryKey = 'asset_kind_id';

    public $incrementing = false;

    protected $fillable = ['asset_kind_id', 'last_number'];

    protected function casts(): array
    {
        return ['last_number' => 'integer'];
    }

    public function assetKind(): BelongsTo
    {
        return $this->belongsTo(AssetKind::class);
    }
}
