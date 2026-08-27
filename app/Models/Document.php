<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'standard_id',
    'category_id',
    'section_id',
    'document_code',
    'title',
    'description',
    'revision_number',
    'effective_date',
    'status',
    'original_file_path',
    'original_file_name',
    'original_file_type',
    'original_file_size',
    'preview_file_path',
    'conversion_status',
    'created_by',
    'updated_by',
])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(DocumentStandard::class, 'standard_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(DocumentSection::class, 'section_id');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(DocumentSection::class, 'document_document_section')
            ->withTimestamps()
            ->orderBy('order_number');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class)->orderByDesc('revision_number');
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(DocumentRevision::class)->latestOfMany('revision_number');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(DocumentActivityLog::class)->latest();
    }

    public function currentRevisionNumber(): int
    {
        return (int) ($this->latestRevision?->revision_number ?? $this->revision_number);
    }

    public function currentOriginalPath(): string
    {
        return (string) ($this->latestRevision?->original_file_path ?? $this->original_file_path);
    }

    public function currentOriginalName(): string
    {
        return (string) ($this->latestRevision?->original_file_name ?? $this->original_file_name);
    }

    public function currentPreviewPath(): ?string
    {
        return $this->latestRevision?->preview_file_path ?? $this->preview_file_path;
    }
}
