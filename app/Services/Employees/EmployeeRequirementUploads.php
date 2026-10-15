<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmployeeRequirementUploads
{
    public function validate(Request $request, ?Employee $employee): void
    {
        $request->validate([
            'requirement_documents' => ['nullable', 'array', 'max:100'],
            'requirement_documents.*.source' => ['required', 'string', 'max:80'],
            'requirement_documents.*.expires_at' => ['nullable', 'date_format:Y-m-d'],
            'requirement_documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip', 'max:10240'],
        ]);
        $position = EmployeePosition::where('name', $request->input('position'))->first();
        $requirements = $position?->requirements->keyBy('id') ?? collect();
        foreach (($request->input('requirement_documents') ?? []) as $id => $input) {
            $key = "requirement_documents.$id";
            $requirement = $requirements->get($id);
            if (! $requirement) {
                throw ValidationException::withMessages([$key.'.source' => 'Persyaratan tidak sesuai dengan jabatan yang dipilih.']);
            }
            $source = $input['source'];
            $hasEvidence = false;
            if ($source === 'upload') {
                $request->validate([$key.'.file' => ['required', 'file']], [$key.'.file.required' => "Pilih berkas untuk {$requirement->name}."]);
                $hasEvidence = true;
            } elseif ($source === 'keep') {
                $hasEvidence = $employee?->documents()->where('requirement_id', $id)->exists() ?? false;
            } elseif ($source === 'last_education') {
                $hasEvidence = ! $request->boolean('remove_last_education_file') && ($request->hasFile('last_education_file') || $employee?->last_education_file_path);
                if (! $hasEvidence) {
                    throw ValidationException::withMessages([$key.'.source' => 'Unggah ijazah pada bagian Pendidikan Terakhir terlebih dahulu.']);
                }
            } elseif (preg_match('/^(document|education):(\d+)$/', $source, $matches)) {
                $record = $matches[1] === 'document' ? $employee?->documents()->find($matches[2]) : $employee?->educations()->find($matches[2]);
                if (! $record?->file_path || ! Storage::disk('public')->exists(preg_replace('/^storage\//', '', $record->file_path))) {
                    throw ValidationException::withMessages([$key.'.source' => 'Dokumen milik karyawan ini tidak ditemukan.']);
                }
                $hasEvidence = true;
            } elseif ($source !== 'none') {
                throw ValidationException::withMessages([$key.'.source' => 'Pilihan sumber dokumen tidak valid.']);
            }
            // Missing evidence is allowed so HR can save an incomplete employee record.
            if ($hasEvidence && $requirement->has_expiry) {
                $request->validate([$key.'.expires_at' => ['required', 'date_format:Y-m-d']], [$key.'.expires_at.required' => "Isi masa berlaku untuk {$requirement->name}."]);
            }
        }
    }

    public function save(Request $request, Employee $employee, array &$storedPaths): void
    {
        $requirements = $employee->positionRelation?->requirements->keyBy('id') ?? collect();
        foreach (($request->input('requirement_documents') ?? []) as $id => $input) {
            $requirement = $requirements->get($id);
            abort_unless($requirement, 422);
            $source = $input['source'];
            $current = $employee->documents()->where('requirement_id', $id)->latest('id')->first();
            $expires = $input['expires_at'] ?? null;
            if ($source === 'none') {
                $employee->documents()->where('requirement_id', $id)->update(['requirement_id' => null]);

                continue;
            }
            if ($source === 'keep') {
                if (! $current || $current->expires_at?->format('Y-m-d') === $expires) {
                    continue;
                }
                $data = $current->only(['file_path', 'file_name', 'mime_type', 'file_size', 'source_key']);
            } elseif ($source === 'upload') {
                $file = $request->file("requirement_documents.$id.file");
                $path = $file->store('employees/documents', 'public');
                if (! $path) {
                    throw new \RuntimeException('Berkas persyaratan gagal disimpan.');
                }
                $storedPaths[] = $path;
                $data = ['file_path' => 'storage/'.$path, 'file_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize()];
            } else {
                $data = $this->sourceData($employee, $source);
                if ($current && $current->file_path === $data['file_path'] && $current->expires_at?->format('Y-m-d') === $expires) {
                    continue;
                }
            }
            // Keep the previous file as an archive, but only the replacement fulfills this requirement.
            $employee->documents()->where('requirement_id', $id)->update(['requirement_id' => null]);
            $employee->documents()->create([
                ...$data,
                'document_label' => $requirement->name,
                'requirement_id' => $id,
                'expires_at' => $expires,
                'verification_status' => 'pending',
            ]);
        }
    }

    private function sourceData(Employee $employee, string $source): array
    {
        if ($source === 'last_education') {
            return ['file_path' => $employee->last_education_file_path, 'file_name' => $employee->last_education_file_name, 'source_key' => $source];
        }
        [$type, $id] = explode(':', $source);
        $record = $type === 'document' ? $employee->documents()->findOrFail($id) : $employee->educations()->findOrFail($id);

        return [
            'file_path' => $record->file_path,
            'file_name' => $record->file_name,
            'mime_type' => $record->mime_type,
            'file_size' => $type === 'document' ? $record->file_size : null,
            'source_key' => $type === 'document' ? $record->source_key : $source,
        ];
    }

    public function syncLastEducation(Employee $employee): void
    {
        $query = $employee->documents()->where('source_key', 'last_education');
        if (! $employee->last_education_file_path) {
            $query->delete();

            return;
        }
        foreach ($query->get() as $document) {
            if ($document->file_path !== $employee->last_education_file_path) {
                $document->update([
                    'file_path' => $employee->last_education_file_path,
                    'file_name' => $employee->last_education_file_name,
                    'mime_type' => null,
                    'file_size' => null,
                    'verification_status' => 'pending',
                    'verification_notes' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                    'requirement_version' => null,
                ]);
            }
        }
    }
}
