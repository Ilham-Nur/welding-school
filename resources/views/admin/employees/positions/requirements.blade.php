@extends('layouts.admin')
@section('title', 'Persyaratan Jabatan: '.$position->name)
@section('eyebrow', 'HR Management')
@section('heading', 'Persyaratan Jabatan')
@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Persyaratan {{ $position->name }}</h1>
            <p>{{ $position->department ?: 'Departemen belum diisi' }} · {{ $position->description ?: 'Atur kriteria dan bukti yang harus dimiliki karyawan.' }}</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.employee-positions.index') }}">Kembali ke Master Jabatan</a>
            @can('employees.manage')
                <button class="button button--primary admin-button" type="button" data-modal-open="create-requirement">+ Tambah Persyaratan</button>
            @endcan
        </div>
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div><h2>Daftar Persyaratan ({{ $position->requirements->count() }})</h2><p>Persyaratan wajib menentukan kelengkapan dokumen karyawan.</p></div>
        </header>
        @if ($position->requirements->isEmpty())
            <div class="admin-empty"><h3>Belum ada persyaratan jabatan</h3><p>Tambahkan ijazah, sertifikat, bukti pengalaman, atau dokumen pendukung.</p></div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th>Persyaratan</th>
                        <th>Jenis</th>
                        <th>Kewajiban</th>
                        <th>Masa Berlaku</th>
                        <th>Verifikasi HR</th>
                        @can('employees.manage')<th>Aksi</th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($position->requirements as $requirement)
                        <tr>
                            <td>
                                <strong>{{ $requirement->name }}</strong>
                                @if ($requirement->description)
                                    <small style="display: block; white-space: pre-line; max-width: 360px; overflow-wrap: anywhere; color: #64748b">{{ $requirement->description }}</small>
                                @endif
                            </td>
                            <td>{{ \App\Models\EmployeePositionRequirement::CATEGORIES[$requirement->category] }}</td>
                            <td><x-admin.status-badge :status="$requirement->is_required ? 'pending' : 'draft'">{{ $requirement->is_required ? 'Wajib' : 'Opsional' }}</x-admin.status-badge></td>
                            <td>{{ $requirement->has_expiry ? 'Ada kedaluwarsa' : 'Tidak dibatasi' }}</td>
                            <td>{{ $requirement->requires_verification ? 'Perlu' : 'Tidak perlu' }}</td>
                            @can('employees.manage')
                                <td>
                                    <div class="admin-action-group">
                                        <button class="admin-action-button admin-action-button--edit" type="button" data-modal-open="edit-requirement-{{ $requirement->id }}" aria-label="Edit persyaratan {{ $requirement->name }}"><x-ui.icon name="edit" size="14" /> Edit</button>
                                        <form method="POST" action="{{ route('admin.employee-positions.requirements.destroy', [$position, $requirement]) }}" onsubmit="return confirm('Hapus persyaratan ini? Dokumen karyawan tetap tersimpan.')">
                                            @csrf @method('DELETE')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Hapus persyaratan {{ $requirement->name }}"><x-ui.icon name="trash" size="14" /> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>

    @can('employees.manage')
        <x-ui.modal id="create-requirement" title="Tambah Persyaratan" description="Tentukan kriteria dan bukti yang diperlukan untuk jabatan ini.">
            <form id="create-requirement-form" method="POST" action="{{ route('admin.employee-positions.requirements.store', $position) }}">
                @csrf
                @include('admin.employees.positions.requirement-fields', ['requirement' => null, 'formKey' => 'create'])
            </form>
            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                <button class="button button--primary admin-button" type="submit" form="create-requirement-form">Simpan Persyaratan</button>
            </x-slot:footer>
        </x-ui.modal>

        @foreach ($position->requirements as $requirement)
            <x-ui.modal id="edit-requirement-{{ $requirement->id }}" title="Edit Persyaratan: {{ $requirement->name }}" description="Perubahan kriteria meminta verifikasi ulang untuk dokumen yang memerlukan pemeriksaan HR.">
                <form id="edit-requirement-form-{{ $requirement->id }}" method="POST" action="{{ route('admin.employee-positions.requirements.update', [$position, $requirement]) }}">
                    @csrf @method('PUT')
                    @include('admin.employees.positions.requirement-fields', ['formKey' => 'edit-'.$requirement->id])
                </form>
                <x-slot:footer>
                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                    <button class="button button--primary admin-button" type="submit" form="edit-requirement-form-{{ $requirement->id }}">Simpan Perubahan</button>
                </x-slot:footer>
            </x-ui.modal>
        @endforeach

        @if ($errors->any())
            @php
                $failedForm = old('_requirement_form');
                $failedModal = $failedForm === 'create' ? 'create-requirement' : null;
                foreach ($position->requirements as $requirement) {
                    if ($failedForm === 'edit-'.$requirement->id) {
                        $failedModal = 'edit-requirement-'.$requirement->id;
                    }
                }
            @endphp
            @if ($failedModal)
                @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const modalId = @json($failedModal);
                            document.querySelector(`[data-modal-open="${modalId}"]`)?.click();
                        });
                    </script>
                @endpush
            @endif
        @endif
    @endcan
@endsection
