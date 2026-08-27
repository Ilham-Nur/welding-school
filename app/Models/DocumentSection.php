<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['standard_id', 'parent_id', 'chapter_number', 'title', 'order_number'])]
class DocumentSection extends Model
{
    public function standard(): BelongsTo
    {
        return $this->belongsTo(DocumentStandard::class, 'standard_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_number');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'section_id');
    }

    public function linkedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_document_section')->withTimestamps();
    }
}
