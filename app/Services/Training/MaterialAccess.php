<?php

namespace App\Services\Training;

use App\Models\TrainingBatch;
use App\Models\BatchSession;
use App\Models\BatchTrainingMaterial;
use App\Models\TrainingMaterialFile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class MaterialAccess
{
    public function internal(User $user, string $permission): void
    {
        abort_unless($user->status === 'active' && $user->isInternal() && $user->can($permission), 403);
    }

    public function participant(User $user, TrainingBatch $batch): void
    {
        abort_unless($user->status === 'active' && $batch->operational_status !== 'cancelled'
            && $batch->activePaidEnrollments()->where('user_id', $user->id)->exists(), 403);
    }

    public function readiness(TrainingBatch $batch): array
    {
        $items = $batch->materials()->with(['revision.files', 'session'])->get();
        $sessions = $batch->sessions()->where('status', 'published')->pluck('id');
        $linked = $items->filter(fn ($item) => $item->revision->published_at && $item->revision->files->isNotEmpty()
            && $item->revision->files->every(fn ($file) => Storage::disk('local')->exists($file->path))
            && $item->session?->status === 'published');
        $missing = $sessions->diff($linked->pluck('batch_session_id'));

        return ['done' => $sessions->isNotEmpty() && $missing->isEmpty(), 'count' => $items->count(), 'linked' => $linked->count(), 'missing' => $missing->count(), 'sessions' => $sessions->count()];
    }

    public function publicationChecks(BatchTrainingMaterial $item, ?BatchSession $session): array
    {
        $files = $item->revision->files->where('audience', 'participant');

        return [
            ['key' => 'session', 'done' => $session?->status === 'published',
                'message' => ! $session ? 'Pilih sesi untuk materi ini melalui Atur materi.' : 'Sesi masih '.(BatchSession::STATUSES[$session->status] ?? $session->status).'. Terbitkan sesi melalui Jadwal terlebih dahulu.',
                'label' => 'Terhubung ke sesi berstatus Terbit', 'tab' => 'schedule'],
            ['key' => 'participant-files', 'done' => $files->isNotEmpty(),
                'message' => 'Belum ada file berakses Peserta. Minta pengelola membuat revisi dengan file peserta, terbitkan, lalu pasang revisi tersebut pada batch.',
                'label' => 'Memiliki file berakses Peserta', 'tab' => 'library'],
            ...($files->isNotEmpty() ? [['key' => 'available-files', 'done' => $files->every(fn ($file) => Storage::disk('local')->exists($file->path)),
                'message' => 'File peserta belum tersedia di penyimpanan. Minta pengelola memulihkan file atau memasang revisi dengan file yang tersedia.',
                'label' => 'File peserta tersedia untuk dibuka', 'tab' => 'library']] : []),
            ['key' => 'published', 'done' => (bool) $item->revision->published_at,
                'message' => 'Revisi materi belum diterbitkan. Terbitkan melalui library sebelum membagikannya.',
                'label' => 'Revisi materi sudah diterbitkan', 'tab' => 'library'],
        ];
    }

    public function file(TrainingMaterialFile $file, bool $preview = false)
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($preview) {
            abort_unless($file->canPreview(), 404);

            return Storage::disk('local')->response($file->path, $file->original_name,
                $headers + ['Content-Type' => TrainingMaterialFile::TYPES[$file->extension]], 'inline');
        }

        return Storage::disk('local')->download($file->path, $file->original_name, $headers);
    }
}
