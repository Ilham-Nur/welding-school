@extends('layouts.admin')

@section('title', 'Profil Karyawan: ' . $employee->full_name)
@section('eyebrow', 'Kepegawaian & SDM')
@section('heading', $employee->full_name)

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Detail Profil Karyawan</h1>
            <p>Informasi identitas resmi, penempatan kerja, dan berkas kepegawaian.</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.employees.index') }}">← Kembali ke daftar</a>
        </div>
    </section>

    <div class="admin-detail-grid">
        <!-- Kolom Kiri: Biodata Lengkap, Pendidikan, dan Dokumen -->
        <div style="display: grid; gap: 24px">
            <!-- 1. Biodata & Data Pribadi -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Biodata & Data Pribadi</h2>
                        <p>Informasi identitas lengkap dan data kependudukan.</p>
                    </div>
                </header>
                <dl class="admin-description-list">
                    <div>
                        <dt>Nama Lengkap</dt>
                        <dd>{{ $employee->full_name }}</dd>
                    </div>
                    <div>
                        <dt>Nomor NIK / KTP</dt>
                        <dd>{{ $employee->identity_number ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Jenis Kelamin</dt>
                        <dd>{{ $employee->gender ? $employee->genderLabel() : '-' }}</dd>
                    </div>
                    <div>
                        <dt>Tempat, Tanggal Lahir</dt>
                        <dd>
                            {{ $employee->birth_place ?: '-' }},
                            {{ $employee->birth_date?->translatedFormat('d F Y') ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt>Agama</dt>
                        <dd>{{ $employee->religion ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Status Pernikahan</dt>
                        <dd>{{ $employee->marital_status ? $employee->maritalStatusLabel() : '-' }}</dd>
                    </div>
                    <div>
                        <dt>Kewarganegaraan</dt>
                        <dd>{{ $employee->nationality ?: 'Indonesia' }}</dd>
                    </div>
                    <div>
                        <dt>Nomor Telepon</dt>
                        <dd>{{ $employee->phone ?: '-' }}</dd>
                    </div>
                    <div class="admin-field--full">
                        <dt>Alamat Lengkap</dt>
                        <dd>{{ $employee->full_address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Kontak Darurat</dt>
                        <dd>{{ $employee->emergency_contact_name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Telepon Darurat</dt>
                        <dd>{{ $employee->emergency_contact_phone ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>BPJS Ketenagakerjaan</dt>
                        <dd>{{ $employee->bpjs_ketenagakerjaan_number ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>BPJS Kesehatan</dt>
                        <dd>{{ $employee->bpjs_kesehatan_number ?: '-' }}</dd>
                    </div>
                    <div class="admin-field--full">
                        <dt>Informasi Penting / Catatan</dt>
                        <dd>{{ $employee->important_information ?: '-' }}</dd>
                    </div>
                </dl>
            </section>

            <!-- 2. Pendidikan Terakhir -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Pendidikan Terakhir</h2>
                        <p>Informasi dan berkas ijazah terakhir.</p>
                    </div>
                </header>
                <dl class="admin-description-list">
                    <div>
                        <dt>Jenjang Pendidikan</dt>
                        <dd>{{ $employee->last_education ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt>Berkas Ijazah Terakhir</dt>
                        <dd>
                            @if ($employee->last_education_file_path)
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <span style="font-weight: 500">{{ $employee->last_education_file_name ?: basename($employee->last_education_file_path) }}</span>
                                    <a class="button button--outline admin-button" style="padding: 4px 10px; font-size: 12px" href="{{ route('admin.employees.last-education.preview', $employee) }}" target="_blank">
                                        <x-ui.icon name="eye" size="12" /> Preview
                                    </a>
                                    <a class="button button--outline admin-button" style="padding: 4px 10px; font-size: 12px" href="{{ route('admin.employees.last-education.download', $employee) }}">
                                        <x-ui.icon name="download" size="12" /> Download
                                    </a>
                                </div>
                            @else
                                <span style="color: #94a3b8">Belum ada berkas ijazah diunggah</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- 3. Riwayat Pendidikan -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Riwayat Pendidikan (Formal & Non-Formal)</h2>
                        <p>Daftar seluruh jenjang sekolah, universitas, atau kursus yang pernah ditempuh.</p>
                    </div>
                    @can('employees.manage')
                        <button class="button button--outline admin-button" type="button" data-modal-open="create-education">
                            + Tambah Pendidikan
                        </button>
                    @endcan
                </header>

                @if ($employee->educations->isEmpty())
                    <div class="admin-empty">
                        <span aria-hidden="true"><x-ui.icon name="book-open" size="24" /></span>
                        <h3>Belum ada riwayat pendidikan</h3>
                        <p>Tambahkan riwayat jenjang sekolah, universitas, atau pelatihan karyawan.</p>
                    </div>
                @else
                    <x-ui.table class="admin-table-wrap">
                        <thead>
                            <tr>
                                <th>Jenjang & Institusi</th>
                                <th>Jurusan</th>
                                <th>Periode</th>
                                <th>Nilai / IPK</th>
                                <th>Berkas Ijazah</th>
                                @can('employees.manage')
                                    <th>Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employee->educations as $edu)
                                <tr>
                                    <td>
                                        <strong>{{ $edu->institution_name }}</strong>
                                        <small style="display: block; color: #64748b">
                                            {{ $edu->education_level ?: 'Pendidikan' }}
                                            @if ($edu->is_current)
                                                <span class="admin-badge admin-badge--neutral" style="background: #e0f2fe; color: #0369a1; font-size: 12px; padding: 1px 5px; border-radius: 3px;">Sedang Berjalan</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>{{ $edu->major ?: '-' }}</td>
                                    <td>
                                        {{ $edu->start_year ?: '?' }} - {{ $edu->is_current ? 'Sekarang' : ($edu->end_year ?: '?') }}
                                    </td>
                                    <td>{{ $edu->grade ?: '-' }}</td>
                                    <td>
                                        @if ($edu->file_path)
                                            <div style="display: flex; gap: 6px">
                                                <a class="button button--outline admin-button" style="padding: 3px 8px; font-size: 12px" href="{{ route('admin.employees.educations.preview', [$employee, $edu]) }}" target="_blank" title="Preview">
                                                    <x-ui.icon name="eye" size="12" />
                                                </a>
                                                <a class="button button--outline admin-button" style="padding: 3px 8px; font-size: 12px" href="{{ route('admin.employees.educations.download', [$employee, $edu]) }}" title="Download">
                                                    <x-ui.icon name="download" size="12" />
                                                </a>
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 12px">Tidak ada berkas</span>
                                        @endif
                                    </td>
                                    @can('employees.manage')
                                        <td>
                                            <form method="POST" action="{{ route('admin.employees.educations.destroy', [$employee, $edu]) }}" onsubmit="return confirm('Hapus riwayat pendidikan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="admin-action-button admin-action-button--delete" type="submit" title="Hapus">
                                                    <x-ui.icon name="trash" size="13" /> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </section>

            <!-- 4. Dokumen Digital Kepegawaian -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Dokumen Digital Kepegawaian</h2>
                        <p>KTP, NPWP, Kontrak Kerja, Sertifikat Welder / BNSP, SKCK, dan berkas lainnya.</p>
                    </div>
                    @can('employees.manage')
                        <button class="button button--outline admin-button" type="button" data-modal-open="create-document">
                            + Unggah Dokumen
                        </button>
                    @endcan
                </header>

                @if ($employee->documents->isEmpty())
                    <div class="admin-empty">
                        <span aria-hidden="true"><x-ui.icon name="file" size="24" /></span>
                        <h3>Belum ada dokumen diunggah</h3>
                        <p>Arsipkan berkas digital resmi karyawan seperti KTP, sertifikat, atau kontrak kerja.</p>
                    </div>
                @else
                    <x-ui.table class="admin-table-wrap">
                        <thead>
                            <tr>
                                <th>Label Dokumen</th>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Tgl Unggah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employee->documents as $doc)
                                <tr>
                                    <td>
                                        <strong style="color: #0f172a">{{ $doc->document_label }}</strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px">
                                            <x-ui.icon name="file" size="15" />
                                            <span>{{ $doc->file_name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $doc->formattedFileSize() }}</td>
                                    <td>{{ $doc->created_at?->translatedFormat('d M Y, H:i') }}</td>
                                    <td>
                                        <div class="admin-action-group">
                                            <a class="button button--outline admin-button" style="padding: 3px 8px; font-size: 12px" href="{{ route('admin.employees.documents.preview', [$employee, $doc]) }}" target="_blank" title="Preview">
                                                <x-ui.icon name="eye" size="12" /> Preview
                                            </a>
                                            <a class="button button--outline admin-button" style="padding: 3px 8px; font-size: 12px" href="{{ route('admin.employees.documents.download', [$employee, $doc]) }}" title="Download">
                                                <x-ui.icon name="download" size="12" /> Unduh
                                            </a>
                                            @can('employees.manage')
                                                <form method="POST" action="{{ route('admin.employees.documents.destroy', [$employee, $doc]) }}" onsubmit="return confirm('Hapus dokumen {{ $doc->document_label }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="admin-action-button admin-action-button--delete" type="submit" title="Hapus">
                                                        <x-ui.icon name="trash" size="13" /> Hapus
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </section>
        </div>

        <!-- Kolom Kanan (Sidebar Profil & Info Cepat Karyawan) -->
        <div style="display: grid; gap: 20px; align-content: start;">
            <!-- Kartu Foto & Identitas Utama -->
            <section class="admin-panel">
                <div class="admin-panel__body" style="padding: 24px; text-align: center;">
                    <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                        @if ($employee->photo_path)
                            <img
                                src="{{ $employee->photoUrl() }}"
                                alt="{{ $employee->full_name }}"
                                style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"
                            >
                        @else
                            <div style="width: 120px; height: 120px; border-radius: 50%; background: #e2e8f0; color: #334155; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 40px; border: 4px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                {{ strtoupper(substr($employee->full_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <h2 style="font-size: 18px; margin: 0 0 6px; color: #0f172a; font-weight: 700;">{{ $employee->full_name }}</h2>
                    <p style="color: #64748b; font-size: 13px; margin: 0 0 12px;">{{ $employee->position ?: 'Staff Karyawan' }}</p>

                    <div style="margin-bottom: 18px;">
                        <x-admin.status-badge :status="$employee->employment_status">
                            {{ $employee->employmentStatusLabel() }}
                        </x-admin.status-badge>
                    </div>

                    <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; text-align: left; display: grid; gap: 10px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b;">Kode Karyawan</span>
                            <span class="admin-badge admin-badge--neutral" style="font-family: monospace; font-size: 12px; font-weight: 700;">
                                {{ $employee->employee_code ?: '-' }}
                            </span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b;">Tgl Bergabung</span>
                            <strong style="color: #334155;">{{ $employee->hire_date?->translatedFormat('d M Y') ?? '-' }}</strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #64748b;">No. Telepon / WA</span>
                            <strong style="color: #334155;">{{ $employee->phone ?: '-' }}</strong>
                        </div>

                        @if ($employee->user)
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #64748b;">Akun Login</span>
                                <span style="color: #0284c7; font-weight: 600; font-size: 12px;">{{ $employee->user->email }}</span>
                            </div>
                        @endif

                        @if ($employee->emergency_contact_name)
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #64748b;">Kontak Darurat</span>
                                <strong style="color: #334155;">{{ $employee->emergency_contact_name }}</strong>
                            </div>
                        @endif
                    </div>

                    @can('employees.manage')
                        <div style="margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                            <a class="button button--primary admin-button" style="width: 100%; justify-content: center;" href="{{ route('admin.employees.edit', $employee) }}">
                                <x-ui.icon name="edit" size="14" /> Edit Data Karyawan
                            </a>
                        </div>
                    @endcan
                </div>
            </section>

            <!-- Ringkasan Arsip Berkas -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Ringkasan Berkas</h2>
                        <p>Total riwayat dan arsip digital.</p>
                    </div>
                </header>
                <div class="admin-panel__body" style="padding: 16px; display: grid; gap: 10px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #334155;">
                            <x-ui.icon name="book-open" size="16" /> Riwayat Pendidikan
                        </span>
                        <strong style="color: #0284c7; font-size: 14px;">{{ $employee->educations->count() }}</strong>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #334155;">
                            <x-ui.icon name="file" size="16" /> Dokumen Digital
                        </span>
                        <strong style="color: #0284c7; font-size: 14px;">{{ $employee->documents->count() }}</strong>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @can('employees.manage')
        <!-- Modal Tambah Pendidikan -->
        <x-ui.modal id="create-education" title="Tambah Riwayat Pendidikan">
            <form method="POST" action="{{ route('admin.employees.educations.store', $employee) }}" enctype="multipart/form-data">
                @csrf
                <div style="display: grid; gap: 14px">
                    <x-ui.text-input
                        label="Nama Institusi / Universitas / Sekolah"
                        name="institution_name"
                        placeholder="Contoh: Universitas Sriwijaya"
                        required
                    />

                    <x-ui.text-input
                        label="Jenjang Pendidikan"
                        name="education_level"
                        placeholder="Contoh: S1, D3, SMA, SMK, Sertifikasi"
                    />

                    <x-ui.text-input
                        label="Jurusan / Program Studi"
                        name="major"
                        placeholder="Contoh: Teknik Mesin"
                    />

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px">
                        <x-ui.text-input
                            label="Tahun Mulai"
                            name="start_year"
                            placeholder="2018"
                            maxlength="10"
                        />

                        <x-ui.text-input
                            label="Tahun Selesai"
                            name="end_year"
                            placeholder="2022"
                            maxlength="10"
                        />
                    </div>

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Status Pendidikan</span>
                        <select name="is_current">
                            <option value="0">Telah Selesai / Lulus</option>
                            <option value="1">Masih Berjalan (Sedang Menempuh)</option>
                        </select>
                    </label>

                    <x-ui.text-input
                        label="Nilai / IPK (Opsional)"
                        name="grade"
                        placeholder="Contoh: 3.75"
                        maxlength="30"
                    />

                    <x-ui.file-input
                        label="Upload Berkas Ijazah / Sertifikat"
                        name="education_file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        hint="Format PDF atau Gambar (Maks 10 MB)."
                    />

                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Catatan Tambahan (Opsional)</span>
                        <textarea name="description" rows="2" placeholder="Catatan singkat"></textarea>
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px">
                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                    <button class="button button--primary admin-button" type="submit">Simpan Pendidikan</button>
                </div>
            </form>
        </x-ui.modal>

        <!-- Modal Unggah Dokumen -->
        <x-ui.modal id="create-document" title="Unggah Dokumen Kepegawaian">
            <form method="POST" action="{{ route('admin.employees.documents.store', $employee) }}" enctype="multipart/form-data">
                @csrf
                <div style="display: grid; gap: 14px">
                    <x-ui.text-input
                        label="Label / Jenis Dokumen"
                        name="document_label"
                        placeholder="Contoh: KTP, NPWP, Kontrak Kerja 2026, Sertifikat Welder 6G, SKCK"
                        required
                    />

                    <x-ui.file-input
                        label="Pilih Berkas Dokumen"
                        name="document_file"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip"
                        hint="Format file PDF, Gambar, Dokumen Word/Excel, atau ZIP (Maks 10 MB)."
                        required
                    />
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px">
                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                    <button class="button button--primary admin-button" type="submit">Unggah Dokumen</button>
                </div>
            </form>
        </x-ui.modal>
    @endcan
@endsection
