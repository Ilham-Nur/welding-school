<?php

namespace App\Services\Training;

use App\Models\BatchSession;
use App\Models\SessionAttendance;
use App\Models\TrainingBatch;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SessionAttendanceService
{
    public function __construct(private BatchOperations $operations) {}

    public function operator(User $user, TrainingBatch $batch, BatchSession $session, string $permission = 'operations.view'): void
    {
        abort_unless($session->training_batch_id === $batch->id, 404);
        $this->operations->authorize($user, $batch, $permission);
        if ($permission !== 'operations.view' && ! $user->can('operations.manage-all')) {
            abort_unless($batch->assignments()->where('user_id', $user->id)->whereIn('role', ['lead_trainer', 'assistant_trainer'])->exists(), 403);
        }
    }

    private function locked(TrainingBatch $batch, BatchSession $session, Closure $callback): mixed
    {
        return DB::transaction(function () use ($batch, $session, $callback) {
            $batch = TrainingBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $session = BatchSession::query()->lockForUpdate()->findOrFail($session->id);
            abort_unless($session->training_batch_id === $batch->id, 404);

            return $callback($batch, $session);
        });
    }

    private function openBatch(TrainingBatch $batch, BatchSession $session): void
    {
        if ($batch->operational_status !== 'running' || $session->status !== 'published') {
            $this->operations->invalid('session', 'Batch harus Berjalan dan sesi harus diterbitkan.');
        }
    }

    public function area(TrainingBatch $batch, BatchSession $session, User $actor, array $data): void
    {
        $this->locked($batch, $session, function ($batch, $session) use ($actor, $data) {
            $this->operator($actor, $batch, $session, 'attendance.area');
            if (in_array($batch->operational_status, ['cancelled', 'completed', 'postponed'], true) || $session->status === 'cancelled' || $session->execution_status !== 'scheduled') {
                $this->operations->invalid('session', 'Area hanya dapat diatur sebelum pelaksanaan pada sesi dan batch yang terbuka.');
            }
            $before = $session->only(array_keys($data));
            $session->update($data);
            $this->operations->log($batch, $actor, 'attendance.area', 'Area absensi sesi '.$session->title.' ditentukan.', $before, $session->only(array_keys($data)));
        });
    }

    public function start(TrainingBatch $batch, BatchSession $session, User $actor): void
    {
        $this->locked($batch, $session, function ($batch, $session) use ($actor) {
            $this->operator($actor, $batch, $session, 'operations.execution');
            $this->openBatch($batch, $session);
            if ($session->execution_status !== 'scheduled' || now()->lt($session->starts_at) || ! now()->isSameDay($session->starts_at)) {
                $this->operations->invalid('session', 'Mulai sesi pada hari jadwalnya, paling cepat pada waktu mulai. Sesi hanya dapat dimulai sekali.');
            }
            if ($session->attendance_latitude === null || $session->attendance_longitude === null || ! $session->attendance_radius_meters || ! $session->location?->is_active) {
                $this->operations->invalid('area', 'Tentukan titik dan radius absensi serta pastikan lokasi sesi aktif.');
            }
            $this->operations->staff($session->trainer_id);
            if (! $batch->assignments()->where('user_id', $session->trainer_id)->whereIn('role', ['lead_trainer', 'assistant_trainer'])->exists()) {
                $this->operations->invalid('trainer', 'Trainer sesi harus masih ditugaskan pada batch ini.');
            }
            if ($batch->sessions()->where('execution_status', 'running')->where('status', 'published')->exists()) {
                $this->operations->invalid('session', 'Selesaikan sesi yang sedang berjalan pada batch ini terlebih dahulu.');
            }
            $enrollments = $batch->activePaidEnrollments()->whereHas('user', fn ($q) => $q->where('status', 'active'))->lockForUpdate()->get();
            if ($enrollments->isEmpty()) {
                $this->operations->invalid('participants', 'Tidak ada peserta aktif lunas untuk sesi ini.');
            }
            foreach ($enrollments as $enrollment) {
                $session->attendances()->create(['enrollment_id' => $enrollment->id]);
            }
            $session->update(['execution_status' => 'running', 'actual_started_at' => now()]);
            $this->operations->log($batch, $actor, 'session.started', 'Sesi '.$session->title.' dimulai.', null, ['session_id' => $session->id, 'participants' => $enrollments->count()]);
        });
    }

    public function complete(TrainingBatch $batch, BatchSession $session, User $actor, string $notes): void
    {
        $this->locked($batch, $session, function ($batch, $session) use ($actor, $notes) {
            $this->operator($actor, $batch, $session, 'operations.execution');
            $this->openBatch($batch, $session);
            if ($session->execution_status !== 'running') {
                $this->operations->invalid('session', 'Hanya sesi yang sedang dilaksanakan dapat diselesaikan.');
            }
            if ($session->attendances()->whereIn('status', ['pending', 'present'])->whereNull('checked_out_at')->exists()) {
                $this->operations->invalid('attendance', 'Masih ada peserta yang belum absen keluar. Minta absen keluar atau periksa statusnya sebelum menutup sesi.');
            }
            $session->update(['execution_status' => 'completed', 'actual_ended_at' => now(), 'execution_notes' => $notes]);
            $this->operations->log($batch, $actor, 'session.completed', 'Sesi '.$session->title.' selesai dilaksanakan.', null, ['session_id' => $session->id, 'actual_ended_at' => now()->toIso8601String(), 'notes' => $notes]);
        });
    }

    public function review(TrainingBatch $batch, BatchSession $session, SessionAttendance $attendance, User $actor, array $data): void
    {
        $this->locked($batch, $session, function ($batch, $session) use ($attendance, $actor, $data) {
            abort_unless($attendance->batch_session_id === $session->id, 404);
            $permission = $session->attendance_finalized_at ? 'attendance.correct' : 'attendance.manage';
            $this->operator($actor, $batch, $session, $permission);
            if ($batch->operational_status === 'cancelled' || $session->status === 'cancelled' || $session->execution_status === 'scheduled'
                || (! $session->attendance_finalized_at && $batch->operational_status !== 'running')) {
                $this->operations->invalid('session', 'Pemeriksaan tidak tersedia untuk sesi atau batch ini.');
            }
            $attendance->refresh();
            if ($data['status'] === 'present' && (! $attendance->checked_in_at || ! $attendance->checked_out_at
                || ! Storage::disk('local')->exists($attendance->in_photo_path ?? '') || ! Storage::disk('local')->exists($attendance->out_photo_path ?? ''))) {
                $this->operations->invalid('status', 'Status Hadir memerlukan bukti selfie masuk dan keluar.');
            }
            $before = $attendance->toArray();
            $minutes = $data['status'] === 'present' ? (int) floor($attendance->checked_in_at->diffInSeconds($attendance->checked_out_at) / 60) : 0;
            $attendance->update(['status' => $data['status'], 'review_notes' => $data['review_notes'], 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'attended_minutes' => $minutes]);
            // A later attendance correction cannot leave practice hours above the new evidence.
            $affected = \App\Models\PracticeSubmission::whereHas('task', fn ($q) => $q->where('batch_session_id', $session->id))
                ->where('enrollment_id', $attendance->enrollment_id)->whereNotNull('verified_minutes')
                ->when($data['status'] === 'present', fn ($q) => $q->where('verified_minutes', '>', $minutes))->lockForUpdate()->get();
            foreach ($affected as $submission) {
                $previous = $submission->toArray();
                $submission->update(['verified_minutes' => null, 'version' => $submission->version + 1]);
                $submission->events()->create(['actor_id' => $actor->id, 'action' => 'hours_invalidated', 'snapshot' => $submission->toArray(), 'created_at' => now()]);
                $this->operations->log($batch, $actor, 'practice.hours-invalidated', 'Durasi praktik perlu diverifikasi ulang setelah koreksi absensi.', $previous, $submission->toArray(), $data['review_notes']);
            }
            $this->operations->log($batch, $actor, $session->attendance_finalized_at ? 'attendance.corrected' : 'attendance.reviewed', 'Absensi '.$attendance->enrollment->user->name.' pada '.$session->title.' diperiksa.', $before, $attendance->toArray(), $data['review_notes']);
        });
    }

    public function finalize(TrainingBatch $batch, BatchSession $session, User $actor): void
    {
        $this->locked($batch, $session, function ($batch, $session) use ($actor) {
            $this->operator($actor, $batch, $session, 'attendance.manage');
            $this->openBatch($batch, $session);
            if ($session->execution_status !== 'completed' || $session->attendance_finalized_at || ! $session->attendances()->exists()
                || $session->attendances()->whereIn('status', ['unrecorded', 'pending'])->exists()) {
                $this->operations->invalid('attendance', 'Selesaikan sesi dan periksa seluruh absensi sebelum finalisasi.');
            }
            $session->update(['attendance_finalized_at' => now()]);
            $this->operations->log($batch, $actor, 'attendance.finalized', 'Absensi sesi '.$session->title.' difinalisasi.', null, ['session_id' => $session->id]);
        });
    }

    public function participant(User $user, TrainingBatch $batch): void
    {
        app(MaterialAccess::class)->participant($user, $batch);
    }

    private function eligible(User $user, TrainingBatch $batch, BatchSession $session, string $kind): SessionAttendance
    {
        $this->participant($user, $batch);
        $this->openBatch($batch, $session);
        if ($session->execution_status !== 'running' || $session->attendance_finalized_at || ! $session->location?->is_active) {
            $this->operations->invalid('session', 'Absensi belum dibuka atau sesi sudah ditutup.');
        }
        $enrollment = $batch->activePaidEnrollments()->where('user_id', $user->id)->firstOrFail();
        $attendance = $session->attendances()->where('enrollment_id', $enrollment->id)->firstOrFail();
        if (($kind === 'in' && ($attendance->checked_in_at || $attendance->status !== 'unrecorded'))
            || ($kind === 'out' && (! $attendance->checked_in_at || $attendance->checked_out_at || $attendance->status !== 'pending'))) {
            $this->operations->invalid('attendance', 'Urutan absensi tidak valid atau absensi sudah tercatat/diperiksa.');
        }

        return $attendance;
    }

    public function challenge(TrainingBatch $batch, BatchSession $session, User $user, string $kind): array
    {
        return $this->locked($batch, $session, function ($batch, $session) use ($user, $kind) {
            $attendance = $this->eligible($user, $batch, $session, $kind);
            $token = Str::random(64);
            DB::table('attendance_challenges')->updateOrInsert(['session_attendance_id' => $attendance->id, 'kind' => $kind],
                ['token_hash' => hash('sha256', $token), 'expires_at' => now()->addSeconds(config('attendance.challenge_seconds')), 'consumed_at' => null]);

            return ['token' => $token, 'expires_in' => config('attendance.challenge_seconds')];
        });
    }

    public static function distance(float $latitude, float $longitude, float $centerLatitude, float $centerLongitude): float
    {
        $a = sin(deg2rad($latitude - $centerLatitude) / 2) ** 2
            + cos(deg2rad($centerLatitude)) * cos(deg2rad($latitude)) * sin(deg2rad($longitude - $centerLongitude) / 2) ** 2;

        return 6371000 * 2 * asin(sqrt(min(1, max(0, $a))));
    }

    public function capture(TrainingBatch $batch, BatchSession $session, User $user, array $data, UploadedFile $photo): void
    {
        $path = null;
        try {
            $this->locked($batch, $session, function ($batch, $session) use ($user, $data, $photo, &$path) {
                $kind = $data['kind'];
                $attendance = $this->eligible($user, $batch, $session, $kind);
                $challenge = DB::table('attendance_challenges')->where('session_attendance_id', $attendance->id)->where('kind', $kind)->lockForUpdate()->first();
                if (! $challenge || $challenge->consumed_at || now()->gte($challenge->expires_at) || ! hash_equals($challenge->token_hash, hash('sha256', $data['token']))) {
                    $this->operations->invalid('token', 'Waktu pengambilan selfie habis atau token tidak valid. Ambil foto ulang.');
                }
                $age = now()->getTimestampMs() - (float) $data['location_timestamp'];
                if ($age < -5000 || $age > config('attendance.location_max_age_seconds') * 1000) {
                    $this->operations->invalid('location', 'Lokasi sudah kedaluwarsa. Ambil lokasi dan foto ulang.');
                }
                $distance = self::distance((float) $data['latitude'], (float) $data['longitude'], (float) $session->attendance_latitude, (float) $session->attendance_longitude);
                if ($data['accuracy'] > config('attendance.max_accuracy_meters')) {
                    $this->operations->invalid('location', 'Lokasi belum akurat. Aktifkan lokasi presisi dan coba di area terbuka.');
                }
                // Include the reported uncertainty; do not expand the permitted area by GPS error.
                if ($distance + $data['accuracy'] > $session->attendance_radius_meters) {
                    $this->operations->invalid('location', 'Anda belum terdeteksi di dalam area absensi. Dekati titik absensi dan coba lagi.');
                }
                $size = @getimagesize($photo->getRealPath());
                if (! $size || $size[2] !== IMAGETYPE_JPEG || min($size[0], $size[1]) < 160 || max($size[0], $size[1]) > 1600) {
                    $this->operations->invalid('photo', 'Selfie harus berupa foto JPEG kamera, ukuran 160–1600 piksel.');
                }
                $image = @imagecreatefromjpeg($photo->getRealPath());
                if (! $image) {
                    $this->operations->invalid('photo', 'Selfie tidak dapat dibaca. Ambil foto ulang.');
                }
                ob_start();
                imagejpeg($image, null, 85);
                $jpeg = ob_get_clean();
                imagedestroy($image);
                $path = 'attendance/'.$session->id.'/'.$attendance->id.'/'.Str::uuid().'.jpg';
                if (! Storage::disk('local')->put($path, $jpeg)) {
                    throw new \RuntimeException('Selfie gagal disimpan.');
                }
                $attributes = [
                    'checked_'.$kind.'_at' => now(), $kind.'_photo_path' => $path,
                    $kind.'_latitude' => $data['latitude'], $kind.'_longitude' => $data['longitude'],
                    $kind.'_accuracy' => $data['accuracy'], $kind.'_distance' => round($distance, 2),
                ];
                if ($kind === 'in') {
                    $attributes['status'] = 'pending';
                    $attributes['is_late'] = now()->gt($session->starts_at->copy()->addMinutes($session->late_grace_minutes));
                }
                $attendance->update($attributes);
                DB::table('attendance_challenges')->where('id', $challenge->id)->update(['consumed_at' => now()]);
                $this->operations->log($batch, $user, 'attendance.selfie-'.$kind, 'Selfie absen '.($kind === 'in' ? 'masuk' : 'keluar').' pada '.$session->title.' tercatat.', null,
                    ['attendance_id' => $attendance->id, 'session_id' => $session->id, 'distance_meters' => round($distance, 2), 'accuracy_meters' => $data['accuracy']]);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function photo(SessionAttendance $attendance, string $kind)
    {
        $path = $attendance->{$kind.'_photo_path'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'selfie-'.$kind.'.jpg',
            ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'], 'inline');
    }
}
