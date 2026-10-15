@php($restore = old('_requirement_form') === $formKey)
@if ($restore && $errors->any())
    <div role="alert" style="margin-bottom: 16px; color: #b91c1c">
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif
<input type="hidden" name="_requirement_form" value="{{ $formKey }}">
<div style="display: grid; gap: 14px">
    <label class="ui-field admin-field">
        <span class="ui-field__label">Nama Persyaratan</span>
        <input name="name" required maxlength="255" value="{{ $restore ? old('name') : $requirement?->name }}" placeholder="Contoh: Ijazah minimal SMK / Sertifikat Welder 6G">
    </label>
    <label class="ui-field admin-field">
        <span class="ui-field__label">Jenis Persyaratan</span>
        <select name="category" required>
            @foreach (\App\Models\EmployeePositionRequirement::CATEGORIES as $key => $label)
                <option value="{{ $key }}" @selected(($restore ? old('category') : $requirement?->category) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="ui-field admin-field">
        <span class="ui-field__label">Kriteria / Keterangan</span>
        <textarea name="description" rows="3" maxlength="2000" placeholder="Tulis pendidikan minimal, bidang sertifikasi, lama pengalaman, atau bukti yang diperlukan.">{{ $restore ? old('description') : $requirement?->description }}</textarea>
    </label>
    <label class="ui-field admin-field">
        <span class="ui-field__label">Kewajiban</span>
        <select name="is_required">
            <option value="1" @selected((string) ($restore ? old('is_required') : ($requirement?->is_required ?? true)) === '1')>Wajib</option>
            <option value="0" @selected((string) ($restore ? old('is_required') : (int) ($requirement?->is_required ?? true)) === '0')>Opsional</option>
        </select>
    </label>
    <label class="ui-field admin-field">
        <span class="ui-field__label">Masa Berlaku</span>
        <select name="has_expiry">
            <option value="0" @selected(! ($restore ? old('has_expiry') : $requirement?->has_expiry))>Tidak membutuhkan tanggal kedaluwarsa</option>
            <option value="1" @selected($restore ? old('has_expiry') : $requirement?->has_expiry)>Wajib mengisi tanggal kedaluwarsa</option>
        </select>
    </label>
    <label class="ui-field admin-field">
        <span class="ui-field__label">Pemeriksaan HR</span>
        <select name="requires_verification">
            <option value="1" @selected($restore ? old('requires_verification') : ($requirement?->requires_verification ?? true))>Perlu verifikasi HR</option>
            <option value="0" @selected(! ($restore ? old('requires_verification') : ($requirement?->requires_verification ?? true)))>Valid setelah diunggah, selama belum kedaluwarsa</option>
        </select>
    </label>
</div>
