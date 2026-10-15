<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchSession extends Model
{
    public const TYPES = ['orientation' => 'Orientasi', 'theory' => 'Teori', 'practice' => 'Praktik'];

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Diterbitkan', 'cancelled' => 'Dibatalkan'];

    public const EXECUTION_STATUSES = ['scheduled' => 'Belum dimulai', 'running' => 'Sedang dilaksanakan', 'completed' => 'Selesai dilaksanakan'];

    protected $attributes = ['execution_status' => 'scheduled', 'late_grace_minutes' => 15];

    protected $fillable = ['training_batch_id', 'title', 'type', 'trainer_id', 'location_id', 'starts_at', 'ends_at', 'status', 'notes', 'execution_status', 'actual_started_at', 'actual_ended_at', 'execution_notes', 'attendance_latitude', 'attendance_longitude', 'attendance_radius_meters', 'late_grace_minutes', 'attendance_finalized_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'actual_started_at' => 'datetime', 'actual_ended_at' => 'datetime', 'attendance_finalized_at' => 'datetime'];
    }

    public function attendances()
    {
        return $this->hasMany(SessionAttendance::class);
    }

    public function practiceTask()
    {
        return $this->hasOne(SessionPracticeTask::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class);
    }
}
