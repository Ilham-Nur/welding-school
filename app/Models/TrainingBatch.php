<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBatch extends Model
{
    public const OPERATIONAL_STATUSES = ['preparation' => 'Persiapan', 'ready' => 'Siap Dilaksanakan', 'running' => 'Berjalan', 'postponed' => 'Ditunda', 'cancelled' => 'Dibatalkan', 'completed' => 'Selesai (riwayat)'];

    protected $fillable = [
        'training_program_id',
        'code',
        'name',
        'registration_deadline',
        'start_date',
        'end_date',
        'capacity',
        'status',
        'operational_status',
        'training_budget_id',
        'budget_required',
        'materials_managed',
    ];

    protected static function booted(): void
    {
        static::created(function (self $batch): void {
            foreach (BatchChecklistItem::DEFAULTS as $key => $title) {
                $batch->checklistItems()->create(['key' => $key, 'title' => $title]);
            }
        });
    }

    public function materials(): HasMany
    {
        return $this->hasMany(BatchTrainingMaterial::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BatchAssignment::class);
    }

    public function trainingBudget(): BelongsTo
    {
        return $this->belongsTo(TrainingBudget::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(BatchSession::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(BatchChecklistItem::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(BatchActivityLog::class);
    }

    public function activePaidEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', 'active')
            ->whereHas('trainingApplication.invoice', fn (Builder $query) => $query->where('status', 'paid'));
    }

    public function scopeVisibleToOperator(Builder $query, User $user): void
    {
        if (! $user->can('operations.manage-all')) {
            $query->whereHas('assignments', fn (Builder $q) => $q->where('user_id', $user->id));
        }
    }

    protected function casts(): array
    {
        return [
            'registration_deadline' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
            'budget_required' => 'boolean',
            'materials_managed' => 'boolean',
        ];
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TrainingApplication::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
