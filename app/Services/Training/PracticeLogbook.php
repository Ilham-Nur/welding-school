<?php

namespace App\Services\Training;

use App\Models\BatchSession;
use App\Models\Enrollment;
use App\Models\PracticeSubmission;
use App\Models\SessionPracticeTask;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PracticeLogbook
{
    public function __construct(private SessionAttendanceService $access, private BatchOperations $operations) {}

    public function operator(User $user, TrainingBatch $batch, BatchSession $session, string $permission = 'operations.view'): void
    {
        $this->access->operator($user, $batch, $session, $permission);
    }

    public function participant(User $user, TrainingBatch $batch): Enrollment
    {
        $this->access->participant($user, $batch);

        return $batch->activePaidEnrollments()->where('user_id', $user->id)->firstOrFail();
    }

    public function task(TrainingBatch $batch, SessionPracticeTask $task): void
    {
        abort_unless($task->session->training_batch_id === $batch->id && $task->session->status === 'published' && $task->published_at, 404);
    }

    public function configure(User $actor, TrainingBatch $batch, BatchSession $session, array $data): void
    {
        DB::transaction(function () use ($actor, $batch, $session, $data) {
            $batch = TrainingBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $session = BatchSession::query()->lockForUpdate()->findOrFail($session->id);
            $this->operator($actor, $batch, $session, 'practice.manage');
            if (in_array($batch->operational_status, ['cancelled', 'completed', 'postponed'], true) || $session->status === 'cancelled') {
                $this->invalid('task', 'Aktivitas tidak dapat diubah pada sesi atau batch yang ditutup.');
            }
            $task = $session->practiceTask()->lockForUpdate()->first();
            if ($task?->submissions()->exists()) {
                $this->invalid('task', 'Instruksi terkunci setelah peserta mulai menyimpan hasil agar acuan pemeriksaan tetap sama.');
            }
            if ($data['effective_minutes'] !== null && ($session->type !== 'practice' || $data['effective_minutes'] > $this->sessionMinutes($session))) {
                $this->invalid('effective_minutes', 'Durasi efektif hanya untuk sesi praktik dan tidak boleh melebihi jadwal sesi.');
            }
            $before = $task?->toArray();
            $task = $session->practiceTask()->updateOrCreate([], [
                ...$data, 'created_by' => $task?->created_by ?? $actor->id,
                'published_at' => now(),
            ]);
            $this->operations->log($batch, $actor, 'practice.configured', 'Aktivitas '.$session->title.' disiapkan.', $before, $task->toArray());
        });
    }

    public function save(User $user, TrainingBatch $batch, SessionPracticeTask $task, array $data, ?UploadedFile $photo): PracticeSubmission
    {
        $storedPath = null;
        try {
            return DB::transaction(function () use ($user, $batch, $task, $data, $photo, &$storedPath) {
                $batch = TrainingBatch::query()->lockForUpdate()->findOrFail($batch->id);
                $session = BatchSession::query()->lockForUpdate()->findOrFail($task->batch_session_id);
                $task = SessionPracticeTask::query()->lockForUpdate()->findOrFail($task->id);
                $enrollment = $this->participant($user, $batch);
                $task->setRelation('session', $session);
                $this->task($batch, $task);
                if ($batch->operational_status !== 'running' || ! in_array($session->execution_status, ['running', 'completed'], true)) {
                    $this->invalid('task', 'Pengisian tersedia setelah sesi dimulai pada batch yang sedang berjalan.');
                }
                $record = $task->submissions()->where('enrollment_id', $enrollment->id)->lockForUpdate()->first();
                // A retry after a lost response must not create a second submission or lose the draft.
                if ($record && $record->last_request_id === $data['request_id']) {
                    return $record;
                }
                if (($record?->version ?? 0) !== (int) $data['version']) {
                    $this->invalid('version', 'Data berubah di tab lain atau sudah diperiksa. Muat ulang halaman sebelum mengubah hasil.');
                }
                if ($record && ! in_array($record->status, ['draft', 'revision'], true)) {
                    $this->invalid('task', 'Hasil sudah dikirim. Perubahan tersedia jika instruktur meminta perbaikan.');
                }
                $sending = $data['action'] === 'submit';
                $outcome = $data['outcome'] ?? null;
                $obstacle = $outcome !== 'completed' ? ($data['obstacle'] ?? null) : null;
                $notes = $data['notes'] ?? null;
                if ($sending && ! $outcome) {
                    $this->invalid('outcome', 'Pilih hasil praktik terlebih dahulu.');
                }
                if ($sending && $obstacle === 'other' && ! filled($notes)) {
                    $this->invalid('notes', 'Tuliskan keterangan singkat untuk kendala lainnya.');
                }
                if ($sending && $task->photo_required && $outcome !== 'not_started'
                    && ! $photo && (! $record?->photo_path || ! Storage::disk('local')->exists($record->photo_path))) {
                    $this->invalid('photo', 'Tambahkan satu foto hasil pekerjaan, bukan selfie absensi.');
                }
                $before = $record?->toArray();
                if ($photo) {
                    $storedPath = $photo->store('practice/'.$batch->id.'/'.$task->id, 'local');
                    if (! $storedPath) {
                        $this->invalid('photo', 'Foto belum tersimpan. Coba kirim kembali.');
                    }
                }
                $values = [
                    'outcome' => $outcome, 'obstacle' => $obstacle, 'notes' => $notes,
                    'status' => $sending ? 'submitted' : ($record?->status ?? 'draft'),
                    'version' => ($record?->version ?? 0) + 1, 'last_request_id' => $data['request_id'],
                ];
                if ($storedPath) {
                    $values += ['photo_path' => $storedPath, 'photo_mime' => $photo->getMimeType()];
                }
                if ($sending) {
                    $values += ['submitted_at' => now(), 'reviewed_by' => null, 'reviewed_at' => null, 'verified_minutes' => null];
                }
                $record = $task->submissions()->updateOrCreate(['enrollment_id' => $enrollment->id], $values);
                if ($sending) {
                    $this->event($record, $user, 'submitted');
                    $this->operations->log($batch, $user, 'practice.submitted', 'Hasil '.$task->title.' dikirim.', $before, $record->toArray());
                }

                return $record;
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $exception;
        }
    }

    public function review(User $actor, TrainingBatch $batch, BatchSession $session, PracticeSubmission $record, array $data): void
    {
        DB::transaction(function () use ($actor, $batch, $session, $record, $data) {
            $batch = TrainingBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $session = BatchSession::query()->lockForUpdate()->findOrFail($session->id);
            $this->operator($actor, $batch, $session, 'practice.review');
            $record = PracticeSubmission::query()->lockForUpdate()->findOrFail($record->id);
            abort_unless($record->task->batch_session_id === $session->id, 404);
            if (! in_array($batch->operational_status, ['running', 'completed'], true) || $session->status !== 'published') {
                $this->invalid('task', 'Pemeriksaan tidak tersedia pada sesi atau batch ini.');
            }
            if (! in_array($record->status, ['submitted', 'accepted'], true) || $record->version !== (int) $data['version']) {
                $this->invalid('version', 'Hasil telah berubah atau diperiksa. Muat ulang sebelum memeriksa kembali.');
            }
            if ($data['status'] === 'revision' && $batch->operational_status === 'completed') {
                $this->invalid('status', 'Batch selesai; buka kembali batch sebelum meminta peserta memperbaiki hasil.');
            }
            $minutes = $data['status'] === 'accepted' ? ($data['verified_minutes'] ?? null) : null;
            if ($minutes !== null) {
                $attendance = $session->attendances()->where('enrollment_id', $record->enrollment_id)->first();
                if ($session->type !== 'practice' || ! $session->attendance_finalized_at || $attendance?->status !== 'present'
                    || $record->outcome === 'not_started' || $minutes > min($attendance->attended_minutes, $this->sessionMinutes($session))) {
                    $this->invalid('verified_minutes', 'Jam praktik memerlukan absensi Hadir yang final, pekerjaan yang dilakukan, dan durasi tidak melebihi kehadiran maupun jadwal.');
                }
            }
            $before = $record->toArray();
            $record->update([
                'status' => $data['status'], 'review_notes' => $data['review_notes'] ?? null,
                'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'verified_minutes' => $minutes,
                'version' => $record->version + 1,
            ]);
            $this->event($record, $actor, $data['status']);
            $this->operations->log($batch, $actor, 'practice.'.$data['status'], 'Hasil '.$record->task->title.' diperiksa.', $before, $record->toArray(), $data['review_notes'] ?? null);
        });
    }

    public function photo(PracticeSubmission $record)
    {
        abort_unless($record->photo_path && Storage::disk('local')->exists($record->photo_path), 404);

        return Storage::disk('local')->response($record->photo_path, 'hasil-praktik', [
            'Content-Type' => $record->photo_mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function event(PracticeSubmission $record, User $actor, string $action): void
    {
        $record->events()->create(['actor_id' => $actor->id, 'action' => $action, 'snapshot' => $record->toArray(), 'created_at' => now()]);
    }

    private function sessionMinutes(BatchSession $session): int
    {
        return max(0, (int) floor($session->starts_at->diffInSeconds($session->ends_at, false) / 60));
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
