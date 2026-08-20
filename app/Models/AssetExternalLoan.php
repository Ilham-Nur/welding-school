<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetExternalLoan extends Model
{
    protected $fillable = ['number', 'asset_id', 'borrower_user_id', 'borrower_name', 'borrower_contact', 'organization', 'destination', 'purpose', 'loaned_at', 'due_at', 'returned_at', 'condition_out', 'condition_in', 'status', 'notes', 'created_by', 'returned_by'];

    protected function casts(): array
    {
        return ['loaned_at' => 'datetime', 'due_at' => 'datetime', 'returned_at' => 'datetime'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetExternalLoanItem::class);
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active' && $this->due_at?->isPast() === true;
    }
}
