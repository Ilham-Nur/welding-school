<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetExternalLoanItem extends Model
{
    protected $fillable = ['asset_external_loan_id', 'asset_id'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(AssetExternalLoan::class, 'asset_external_loan_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
