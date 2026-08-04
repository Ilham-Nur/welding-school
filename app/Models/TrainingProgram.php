<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    protected $fillable = [
        'code',
        'title',
        'category',
        'duration_hours',
        'price',
        'status',
        'start_date',
    ];

    protected function casts(): array
    {
        return [
            'duration_hours' => 'integer',
            'price' => 'integer',
            'start_date' => 'date',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class);
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
