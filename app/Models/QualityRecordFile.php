<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quality_record_id',
    'label',
    'description',
    'file_path',
    'file_name',
    'file_type',
    'file_size',
    'created_by',
    'updated_by',
])]
class QualityRecordFile extends Model
{
    public function record(): BelongsTo
    {
        return $this->belongsTo(QualityRecord::class, 'quality_record_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canPreview(): bool
    {
        return in_array(strtolower((string) $this->file_type), ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'txt', 'csv'], true);
    }
}
