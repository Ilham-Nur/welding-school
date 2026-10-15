<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchChecklistItem extends Model
{
    public const DEFAULTS = ['materials' => 'Materi pelatihan siap', 'equipment' => 'Alat dan material praktik siap', 'venue' => 'Lokasi pelatihan siap'];

    public const STATUSES = ['pending' => 'Belum mulai', 'in_progress' => 'Dalam proses', 'completed' => 'Selesai', 'blocked' => 'Bermasalah'];

    protected $fillable = ['training_batch_id', 'key', 'title', 'is_required', 'status', 'assigned_to', 'notes', 'completed_by', 'completed_at'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
