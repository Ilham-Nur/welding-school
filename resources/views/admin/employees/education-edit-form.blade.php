<section class="admin-panel" id="riwayat-pendidikan">
    <header class="admin-panel__header"><div><h2>Riwayat Pendidikan</h2><p>Tambahkan riwayat dan berkas pendidikan melalui form karyawan.</p></div></header>
    <div class="admin-panel__body">
        @foreach ($employee->educations as $education)
            <p><strong>{{ $education->institution_name }}</strong> · {{ $education->education_level }}
                @if ($education->file_path)<a href="{{ route('admin.employees.educations.preview', [$employee, $education]) }}" target="_blank">{{ $education->file_name }}</a>@endif
            </p>
        @endforeach
        <details @if(old('educations.0.institution_name')) open @endif>
            <summary style="cursor: pointer; margin-bottom: 16px">+ Tambah Riwayat Pendidikan</summary>
            <div class="admin-form-grid">
                <x-ui.text-input label="Institusi / Sekolah" name="educations[0][institution_name]" :value="old('educations.0.institution_name')" maxlength="255" />
                <x-ui.text-input label="Jenjang Pendidikan" name="educations[0][education_level]" :value="old('educations.0.education_level')" maxlength="100" />
                <x-ui.text-input label="Jurusan" name="educations[0][major]" :value="old('educations.0.major')" maxlength="255" />
                <x-ui.text-input label="Tahun Mulai" name="educations[0][start_year]" :value="old('educations.0.start_year')" maxlength="10" />
                <x-ui.text-input label="Tahun Selesai" name="educations[0][end_year]" :value="old('educations.0.end_year')" maxlength="10" />
                <x-ui.text-input label="Nilai / IPK" name="educations[0][grade]" :value="old('educations.0.grade')" maxlength="30" />
                <x-ui.file-input label="Berkas Pendidikan" name="educations[0][file]" accept=".pdf,.jpg,.jpeg,.png" :max-size-mb="10" hint="Maksimum 10 MB. Setelah disimpan, berkas tersedia pada pilihan bukti persyaratan." />
                <label class="ui-field admin-field"><span class="ui-field__label">Catatan</span><textarea name="educations[0][description]" maxlength="1000" rows="2">{{ old('educations.0.description') }}</textarea></label>
            </div>
        </details>
    </div>
</section>
