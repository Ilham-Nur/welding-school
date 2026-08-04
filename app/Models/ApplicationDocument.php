<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    private const PREVIEWABLE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    protected $fillable = [
        'training_application_id',
        'document_type',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function trainingApplication(): BelongsTo
    {
        return $this->belongsTo(TrainingApplication::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png'], true);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isPreviewable(): bool
    {
        return in_array($this->mime_type, self::PREVIEWABLE_MIME_TYPES, true);
    }

    public function typeLabel(): string
    {
        return match ($this->document_type) {
            'id' => 'Kartu identitas',
            'photo' => 'Pas foto',
            'education' => 'Ijazah terakhir',
            'certificate' => 'Sertifikat pendukung',
            default => str_replace('_', ' ', ucfirst($this->document_type)),
        };
    }
}
