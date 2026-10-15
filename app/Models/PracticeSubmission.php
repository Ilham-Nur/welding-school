<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeSubmission extends Model
{
    public const OUTCOMES = ['completed' => 'Selesai', 'in_progress' => 'Masih latihan', 'not_started' => 'Belum dikerjakan'];

    public const OBSTACLES = ['equipment' => 'Alat', 'materials' => 'Bahan', 'technique' => 'Teknik', 'other' => 'Lainnya'];

    public const STATUSES = ['draft' => 'Draf', 'submitted' => 'Menunggu pemeriksaan', 'revision' => 'Perlu perbaikan', 'accepted' => 'Diterima'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'version' => 'integer', 'verified_minutes' => 'integer'];
    }

    public function task()
    {
        return $this->belongsTo(SessionPracticeTask::class, 'session_practice_task_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function events()
    {
        return $this->hasMany(PracticeSubmissionEvent::class)->orderBy('id');
    }
}
