<section class="admin-panel" id="dokumen-persyaratan" data-employee-requirements>
    <header class="admin-panel__header">
        <div><h2>Dokumen Persyaratan Jabatan</h2><p>Pilih jabatan untuk melihat persyaratan. Data karyawan boleh disimpan dahulu meskipun bukti belum lengkap.</p></div>
    </header>
    <div class="admin-panel__body">
        <p data-no-position>Pilih Jabatan / Posisi pada bagian Informasi Kepegawaian.</p>
        @foreach ($positions as $positionOption)
            <fieldset data-requirement-position="{{ $positionOption->name }}" hidden disabled style="border: 0; padding: 0; margin: 0; min-width: 0">
                <legend class="sr-only">Persyaratan {{ $positionOption->name }}</legend>
                @forelse ($positionOption->requirements as $requirement)
                    @php
                        $evidence = $employee->exists ? $employee->documents->where('requirement_id', $requirement->id)->sortByDesc('id')->first() : null;
                        $field = 'requirement_documents.'.$requirement->id;
                        $source = old($field.'.source', 'keep');
                    @endphp
                    <div data-evidence-row data-has-evidence="{{ $evidence ? '1' : '0' }}" data-has-expiry="{{ $requirement->has_expiry ? '1' : '0' }}" style="padding: 16px 0; border-bottom: 1px solid #e2e8f0">
                        <strong>{{ $requirement->name }}</strong>
                        <x-admin.status-badge :status="$requirement->is_required ? 'pending' : 'draft'">{{ $requirement->is_required ? 'Wajib' : 'Opsional' }}</x-admin.status-badge>
                        <p>{{ $requirement->description }}</p>
                        @if ($evidence)
                            <p><a href="{{ route('admin.employees.documents.preview', [$employee, $evidence]) }}" target="_blank">{{ $evidence->file_name }}</a> · {{ \App\Models\EmployeePositionRequirement::STATUSES[$requirement->documentStatus($evidence)] }}</p>
                            @if ($evidence->verification_notes)<p style="color: #9a3412">Catatan pemeriksa: {{ $evidence->verification_notes }}</p>@endif
                        @else
                            <p>Belum ada bukti.</p>
                        @endif
                        <div class="admin-form-grid">
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Bukti untuk {{ $requirement->name }}</span>
                                <select name="requirement_documents[{{ $requirement->id }}][source]" data-evidence-source>
                                    <option value="keep" @selected($source === 'keep')>{{ $evidence ? 'Gunakan bukti saat ini' : 'Lengkapi nanti' }}</option>
                                    <option value="upload" @selected($source === 'upload')>Unggah berkas baru</option>
                                    <option value="last_education" @selected($source === 'last_education')>Pakai ijazah Pendidikan Terakhir</option>
                                    @if ($employee->exists)
                                        @foreach ($employee->educations->whereNotNull('file_path') as $education)
                                            <option value="education:{{ $education->id }}" @selected($source === 'education:'.$education->id)>Pendidikan: {{ $education->institution_name }} — {{ $education->file_name }}</option>
                                        @endforeach
                                        @foreach ($employee->documents->unique('file_path') as $existingDocument)
                                            <option value="document:{{ $existingDocument->id }}" @selected($source === 'document:'.$existingDocument->id)>Dokumen: {{ $existingDocument->document_label }} — {{ $existingDocument->file_name }}</option>
                                        @endforeach
                                        @if ($evidence)<option value="none" @selected($source === 'none')>Lepas bukti dari persyaratan (berkas tetap tersimpan)</option>@endif
                                    @endif
                                </select>
                                <small>Pilihan berkas hanya milik karyawan ini. Pilih dokumen yang sesuai dengan persyaratan.</small>
                            </label>
                            <div data-evidence-file-field hidden>
                                <x-ui.file-input :label="'Berkas '.$requirement->name" :name="'requirement_documents['.$requirement->id.'][file]'" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip" :max-size-mb="10" hint="Maksimum 10 MB. Penggantian bukti meminta pemeriksaan ulang jika verifikasi HR diperlukan." data-evidence-file disabled />
                            </div>
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Tanggal Kedaluwarsa {{ $requirement->has_expiry ? '(wajib jika ada bukti)' : '(opsional)' }}</span>
                                <input type="date" name="requirement_documents[{{ $requirement->id }}][expires_at]" value="{{ old($field.'.expires_at', $evidence?->expires_at?->format('Y-m-d')) }}" data-evidence-expiry>
                            </label>
                        </div>
                        <small data-evidence-education-hint hidden>Gunakan berkas pada bagian Pendidikan Terakhir di form ini; tidak perlu diunggah dua kali.</small>
                    </div>
                @empty
                    <p>Jabatan ini belum memiliki persyaratan dokumen.</p>
                @endforelse
            </fieldset>
        @endforeach
        <noscript>Aktifkan JavaScript untuk memilih dan mengunggah persyaratan sesuai jabatan.</noscript>
    </div>
</section>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('[data-employee-requirements]');
            const position = document.querySelector('select[name="position"]');
            if (!container || !position) return;
            const syncRow = (row) => {
                const source = row.querySelector('[data-evidence-source]').value;
                const upload = source === 'upload';
                const file = row.querySelector('[data-evidence-file]');
                row.querySelector('[data-evidence-file-field]').hidden = !upload;
                file.disabled = !upload;
                file.required = upload;
                const hasEvidence = source !== 'none' && (source !== 'keep' || row.dataset.hasEvidence === '1');
                const expiry = row.querySelector('[data-evidence-expiry]');
                expiry.disabled = !hasEvidence;
                expiry.required = hasEvidence && row.dataset.hasExpiry === '1';
                row.querySelector('[data-evidence-education-hint]').hidden = source !== 'last_education';
            };
            const syncPosition = () => {
                let found = false;
                container.querySelectorAll('[data-requirement-position]').forEach(fieldset => {
                    const active = fieldset.dataset.requirementPosition === position.value;
                    fieldset.hidden = !active;
                    fieldset.disabled = !active;
                    if (active) found = true;
                });
                container.querySelector('[data-no-position]').hidden = found;
            };
            container.querySelectorAll('[data-evidence-row]').forEach(row => {
                row.querySelector('[data-evidence-source]').addEventListener('change', () => syncRow(row));
                syncRow(row);
            });
            position.addEventListener('change', syncPosition);
            syncPosition();
        });
    </script>
@endpush
