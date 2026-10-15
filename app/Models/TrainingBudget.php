<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBudget extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'submitted' => 'Diajukan', 'revision' => 'Revisi', 'approved' => 'Disetujui'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['inputs' => 'array', 'approved_snapshot' => 'array', 'approved_price' => 'integer', 'revision' => 'integer', 'approved_revision' => 'integer'];
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrainingBudgetEvent::class);
    }
}
