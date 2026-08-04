<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\TrainingApplication;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainingApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = TrainingApplication::query()
            ->with(['user', 'trainingProgram', 'trainingBatch', 'verifier'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('registration_number', 'like', $search)
                        ->orWhereHas('user', function ($user) use ($search): void {
                            $user
                                ->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        })
                        ->orWhereHas('trainingProgram', fn ($program) => $program->where('title', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->whereNot('status', 'draft')
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.applications.index', compact('applications'));
    }

    public function show(TrainingApplication $application): View
    {
        $application->load([
            'user.participantProfile',
            'trainingProgram',
            'trainingBatch',
            'documents',
            'verifier',
            'statusHistories.actor',
        ]);

        $disk = Storage::disk('local');
        $application->documents->each(
            fn (ApplicationDocument $document) => $document->setAttribute(
                'file_exists',
                $disk->exists($document->storage_path),
            ),
        );

        return view('admin.applications.show', compact('application'));
    }

    public function previewDocument(
        TrainingApplication $application,
        ApplicationDocument $document,
    ): StreamedResponse {
        $this->ensureDocumentBelongsToApplication($application, $document);
        abort_unless($document->isPreviewable(), 415, 'Tipe file ini tidak mendukung preview.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404, 'File dokumen tidak ditemukan.');

        return $disk->response(
            $document->storage_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ],
        );
    }

    public function downloadDocument(
        TrainingApplication $application,
        ApplicationDocument $document,
    ): StreamedResponse {
        $this->ensureDocumentBelongsToApplication($application, $document);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->storage_path), 404, 'File dokumen tidak ditemukan.');

        return $disk->download(
            $document->storage_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function review(Request $request, TrainingApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'notes' => [
                Rule::requiredIf($request->string('decision')->toString() === 'rejected'),
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'notes.required' => 'Alasan penolakan wajib dituliskan.',
        ]);

        if (! in_array($application->status, ['submitted', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Pendaftaran ini sudah diproses sebelumnya.',
            ]);
        }

        DB::transaction(function () use ($application, $request, $validated): void {
            $fromStatus = $application->status;

            $application->update([
                'status' => $validated['decision'],
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_notes' => $validated['notes'] ?? null,
            ]);

            $application->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $validated['decision'],
                'changed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $application->user->notify(
            new ApplicationStatusUpdatedNotification($application->fresh(['trainingProgram'])),
        );

        $message = $validated['decision'] === 'approved'
            ? 'Pendaftaran berhasil disetujui dan peserta telah diberi notifikasi.'
            : 'Pendaftaran ditolak dan alasan telah dikirim kepada peserta.';

        return to_route('admin.applications.show', $application)
            ->with('success', $message);
    }

    private function ensureDocumentBelongsToApplication(
        TrainingApplication $application,
        ApplicationDocument $document,
    ): void {
        abort_unless(
            $document->training_application_id === $application->id,
            404,
        );
    }
}
