<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\EmployeePosition;
use App\Models\User;
use App\Services\Employees\EmployeeCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeCodeGenerator $codeGenerator) {}
    public function index(Request $request): View
    {
        $query = Employee::query()
            ->with(['user'])
            ->withCount(['educations', 'documents'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($q) use ($search): void {
                    $q->where('full_name', 'like', $search)
                        ->orWhere('employee_code', 'like', $search)
                        ->orWhere('identity_number', 'like', $search)
                        ->orWhere('position', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('emergency_contact_name', 'like', $search);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('employment_status', $request->string('status'));
            })
            ->when($request->filled('gender'), function ($query) use ($request): void {
                $query->where('gender', $request->string('gender'));
            })
            ->when($request->filled('position'), function ($query) use ($request): void {
                $query->where('position', 'like', '%'.trim((string) $request->string('position')).'%');
            });

        $employees = (clone $query)
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Employee::count(),
            'permanent' => Employee::where('employment_status', 'tetap')->count(),
            'contract' => Employee::where('employment_status', 'kontrak')->count(),
            'other' => Employee::whereNotIn('employment_status', ['tetap', 'kontrak'])->count(),
        ];

        return view('admin.employees.index', [
            'employees' => $employees,
            'stats' => $stats,
            'positions' => Employee::query()->whereNotNull('position')->where('position', '!=', '')->distinct()->pluck('position'),
        ]);
    }

    public function create(): View
    {
        $users = User::query()
            ->whereDoesntHave('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.employees.create', [
            'employee' => new Employee,
            'users' => $users,
            'positions' => EmployeePosition::query()->active()->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEmployee($request);
        $validated['employee_code'] = $this->codeGenerator->generate();

        if (! empty($validated['position'])) {
            $validated['position_id'] = EmployeePosition::where('name', $validated['position'])->value('id');
        }

        $photoPath = null;
        $originalPhotoPath = null;
        $lastEduPath = null;

        if ($request->hasFile('original_photo')) {
            $originalPhotoPath = 'storage/'.$request->file('original_photo')->store('employees/photos/originals', 'public');
            $validated['original_photo_path'] = $originalPhotoPath;
        }

        if ($request->hasFile('photo')) {
            $photoPath = 'storage/'.$request->file('photo')->store('employees/photos', 'public');
            $validated['photo_path'] = $photoPath;
            if (! $originalPhotoPath) {
                $validated['original_photo_path'] = $photoPath;
            }
        }

        if ($request->hasFile('last_education_file')) {
            $file = $request->file('last_education_file');
            $lastEduPath = 'storage/'.$file->store('employees/educations', 'public');
            $validated['last_education_file_path'] = $lastEduPath;
            $validated['last_education_file_name'] = $file->getClientOriginalName();
        }

        unset($validated['photo'], $validated['original_photo'], $validated['last_education_file'], $validated['documents'], $validated['educations']);

        try {
            $employee = DB::transaction(function () use ($validated, $request): Employee {
                $employee = Employee::query()->create($validated);

                // Handle repeatable educations array
                if ($request->has('educations') && is_array($request->input('educations'))) {
                    foreach ($request->input('educations') as $index => $eduInput) {
                        $institution = trim((string) ($eduInput['institution_name'] ?? ''));
                        if ($institution === '') {
                            continue;
                        }

                        $eduData = [
                            'education_level' => trim((string) ($eduInput['education_level'] ?? '')) ?: null,
                            'institution_name' => $institution,
                            'major' => trim((string) ($eduInput['major'] ?? '')) ?: null,
                            'start_year' => trim((string) ($eduInput['start_year'] ?? '')) ?: null,
                            'end_year' => trim((string) ($eduInput['end_year'] ?? '')) ?: null,
                            'is_current' => ! empty($eduInput['is_current']),
                            'grade' => trim((string) ($eduInput['grade'] ?? '')) ?: null,
                            'description' => trim((string) ($eduInput['description'] ?? '')) ?: null,
                        ];

                        if ($request->hasFile("educations.{$index}.file")) {
                            $eduFile = $request->file("educations.{$index}.file");
                            $eduData['file_path'] = 'storage/'.$eduFile->store('employees/educations', 'public');
                            $eduData['file_name'] = $eduFile->getClientOriginalName();
                            $eduData['mime_type'] = $eduFile->getClientMimeType();
                        }

                        $employee->educations()->create($eduData);
                    }
                } elseif ($request->filled('initial_education_institution')) {
                    $eduData = [
                        'education_level' => $request->string('initial_education_level')->toString() ?: null,
                        'institution_name' => $request->string('initial_education_institution')->toString(),
                        'major' => $request->string('initial_education_major')->toString() ?: null,
                        'start_year' => $request->string('initial_education_start_year')->toString() ?: null,
                        'end_year' => $request->string('initial_education_end_year')->toString() ?: null,
                        'is_current' => $request->boolean('initial_education_is_current'),
                        'grade' => $request->string('initial_education_grade')->toString() ?: null,
                        'description' => $request->string('initial_education_description')->toString() ?: null,
                    ];

                    if ($request->hasFile('initial_education_file')) {
                        $eduFile = $request->file('initial_education_file');
                        $eduData['file_path'] = 'storage/'.$eduFile->store('employees/educations', 'public');
                        $eduData['file_name'] = $eduFile->getClientOriginalName();
                        $eduData['mime_type'] = $eduFile->getClientMimeType();
                    }

                    $employee->educations()->create($eduData);
                }

                // Handle repeatable documents array
                if ($request->has('documents') && is_array($request->input('documents'))) {
                    foreach ($request->input('documents') as $index => $docInput) {
                        $label = trim((string) ($docInput['label'] ?? ''));
                        if ($label === '') {
                            continue;
                        }

                        if ($request->hasFile("documents.{$index}.file")) {
                            $docFile = $request->file("documents.{$index}.file");
                            $employee->documents()->create([
                                'document_label' => $label,
                                'file_path' => 'storage/'.$docFile->store('employees/documents', 'public'),
                                'file_name' => $docFile->getClientOriginalName(),
                                'mime_type' => $docFile->getClientMimeType(),
                                'file_size' => $docFile->getSize(),
                            ]);
                        }
                    }
                } elseif ($request->hasFile('initial_document_file') && $request->filled('initial_document_label')) {
                    $docFile = $request->file('initial_document_file');
                    $employee->documents()->create([
                        'document_label' => $request->string('initial_document_label')->toString(),
                        'file_path' => 'storage/'.$docFile->store('employees/documents', 'public'),
                        'file_name' => $docFile->getClientOriginalName(),
                        'mime_type' => $docFile->getClientMimeType(),
                        'file_size' => $docFile->getSize(),
                    ]);
                }

                return $employee;
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                $this->deleteFile($photoPath);
            }
            if ($lastEduPath) {
                $this->deleteFile($lastEduPath);
            }
            throw $exception;
        }

        return redirect()->route('admin.employees.show', $employee)
            ->with('success', 'Data karyawan baru berhasil ditambahkan.');
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'user',
            'educations' => fn ($q) => $q->latest('id'),
            'documents' => fn ($q) => $q->latest('id'),
        ]);

        return view('admin.employees.show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $users = User::query()
            ->where(function ($q) use ($employee): void {
                $q->whereDoesntHave('employee')
                    ->orWhere('id', $employee->user_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.employees.edit', [
            'employee' => $employee,
            'users' => $users,
            'positions' => EmployeePosition::query()->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $this->validateEmployee($request, $employee);

        if (! empty($validated['position'])) {
            $validated['position_id'] = EmployeePosition::where('name', $validated['position'])->value('id');
        } else {
            $validated['position_id'] = null;
        }

        $oldPhoto = $employee->photo_path;
        $oldOriginalPhoto = $employee->original_photo_path;
        $oldLastEdu = $employee->last_education_file_path;
        $newPhotoPath = null;
        $newOriginalPhotoPath = null;
        $newLastEduPath = null;

        if ($request->boolean('remove_photo')) {
            $validated['photo_path'] = null;
            $validated['original_photo_path'] = null;
        } else {
            if ($request->hasFile('original_photo')) {
                $newOriginalPhotoPath = 'storage/'.$request->file('original_photo')->store('employees/photos/originals', 'public');
                $validated['original_photo_path'] = $newOriginalPhotoPath;
            }

            if ($request->hasFile('photo')) {
                $newPhotoPath = 'storage/'.$request->file('photo')->store('employees/photos', 'public');
                $validated['photo_path'] = $newPhotoPath;
                if (! $newOriginalPhotoPath && ! $employee->original_photo_path) {
                    $validated['original_photo_path'] = $newPhotoPath;
                }
            }
        }

        if ($request->boolean('remove_last_education_file')) {
            $validated['last_education_file_path'] = null;
            $validated['last_education_file_name'] = null;
        } elseif ($request->hasFile('last_education_file')) {
            $file = $request->file('last_education_file');
            $newLastEduPath = 'storage/'.$file->store('employees/educations', 'public');
            $validated['last_education_file_path'] = $newLastEduPath;
            $validated['last_education_file_name'] = $file->getClientOriginalName();
        }

        unset($validated['photo'], $validated['original_photo'], $validated['last_education_file'], $validated['documents'], $validated['educations']);

        try {
            DB::transaction(function () use ($employee, $validated): void {
                $employee->update($validated);
            });

            if (($request->boolean('remove_photo') || $newPhotoPath) && $oldPhoto) {
                $this->deleteFile($oldPhoto);
            }

            if (($request->boolean('remove_photo') || $newOriginalPhotoPath) && $oldOriginalPhoto && $oldOriginalPhoto !== $oldPhoto) {
                $this->deleteFile($oldOriginalPhoto);
            }

            if (($request->boolean('remove_last_education_file') || $newLastEduPath) && $oldLastEdu) {
                $this->deleteFile($oldLastEdu);
            }
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                $this->deleteFile($newPhotoPath);
            }
            if ($newOriginalPhotoPath) {
                $this->deleteFile($newOriginalPhotoPath);
            }
            if ($newLastEduPath) {
                $this->deleteFile($newLastEduPath);
            }
            throw $exception;
        }

        return redirect()->route('admin.employees.show', $employee)
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->load(['educations', 'documents']);

        $filesToDelete = array_filter([
            $employee->photo_path,
            $employee->original_photo_path,
            $employee->last_education_file_path,
            ...$employee->educations->pluck('file_path')->all(),
            ...$employee->documents->pluck('file_path')->all(),
        ]);

        DB::transaction(function () use ($employee): void {
            $employee->delete();
        });

        foreach ($filesToDelete as $file) {
            $this->deleteFile($file);
        }

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data karyawan beserta berkas berhasil dihapus.');
    }

    public function storeEducation(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'education_level' => ['nullable', 'string', 'max:100'],
            'institution_name' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'start_year' => ['nullable', 'string', 'max:10'],
            'end_year' => ['nullable', 'string', 'max:10'],
            'is_current' => ['nullable', 'boolean'],
            'grade' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'education_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $validated['is_current'] = $request->boolean('is_current');

        if ($request->hasFile('education_file')) {
            $file = $request->file('education_file');
            $validated['file_path'] = 'storage/'.$file->store('employees/educations', 'public');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['mime_type'] = $file->getClientMimeType();
        }

        unset($validated['education_file']);

        $employee->educations()->create($validated);

        return back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    public function destroyEducation(Employee $employee, EmployeeEducation $education): RedirectResponse
    {
        abort_unless($education->employee_id === $employee->id, 404);

        $filePath = $education->file_path;
        $education->delete();

        if ($filePath) {
            $this->deleteFile($filePath);
        }

        return back()->with('success', 'Riwayat pendidikan berhasil dihapus.');
    }

    public function storeDocument(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'document_label' => ['required', 'string', 'max:255'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip', 'max:10240'],
        ]);

        $file = $request->file('document_file');
        $filePath = 'storage/'.$file->store('employees/documents', 'public');

        $employee->documents()->create([
            'document_label' => $validated['document_label'],
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Dokumen kepegawaian berhasil diunggah.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        abort_unless($document->employee_id === $employee->id, 404);

        $filePath = $document->file_path;
        $document->delete();

        if ($filePath) {
            $this->deleteFile($filePath);
        }

        return back()->with('success', 'Dokumen kepegawaian berhasil dihapus.');
    }

    public function previewDocument(Employee $employee, EmployeeDocument $document): Response
    {
        abort_unless($document->employee_id === $employee->id, 404);

        return $this->serveFile($document->file_path, $document->file_name, $document->mime_type, false);
    }

    public function downloadDocument(Employee $employee, EmployeeDocument $document): Response
    {
        abort_unless($document->employee_id === $employee->id, 404);

        return $this->serveFile($document->file_path, $document->file_name, $document->mime_type, true);
    }

    public function previewEducation(Employee $employee, EmployeeEducation $education): Response
    {
        abort_unless($education->employee_id === $employee->id, 404);
        abort_unless($education->file_path, 404, 'File ijazah tidak ditemukan.');

        return $this->serveFile($education->file_path, $education->file_name, $education->mime_type, false);
    }

    public function downloadEducation(Employee $employee, EmployeeEducation $education): Response
    {
        abort_unless($education->employee_id === $employee->id, 404);
        abort_unless($education->file_path, 404, 'File ijazah tidak ditemukan.');

        return $this->serveFile($education->file_path, $education->file_name, $education->mime_type, true);
    }

    public function previewLastEducation(Employee $employee): Response
    {
        abort_unless($employee->last_education_file_path, 404, 'File ijazah tidak ditemukan.');

        return $this->serveFile($employee->last_education_file_path, $employee->last_education_file_name, null, false);
    }

    public function downloadLastEducation(Employee $employee): Response
    {
        abort_unless($employee->last_education_file_path, 404, 'File ijazah tidak ditemukan.');

        return $this->serveFile($employee->last_education_file_path, $employee->last_education_file_name, null, true);
    }

    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $employeeId = $employee?->id;

        return $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id')->ignore($employeeId),
            ],
            'gender' => ['nullable', 'string', Rule::in(array_keys(Employee::GENDERS))],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'full_address' => ['nullable', 'string', 'max:2000'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:50'],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:50'],
            'important_information' => ['nullable', 'string', 'max:3000'],
            'last_education' => ['nullable', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'employment_status' => ['required', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'original_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'last_education_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'educations' => ['nullable', 'array'],
            'educations.*.institution_name' => ['nullable', 'string', 'max:255'],
            'educations.*.education_level' => ['nullable', 'string', 'max:100'],
            'educations.*.major' => ['nullable', 'string', 'max:255'],
            'educations.*.start_year' => ['nullable', 'string', 'max:10'],
            'educations.*.end_year' => ['nullable', 'string', 'max:10'],
            'educations.*.grade' => ['nullable', 'string', 'max:30'],
            'educations.*.description' => ['nullable', 'string', 'max:1000'],
            'educations.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documents' => ['nullable', 'array'],
            'documents.*.label' => ['nullable', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip', 'max:10240'],
        ]);
    }

    private function deleteFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        $cleanPath = preg_replace('/^storage\//', '', $relativePath);
        if (Storage::disk('public')->exists($cleanPath)) {
            Storage::disk('public')->delete($cleanPath);
        }
    }

    private function serveFile(string $relativePath, ?string $downloadName = null, ?string $mimeType = null, bool $isDownload = false): Response
    {
        $cleanPath = preg_replace('/^storage\//', '', $relativePath);
        $disk = Storage::disk('public');

        abort_unless($disk->exists($cleanPath), 404, 'Berkas tidak ditemukan.');

        $mimeType = $mimeType ?: $disk->mimeType($cleanPath) ?: 'application/octet-stream';
        $fileName = $downloadName ?: basename($cleanPath);

        if ($isDownload) {
            return $disk->download($cleanPath, $fileName, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }

        return $disk->response($cleanPath, $fileName, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
