<section class="admin-panel" style="margin-bottom: 24px" id="persyaratan-jabatan">
    <header class="admin-panel__header">
        <div><h2>Kelengkapan Persyaratan Jabatan</h2><p>{{ $employee->requirementSummary() }}</p></div>
        @can('employees.manage')
            <a class="button button--outline admin-button" href="{{ route('admin.employees.edit', $employee) }}#dokumen-persyaratan">Lengkapi / Perbaiki di Edit Karyawan</a>
        @endcan
    </header>
    @if (! $employee->positionRelation)
        <div class="admin-empty"><p>Pilih jabatan melalui Edit Karyawan agar persyaratan muncul.</p></div>
    @elseif ($employee->positionRelation->requirements->isEmpty())
        <div class="admin-empty"><p>Jabatan ini belum memiliki persyaratan.</p><a href="{{ route('admin.employee-positions.requirements.index', $employee->positionRelation) }}">Lihat Master Persyaratan Jabatan</a></div>
    @else
        <x-ui.table class="admin-table-wrap">
            <thead><tr><th>Persyaratan</th><th>Status</th><th>Bukti Dokumen</th><th>Masa Berlaku</th>@can('employees.verify')<th>Aksi Verifikasi</th>@endcan</tr></thead>
            <tbody>
                @foreach ($employee->requirementChecklist() as $row)
                    <tr>
                        <td><strong>{{ $row['requirement']->name }}</strong><small style="display: block">{{ $row['requirement']->is_required ? 'Wajib' : 'Opsional' }}</small><p>{{ $row['requirement']->description }}</p></td>
                        <td><x-admin.status-badge :status="$row['status'] === 'valid' ? 'valid' : ($row['status'] === 'rejected' || $row['status'] === 'expired' ? 'rejected' : 'pending')">{{ \App\Models\EmployeePositionRequirement::STATUSES[$row['status']] }}</x-admin.status-badge>
                            @if ($row['document']?->verification_notes)<small style="display: block">Catatan: {{ $row['document']->verification_notes }}</small>@endif
                            @if ($row['document']?->verified_at)<small style="display: block">{{ $row['document']->verifier?->name ?? 'Pemeriksa' }} · {{ $row['document']->verified_at->format('d M Y H:i') }}</small>@endif
                        </td>
                        <td>
                            @if ($row['document'])
                                <a href="{{ route('admin.employees.documents.preview', [$employee, $row['document']]) }}" target="_blank">{{ $row['document']->file_name }}</a>
                            @else
                                Belum ada bukti
                            @endif
                        </td>
                        <td>{{ $row['document']?->expires_at?->format('d M Y') ?? ($row['requirement']->has_expiry ? 'Belum diisi' : 'Tidak dibatasi') }}</td>
                        @can('employees.verify')
                            <td>
                                @forelse ($employee->documents->where('requirement_id', $row['requirement']->id) as $evidence)
                                    <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="verify-document-{{ $evidence->id }}">Periksa{{ $employee->documents->where('requirement_id', $row['requirement']->id)->count() > 1 ? ' · '.$evidence->file_name : '' }}</button>
                                @empty
                                    <small>Menunggu unggahan</small>
                                @endforelse
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
        <div class="admin-panel__body"><p>Unggahan dan perbaikan bukti dilakukan melalui form tambah/edit karyawan. Pemeriksa dapat menyetujui atau menolak bukti melalui tombol Periksa.</p></div>
    @endif
</section>
@can('employees.verify')
    @foreach ($employee->documents as $evidence)
        @if ($evidence->requirement && $evidence->requirement->employee_position_id === $employee->position_id)
            <x-ui.modal id="verify-document-{{ $evidence->id }}" title="Periksa: {{ $evidence->requirement->name }}" size="large">
                <p>{{ $evidence->requirement->description }}</p>
                <p><strong>{{ $evidence->file_name }}</strong> · Berlaku sampai {{ $evidence->expires_at?->format('d M Y') ?? 'Tidak dibatasi' }}</p>
                <p><a href="{{ route('admin.employees.documents.preview', [$employee, $evidence]) }}" target="_blank">Buka Berkas</a> · <a href="{{ route('admin.employees.documents.download', [$employee, $evidence]) }}">Unduh Berkas</a></p>
                @if (strtolower(pathinfo($evidence->file_name, PATHINFO_EXTENSION)) === 'pdf')
                    <iframe title="Pratinjau {{ $evidence->file_name }}" src="{{ route('admin.employees.documents.preview', [$employee, $evidence]) }}" loading="lazy" style="width: 100%; height: 360px; border: 1px solid #e2e8f0"></iframe>
                @elseif (in_array(strtolower(pathinfo($evidence->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                    <img src="{{ route('admin.employees.documents.preview', [$employee, $evidence]) }}" loading="lazy" alt="Bukti {{ $evidence->requirement->name }}" style="max-width: 100%; max-height: 360px; object-fit: contain">
                @else
                    <p>Unduh berkas untuk memeriksa format dokumen ini.</p>
                @endif
                <form id="verify-document-form-{{ $evidence->id }}" method="POST" action="{{ route('admin.employees.documents.verify', [$employee, $evidence]) }}" style="display: grid; gap: 14px; margin-top: 16px">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_review_document" value="{{ $evidence->id }}">
                    <input type="hidden" name="review_token" value="{{ $evidence->reviewToken() }}">
                    @if ((string) old('_review_document') === (string) $evidence->id && $errors->any())
                        <div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                    @endif
                    <label class="ui-field admin-field"><span class="ui-field__label">Hasil Pemeriksaan</span><select name="verification_status" required data-review-result><option value="">Pilih keputusan</option><option value="approved">Setujui / Accept</option><option value="rejected">Tolak / Reject</option></select></label>
                    <label class="ui-field admin-field"><span class="ui-field__label">Catatan (wajib jika ditolak)</span><textarea name="verification_notes" rows="3" maxlength="2000" data-review-notes>{{ (string) old('_review_document') === (string) $evidence->id ? old('verification_notes') : '' }}</textarea></label>
                </form>
                <x-slot:footer>
                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                    <button class="button button--primary admin-button" type="submit" form="verify-document-form-{{ $evidence->id }}">Simpan Verifikasi</button>
                </x-slot:footer>
            </x-ui.modal>
        @endif
    @endforeach
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-review-result]').forEach(select => {
                    select.addEventListener('change', () => { select.form.querySelector('[data-review-notes]').required = select.value === 'rejected'; });
                });
                @if ($errors->any() && ctype_digit((string) old('_review_document', '')))
                    document.querySelector('[data-modal-open="verify-document-{{ (int) old('_review_document') }}"]')?.click();
                @endif
            });
        </script>
    @endpush
@endcan
