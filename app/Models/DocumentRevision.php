<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_id',
    'document_code',
    'title',
    'description',
    'status',
    'section_ids',
    'revision_number',
    'effective_date',
    'original_file_path',
    'original_file_name',
    'original_file_type',
    'original_file_size',
    'preview_file_path',
    'conversion_status',
    'notes',
    'created_by',
])]
class DocumentRevision extends Model
{
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'section_ids' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
