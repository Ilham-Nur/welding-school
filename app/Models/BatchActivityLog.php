<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class BatchActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['training_batch_id', 'actor_id', 'action', 'description', 'before', 'after', 'reason', 'created_at'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function readableChanges(array $users, array $locations): array
    {
        $labels = ['title' => 'Nama sesi', 'name' => 'Nama batch', 'code' => 'Kode batch', 'role' => 'Peran', 'status' => 'Status', 'user_id' => 'Petugas', 'trainer_id' => 'Trainer', 'assigned_to' => 'PIC', 'location_id' => 'Lokasi', 'starts_at' => 'Mulai', 'ends_at' => 'Selesai', 'start_date' => 'Tanggal mulai', 'end_date' => 'Tanggal selesai', 'capacity' => 'Kapasitas', 'notes' => 'Catatan', 'operational_notes' => 'Catatan operasional'];
        $labels += ['session_id' => 'ID sesi', 'attendance_id' => 'ID absensi', 'attendance_latitude' => 'Lintang area', 'attendance_longitude' => 'Bujur area', 'attendance_radius_meters' => 'Radius area (m)', 'late_grace_minutes' => 'Toleransi terlambat (menit)', 'checked_in_at' => 'Absen masuk', 'checked_out_at' => 'Absen keluar', 'attended_minutes' => 'Kehadiran (menit)', 'review_notes' => 'Catatan pemeriksaan', 'participants' => 'Jumlah peserta', 'actual_ended_at' => 'Selesai aktual', 'distance_meters' => 'Jarak titik absensi (m)', 'accuracy_meters' => 'Akurasi lokasi (m)'];
        $statusLabels = match (true) {
            str_starts_with($this->action, 'attendance.') => SessionAttendance::STATUSES,
            str_starts_with($this->action, 'status.') => TrainingBatch::OPERATIONAL_STATUSES,
            str_starts_with($this->action, 'session.') => BatchSession::STATUSES,
            str_starts_with($this->action, 'checklist.') => BatchChecklistItem::STATUSES,
            default => ['draft' => 'Draft', 'open' => 'Dibuka', 'closed' => 'Ditutup', 'completed' => 'Selesai'],
        };
        $format = function ($key, $value) use ($users, $locations, $statusLabels): string {
            if ($value === null || $value === '') {
                return '—';
            }
            if (in_array($key, ['user_id', 'trainer_id', 'assigned_to'], true)) {
                return $users[$value] ?? 'Akun dihapus';
            }
            if ($key === 'location_id') {
                return $locations[$value] ?? 'Lokasi dihapus';
            }
            if ($key === 'role') {
                return BatchAssignment::ROLES[$value] ?? $value;
            }
            if ($key === 'status') {
                return $statusLabels[$value] ?? $value;
            }
            if (in_array($key, ['starts_at', 'ends_at', 'start_date', 'end_date'], true)) {
                return Carbon::parse($value)->setTimezone(config('app.timezone'))->format(in_array($key, ['start_date', 'end_date'], true) ? 'd M Y' : 'd M Y H:i');
            }

            return (string) $value;
        };
        $changes = [];
        foreach ($labels as $key => $label) {
            $before = $this->before[$key] ?? null;
            $after = $this->after[$key] ?? null;
            if ($before != $after) {
                $changes[] = ['label' => $label, 'before' => $format($key, $before), 'after' => $format($key, $after)];
            }
        }

        return $changes;
    }
}
