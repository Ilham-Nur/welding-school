<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionPracticeTask extends Model
{
    protected $fillable = ['batch_session_id', 'title', 'instructions', 'photo_required', 'effective_minutes', 'published_at', 'created_by'];

    protected function casts(): array
    {
        return ['photo_required' => 'boolean', 'published_at' => 'datetime', 'effective_minutes' => 'integer'];
    }

    public function session()
    {
        return $this->belongsTo(BatchSession::class, 'batch_session_id');
    }

    public function submissions()
    {
        return $this->hasMany(PracticeSubmission::class);
    }
}
