<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBatch extends Model
{
    protected $fillable = [
        'training_program_id',
        'code',
        'name',
        'registration_deadline',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'registration_deadline' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
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
