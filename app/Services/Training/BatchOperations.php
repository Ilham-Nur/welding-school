<?php

namespace App\Services\Training;

use App\Models\BatchAssignment;
use App\Models\BatchChecklistItem;
use App\Models\BatchSession;
use App\Models\Location;
use App\Models\TrainingBatch;
use App\Models\User;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatchOperations
{
    public function authorize(User $user, TrainingBatch $batch, string $permission = 'operations.view'): void
    {
        abort_unless($user->status === 'active' && $user->isInternal() && $user->can('operations.view') && $user->can($permission), 403);
        abort_unless($user->can('operations.manage-all') || $batch->assignments()->where('user_id', $user->id)->exists(), 403);
    }

    public function mutate(TrainingBatch $batch, User $actor, string $permission, Closure $callback): void
    {
        DB::transaction(function () use ($batch, $actor, $permission, $callback): void {
            $locked = TrainingBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $this->authorize($actor, $locked, $permission);
            if (in_array($locked->operational_status, ['cancelled', 'completed'], true) && $permission !== 'operations.status') {
                $this->invalid('batch', 'Batch ditutup. Buka kembali ke Persiapan sebelum mengubah operasional.');
            }
            $callback($locked);
        }, 3);
    }

    public function staff(int $id): User
    {
        $user = User::find($id);
        if (! $user || $user->status !== 'active' || ! $user->isInternal() || ! $user->can('operations.view')) {
            $this->invalid('user_id', 'Pilih akun internal aktif dengan izin melihat operasional batch.');
        }

        return $user;
    }

    public function readiness(TrainingBatch $batch): array
    {
        $assignments = $batch->assignments()->with('user')->get();
        $eligible = $assignments->filter(fn ($assignment) => $assignment->user && $assignment->user->status === 'active' && $assignment->user->isInternal() && $assignment->user->can('operations.view'));
        $sessions = $batch->sessions()->where('status', 'published')->with(['trainer', 'location'])->get();
        $paid = $batch->activePaidEnrollments()->count();
        $checks = [
            ['title' => 'Penanggung Jawab aktif ditentukan', 'done' => $eligible->contains('role', 'coordinator')],
            ['title' => 'Trainer utama aktif ditentukan', 'done' => $eligible->contains('role', 'lead_trainer')],
            ['title' => 'Peserta lunas tersedia dan sesuai kapasitas', 'done' => $paid > 0 && $paid <= $batch->capacity],
            ['title' => 'Jadwal diterbitkan, valid, dan lokasi sesi aktif', 'done' => $sessions->isNotEmpty() && $sessions->every(function ($session) use ($eligible, $batch): bool {
                return $eligible->whereIn('role', ['lead_trainer', 'assistant_trainer'])->contains('user_id', $session->trainer_id)
                    && $session->location?->is_active
                    && $session->starts_at->gte($batch->start_date->copy()->startOfDay())
                    && (! $batch->end_date || $session->ends_at->lte($batch->end_date->copy()->endOfDay()));
            })],
        ];
        $items = $batch->checklistItems()->where('is_required', true)->get();
        foreach (BatchChecklistItem::DEFAULTS as $key => $title) {
            $item = $items->firstWhere('key', $key);
            $checks[] = ['title' => $title, 'done' => $key === 'materials' && $batch->materials_managed
                ? app(MaterialAccess::class)->readiness($batch)['done']
                : $item?->status === 'completed'];
        }
        foreach ($items->whereNotIn('key', array_keys(BatchChecklistItem::DEFAULTS)) as $item) {
            $checks[] = ['title' => $item->title, 'done' => $item->status === 'completed'];
        }

        $steps = [
            ['team', 'Buka Tim pelaksana, lalu tugaskan Penanggung Jawab yang aktif.'],
            ['team', 'Buka Tim pelaksana, lalu tugaskan Trainer utama yang aktif.'],
            ['participants', 'Periksa daftar peserta: diperlukan peserta aktif lunas dan jumlahnya tidak boleh melebihi kapasitas batch.'],
            ['schedule', 'Buka Jadwal: terbitkan sesi, pilih Trainer yang ditugaskan dan lokasi aktif, lalu periksa tanggal dalam periode batch.'],
        ];
        foreach ($checks as $index => &$check) {
            [$check['tab'], $check['next_step']] = $steps[$index] ?? [
                str_contains(mb_strtolower($check['title']), 'materi') ? 'materials' : 'checklist',
                str_contains(mb_strtolower($check['title']), 'materi') ? 'Buka Materi, lengkapi file pendukung, lalu hubungkan materi ke setiap sesi Terbit.' : 'Buka Kesiapan, periksa kebutuhan, lalu konfirmasi checklist yang sudah terpenuhi.',
            ];
        }
        unset($check);

        return $checks;
    }

    public function log(TrainingBatch $batch, User $actor, string $action, string $description, ?array $before = null, ?array $after = null, ?string $reason = null): void
    {
        $batch->activityLogs()->create([
            'actor_id' => $actor->id, 'action' => $action, 'description' => $description,
            'before' => $before, 'after' => $after, 'reason' => $reason, 'created_at' => now(),
        ]);
    }

    public function invalidateReady(TrainingBatch $batch, User $actor): void
    {
        if ($batch->operational_status === 'ready') {
            $batch->update(['operational_status' => 'preparation']);
            $this->log($batch, $actor, 'status.invalidated', 'Kesiapan perlu diperiksa kembali setelah perubahan operasional.', ['status' => 'ready'], ['status' => 'preparation']);
        }
    }

    public function assign(TrainingBatch $batch, User $actor, array $data): void
    {
        $user = $this->staff((int) $data['user_id']);
        $existing = $batch->assignments()->where('user_id', $user->id)->first();
        if ($existing && str_contains($existing->role, 'trainer') && $data['role'] === 'coordinator'
            && $batch->sessions()->where('trainer_id', $user->id)->whereNot('status', 'cancelled')->exists()) {
            $this->invalid('role', 'Pindahkan atau batalkan sesi Trainer ini sebelum mengubah perannya.');
        }
        if (in_array($data['role'], ['coordinator', 'lead_trainer'], true)
            && $batch->assignments()->where('role', $data['role'])->where('user_id', '!=', $user->id)->exists()) {
            $this->invalid('role', 'Posisi ini sudah terisi. Akhiri atau ubah penugasan sebelumnya terlebih dahulu.');
        }
        $before = $existing?->toArray();
        $assignment = $batch->assignments()->updateOrCreate(['user_id' => $user->id], ['role' => $data['role']]);
        $this->log($batch, $actor, 'team.assigned', $user->name.' ditugaskan sebagai '.BatchAssignment::ROLES[$data['role']].'.', $before, $assignment->toArray(), $data['reason'] ?? null);
        $this->invalidateReady($batch, $actor);
    }

    public function saveSession(TrainingBatch $batch, User $actor, array $data, ?BatchSession $session = null): void
    {
        $session?->refresh();
        if ($session && $session->execution_status !== 'scheduled') {
            $this->invalid('session', 'Jadwal sesi yang sudah dimulai tidak dapat diubah. Riwayat pelaksanaan dan absensi harus dipertahankan.');
        }
        // Lock shared resources as well as the batch to serialize competing publications.
        $trainer = User::query()->lockForUpdate()->findOrFail($data['trainer_id']);
        $this->staff($trainer->id);
        $location = Location::query()->lockForUpdate()->findOrFail($data['location_id']);
        if (! $location->is_active) {
            $this->invalid('location_id', 'Lokasi harus aktif.');
        }
        if (! $batch->assignments()->where('user_id', $trainer->id)->whereIn('role', ['lead_trainer', 'assistant_trainer'])->exists()) {
            $this->invalid('trainer_id', 'Trainer harus ditugaskan pada batch ini.');
        }
        $starts = Carbon::parse($data['starts_at']);
        $ends = Carbon::parse($data['ends_at']);
        if ($starts->lt($batch->start_date->copy()->startOfDay()) || ($batch->end_date && $ends->gt($batch->end_date->copy()->endOfDay()))) {
            $this->invalid('starts_at', 'Jadwal harus berada dalam periode batch.');
        }
        if ($data['status'] === 'published') {
            $conflict = BatchSession::query()->where('status', 'published')
                ->whereHas('trainingBatch', fn ($q) => $q->whereNotIn('operational_status', ['cancelled', 'completed']))
                ->when($session, fn ($q) => $q->where('id', '!=', $session->id))
                ->where('starts_at', '<', $ends)->where('ends_at', '>', $starts)
                ->where(fn ($q) => $q->where('trainer_id', $trainer->id)->orWhere('location_id', $location->id)->orWhere('training_batch_id', $batch->id))
                ->lockForUpdate()->exists();
            if ($conflict) {
                $this->invalid('starts_at', 'Jadwal bentrok dengan sesi lain pada Trainer, lokasi, atau batch yang sama.');
            }
        }
        $before = $session?->toArray();
        $attributes = collect($data)->except('reason')->all();
        $attributes['starts_at'] = $starts;
        $attributes['ends_at'] = $ends;
        if ($session) {
            $session->update($attributes);
        } else {
            $session = $batch->sessions()->create($attributes);
        }
        $this->log($batch, $actor, 'session.saved', 'Jadwal '.$session->title.' disimpan ('.BatchSession::STATUSES[$session->status].').', $before, $session->toArray(), $data['reason'] ?? null);
        $this->invalidateReady($batch, $actor);
    }

    public function changeStatus(TrainingBatch $batch, User $actor, string $target, ?string $reason = null): void
    {
        $current = $batch->operational_status;
        $allowed = [
            'preparation' => ['ready', 'postponed', 'cancelled'],
            'ready' => ['running', 'postponed', 'cancelled'],
            'running' => ['postponed', 'cancelled'],
            'postponed' => ['cancelled'],
            'cancelled' => [], 'completed' => [],
        ];
        if ($target === 'preparation' && $current !== 'preparation' && $current !== 'completed') {
            abort_unless($actor->can('operations.reopen'), 403);
        } elseif (! in_array($target, $allowed[$current] ?? [], true)) {
            $this->invalid('operational_status', 'Perpindahan status tidak diizinkan.');
        }
        if (in_array($target, ['ready', 'running'], true)) {
            $missing = collect($this->readiness($batch))->where('done', false)->pluck('title');
            if ($missing->isNotEmpty()) {
                $this->invalid('operational_status', 'Belum siap: '.$missing->implode('; ').'.');
            }
        }
        if ($target === 'running' && $batch->start_date->isAfter(today())) {
            $this->invalid('operational_status', 'Pelatihan baru dapat dimulai pada tanggal mulai batch.');
        }
        $batch->update(['operational_status' => $target]);
        if (in_array($target, ['running', 'postponed', 'cancelled'], true)) {
            $batch->update(['status' => 'closed']);
        }
        if ($target === 'cancelled') {
            foreach ($batch->sessions()->whereNot('status', 'cancelled')->where('execution_status', '!=', 'completed')->get() as $session) {
                $before = $session->toArray();
                $session->update(['status' => 'cancelled']);
                $this->log($batch, $actor, 'session.cancelled', 'Sesi '.$session->title.' dibatalkan bersama batch.', $before, $session->toArray(), $reason);
            }
        }
        $this->log($batch, $actor, 'status.changed', 'Status berubah dari '.TrainingBatch::OPERATIONAL_STATUSES[$current].' ke '.TrainingBatch::OPERATIONAL_STATUSES[$target].'.', ['status' => $current], ['status' => $target], $reason);
    }

    public function invalid(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
