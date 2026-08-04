<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\TrainingApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Services\Payments\MidtransPaymentProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TrainingApplicationController extends Controller
{
    public function __construct(
        private readonly MidtransPaymentProcessor $midtransPayments,
    ) {}

    public function current(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->trainingApplications()
            ->with([
                'trainingProgram',
                'trainingBatch',
                'invoice.payments',
                'enrollment',
            ])
            ->whereNot('status', 'draft')
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        foreach ($applications as $application) {
            $this->synchronizePendingPayment($application);
        }

        return response()->json([
            'application' => $applications->first()
                ? $this->payload($applications->first())
                : null,
            'applications' => $applications
                ->map(fn (TrainingApplication $application): array => $this->payload($application))
                ->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->user()->loadMissing('participantProfile');

        if (! $request->user()->hasCompleteParticipantProfile()) {
            throw ValidationException::withMessages([
                'profile' => 'Lengkapi Profil & Data Diri sebelum mengunggah dokumen pendaftaran.',
            ]);
        }

        $validated = $request->validate([
            'training_program_id' => ['required', 'integer', 'exists:training_programs,id'],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:-17 years'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'education' => ['required', 'string', 'max:50'],
            'experience' => ['nullable', 'string', 'max:100'],
            'emergency_name' => ['required', 'string', 'max:255'],
            'emergency_phone' => ['required', 'string', 'max:30'],
            'documents' => ['required', 'array'],
            'documents.id' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.photo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.education' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'documents.certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'birth_date.before' => 'Peserta harus berusia minimal 17 tahun.',
            'documents.id.required' => 'Dokumen identitas wajib diunggah.',
            'documents.photo.required' => 'Pas foto wajib diunggah.',
            'documents.education.required' => 'Ijazah terakhir wajib diunggah.',
            'documents.*.max' => 'Ukuran setiap dokumen maksimal 5 MB.',
        ]);

        $program = TrainingProgram::query()
            ->whereKey($validated['training_program_id'])
            ->where('status', 'active')
            ->first();
        $batch = TrainingBatch::query()
            ->whereKey($validated['training_batch_id'])
            ->where('training_program_id', $validated['training_program_id'])
            ->where('status', 'open')
            ->first();

        if (! $program || ! $batch) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Program atau batch sudah tidak tersedia untuk pendaftaran.',
            ]);
        }

        if ($batch->registration_deadline?->isPast()) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Batas pendaftaran batch ini sudah berakhir.',
            ]);
        }

        $occupied = $batch->applications()
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->count();

        if ($occupied >= $batch->capacity) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Kuota batch ini sudah penuh.',
            ]);
        }

        $hasSameProgramApplication = $request->user()
            ->trainingApplications()
            ->where('training_program_id', $program->id)
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->exists();

        if ($hasSameProgramApplication) {
            throw ValidationException::withMessages([
                'training_program_id' => 'Anda sudah memiliki pendaftaran aktif untuk program ini. Silakan pilih program lain.',
            ]);
        }

        $application = DB::transaction(function () use ($batch, $program, $request, $validated): TrainingApplication {
            $request->user()->update(['name' => trim($validated['full_name'])]);
            $request->user()->participantProfile()->updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'phone' => $validated['phone'],
                    'birth_place' => $validated['birth_place'],
                    'birth_date' => $validated['birth_date'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'last_education' => $validated['education'],
                    'emergency_contact_name' => $validated['emergency_name'],
                    'emergency_contact_phone' => $validated['emergency_phone'],
                ],
            );

            $snapshot = [
                'full_name' => trim($validated['full_name']),
                'email' => $request->user()->email,
                'phone' => $validated['phone'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'education' => $validated['education'],
                'experience' => $validated['experience'] ?? null,
                'emergency_name' => $validated['emergency_name'],
                'emergency_phone' => $validated['emergency_phone'],
            ];

            $application = TrainingApplication::query()->create([
                'registration_number' => $this->registrationNumber(),
                'user_id' => $request->user()->id,
                'training_program_id' => $program->id,
                'training_batch_id' => $batch->id,
                'status' => 'submitted',
                'personal_data_snapshot' => $snapshot,
                'submitted_at' => now(),
            ]);

            /** @var array<string, UploadedFile> $documents */
            $documents = $request->file('documents', []);
            foreach ($documents as $type => $file) {
                if (! $file) {
                    continue;
                }

                $path = $file->store("applications/{$application->id}");
                $application->documents()->create([
                    'document_type' => $type,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'pending',
                ]);
            }

            $application->statusHistories()->create([
                'from_status' => null,
                'to_status' => 'submitted',
                'changed_by' => $request->user()->id,
                'notes' => 'Pendaftaran dikirim oleh peserta.',
            ]);

            return $application;
        });

        return response()->json([
            'message' => 'Pendaftaran berhasil dikirim dan menunggu pemeriksaan admin.',
            'application' => $this->payload($application->load(['trainingProgram', 'trainingBatch', 'invoice', 'enrollment'])),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(TrainingApplication $application): array
    {
        return [
            'id' => $application->id,
            'registration_number' => $application->registration_number,
            'status' => $application->status,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'verification_notes' => $application->verification_notes,
            'training_program_id' => $application->training_program_id,
            'training_batch_id' => $application->training_batch_id,
            'program' => $application->trainingProgram?->only(['id', 'code', 'title']),
            'batch' => $application->trainingBatch?->only(['id', 'code', 'name']),
            'invoice' => $application->invoice
                ? InvoiceResource::make($application->invoice)->resolve()
                : null,
            'enrollment' => $application->enrollment
                ? [
                    'enrollment_number' => $application->enrollment->enrollment_number,
                    'status' => $application->enrollment->status,
                    'enrolled_at' => $application->enrollment->enrolled_at?->toIso8601String(),
                ]
                : null,
        ];
    }

    private function registrationNumber(): string
    {
        do {
            $number = 'WS-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (TrainingApplication::query()->where('registration_number', $number)->exists());

        return $number;
    }

    private function synchronizePendingPayment(TrainingApplication $application): void
    {
        $payment = $application->invoice?->payments
            ->where('gateway', 'midtrans')
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();

        if (! $payment) {
            return;
        }

        try {
            $this->midtransPayments->synchronize($payment);
            $application->load(['invoice', 'enrollment']);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
