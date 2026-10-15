<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAttendance extends Model
{
    public const STATUSES = ['unrecorded' => 'Belum dicatat', 'pending' => 'Menunggu pemeriksaan', 'present' => 'Hadir', 'permission' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Tanpa keterangan'];

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'unrecorded', 'is_late' => false, 'attended_minutes' => 0];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime', 'checked_out_at' => 'datetime', 'reviewed_at' => 'datetime', 'is_late' => 'boolean', 'attended_minutes' => 'integer'];
    }

    public function session()
    {
        return $this->belongsTo(BatchSession::class, 'batch_session_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
