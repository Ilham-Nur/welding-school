@extends('layouts.admin')

@section('title', 'Edit Karyawan: ' . $employee->full_name)
@section('eyebrow', 'Kepegawaian & SDM')
@section('heading', 'Edit Data Karyawan')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Edit Data Karyawan</h1>
            <p>Perbarui identitas, status kepegawaian, nomor kontak, BPJS, dan foto karyawan.</p>
        </div>
        <div class="admin-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.employees.show', $employee) }}">Lihat Detail Profil</a>
            <a class="button button--outline admin-button" href="{{ route('admin.employees.index') }}">← Kembali ke daftar</a>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.employees.update', $employee) }}" enctype="multipart/form-data" id="form-edit-employee">
        @csrf
        @method('PUT')

        <div style="display: grid; gap: 24px">
            <!-- 1. Informasi Kepegawaian -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>1. Informasi Kepegawaian</h2>
                        <p>Status kerja, penempatan jabatan, dan akun sistem (Kode: <strong>{{ $employee->employee_code }}</strong>).</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Jabatan / Posisi</span>
                            <select name="position">
                                <option value="">-- Pilih Jabatan / Posisi --</option>
                                @foreach ($positions as $pos)
                                    <option value="{{ $pos->name }}" @selected(old('position', $employee->position) === $pos->name)>
                                        {{ $pos->name }}{{ $pos->code ? " ({$pos->code})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Pilih dari Master Data Jabatan. <a href="{{ route('admin.employee-positions.index') }}" target="_blank" style="color: var(--admin-primary, #0284c7)">+ Kelola Master Jabatan</a></small>
                        </label>

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Status Kepegawaian <em>Wajib</em></span>
                            <select name="employment_status" required>
                                @foreach (\App\Models\Employee::EMPLOYMENT_STATUSES as $val => $label)
                                    <option value="{{ $val }}" @selected(old('employment_status', $employee->employment_status) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-ui.text-input
                            label="Tanggal Masuk / Mulai Kerja"
                            name="hire_date"
                            type="date"
                            :value="$employee->hire_date?->format('Y-m-d')"
                        />

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Hubungkan Akun Login (Opsional)</span>
                            <select name="user_id">
                                <option value="">-- Tidak terhubung ke akun pengguna --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id', $employee->user_id) == $user->id)>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <small>Pilih jika karyawan memiliki akun user internal pada sistem.</small>
                        </label>
                    </div>
                </div>
            </section>

            <!-- 2. Biodata & Identitas Pribadi -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>2. Biodata & Data Pribadi</h2>
                        <p>Informasi identitas resmi sesuai KTP.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Nama Lengkap"
                            name="full_name"
                            :value="$employee->full_name"
                            placeholder="Contoh: Wahyu Adi Kesuma"
                            maxlength="255"
                            required
                        />

                        <x-ui.text-input
                            label="Nomor KTP / NIK"
                            name="identity_number"
                            :value="$employee->identity_number"
                            placeholder="16 digit nomor NIK"
                            maxlength="50"
                        />

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Jenis Kelamin</span>
                            <select name="gender">
                                <option value="">-- Pilih jenis kelamin --</option>
                                @foreach (\App\Models\Employee::GENDERS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('gender', $employee->gender) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-ui.text-input
                            label="Tempat Lahir"
                            name="birth_place"
                            :value="$employee->birth_place"
                            placeholder="Contoh: Palembang"
                            maxlength="255"
                        />

                        <x-ui.text-input
                            label="Tanggal Lahir"
                            name="birth_date"
                            type="date"
                            :value="$employee->birth_date?->format('Y-m-d')"
                        />

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Agama</span>
                            <select name="religion">
                                <option value="">-- Pilih agama --</option>
                                @foreach (\App\Models\Employee::RELIGIONS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('religion', $employee->religion) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Status Pernikahan</span>
                            <select name="marital_status">
                                <option value="">-- Pilih status --</option>
                                @foreach (\App\Models\Employee::MARITAL_STATUSES as $val => $label)
                                    <option value="{{ $val }}" @selected(old('marital_status', $employee->marital_status) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-ui.text-input
                            label="Kewarganegaraan"
                            name="nationality"
                            :value="$employee->nationality ?? 'Indonesia'"
                            placeholder="Contoh: Indonesia"
                            maxlength="100"
                        />

                        <!-- Foto Karyawan dengan Pengaturan Posisi & Pratinjau -->
                        <div class="admin-field--full" style="margin-top: 8px">
                            <label class="ui-field__label" style="display: block; margin-bottom: 8px; font-weight: 600; color: #334155">
                                Foto Karyawan
                            </label>
                            <div style="display: flex; gap: 24px; align-items: center; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; flex-wrap: wrap;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <div style="width: 110px; height: 110px; border-radius: 50%; background: #e2e8f0; border: 3px solid #cbd5e1; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                                        @if ($employee->photo_path)
                                            <img id="photo-preview-img" src="{{ $employee->photoUrl() }}" alt="Foto {{ $employee->full_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            <div id="photo-placeholder" style="display: none; flex-direction: column; align-items: center; color: #64748b;">
                                                <x-ui.icon name="image" size="34" />
                                            </div>
                                        @else
                                            <img id="photo-preview-img" src="" alt="Pratinjau Foto" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                            <div id="photo-placeholder" style="display: flex; flex-direction: column; align-items: center; color: #64748b;">
                                                <x-ui.icon name="image" size="34" />
                                            </div>
                                        @endif
                                    </div>
                                    <span style="font-size: 12px; color: #64748b; font-weight: 600">Pratinjau Foto</span>
                                </div>
                                <div style="flex: 1; min-width: 260px; display: grid; gap: 10px;">
                                    <x-ui.file-input
                                        label="{{ $employee->photo_path ? 'Ganti Berkas Foto Karyawan' : 'Pilih Berkas Foto Karyawan' }}"
                                        name="original_photo"
                                        id="employee-original-photo-input"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        hint="Format gambar JPG, PNG, atau WebP. Maksimal 10 MB."
                                    />
                                    <!-- Hidden input for adjusted/positioned photo -->
                                    <input type="file" name="photo" id="employee-cropped-photo-input" style="display: none;">

                                    <div style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
                                        <button
                                            class="button button--outline admin-button"
                                            type="button"
                                            id="btn-open-cropper"
                                            data-modal-open="employee-photo-editor"
                                            @disabled(! $employee->photo_path)
                                            style="padding: 6px 14px; font-size: 13px;"
                                        >
                                            <x-ui.icon name="crop" size="14" /> Atur Posisi Foto
                                        </button>

                                        @if ($employee->photo_path)
                                            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #dc2626; cursor: pointer;">
                                                <input type="checkbox" name="remove_photo" value="1"> Hapus foto karyawan saat ini
                                            </label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Kontak & Alamat -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>3. Kontak & Alamat Domisili</h2>
                        <p>Nomor telepon aktif dan kontak darurat yang dapat dihubungi.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Nomor Telepon / WhatsApp"
                            name="phone"
                            :value="$employee->phone"
                            placeholder="Contoh: 0812-7006-2718"
                            maxlength="50"
                        />

                        <x-ui.text-input
                            label="Nama Kontak Darurat"
                            name="emergency_contact_name"
                            :value="$employee->emergency_contact_name"
                            placeholder="Contoh: Hanum (Istri / Keluarga)"
                            maxlength="255"
                        />

                        <x-ui.text-input
                            label="Nomor Telepon Darurat"
                            name="emergency_contact_phone"
                            :value="$employee->emergency_contact_phone"
                            placeholder="Contoh: 081267394003"
                            maxlength="50"
                        />

                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Alamat Lengkap</span>
                            <textarea
                                name="full_address"
                                rows="3"
                                placeholder="Contoh: Tanjung Buntung Blk A1 No 10 RT.08 RW.02"
                            >{{ old('full_address', $employee->full_address) }}</textarea>
                        </label>
                    </div>
                </div>
            </section>

            <!-- 4. BPJS & Informasi Tambahan -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>4. BPJS & Informasi Tambahan</h2>
                        <p>Jaminan sosial dan catatan khusus mengenai karyawan.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Nomor BPJS Ketenagakerjaan"
                            name="bpjs_ketenagakerjaan_number"
                            :value="$employee->bpjs_ketenagakerjaan_number"
                            placeholder="Nomor kartu BPJS TK"
                            maxlength="50"
                        />

                        <x-ui.text-input
                            label="Nomor BPJS Kesehatan"
                            name="bpjs_kesehatan_number"
                            :value="$employee->bpjs_kesehatan_number"
                            placeholder="Nomor kartu BPJS Kesehatan"
                            maxlength="50"
                        />

                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Informasi Penting / Catatan Tambahan</span>
                            <textarea
                                name="important_information"
                                rows="3"
                                placeholder="Contoh: Sedang Melanjutkan pendidikan S1 (Ilmu Komunikasi)"
                            >{{ old('important_information', $employee->important_information) }}</textarea>
                        </label>
                    </div>
                </div>
            </section>

            <!-- 5. Pendidikan Terakhir -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>5. Pendidikan Terakhir</h2>
                        <p>Jenjang pendidikan dan berkas ijazah terakhir.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Jenjang Pendidikan Terakhir"
                            name="last_education"
                            :value="$employee->last_education"
                            placeholder="Contoh: S1 Ilmu Komunikasi / SMK Teknik Mesin"
                            maxlength="100"
                        />

                        <div class="admin-field--full">
                            @if ($employee->last_education_file_path)
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; flex-wrap: wrap; gap: 10px;">
                                    <div>
                                        <strong>Berkas saat ini: {{ $employee->last_education_file_name ?: basename($employee->last_education_file_path) }}</strong>
                                        <div style="margin-top: 4px; display: flex; gap: 10px;">
                                            <a href="{{ route('admin.employees.last-education.preview', $employee) }}" target="_blank" style="color: #2563eb; font-size: 12px">Preview</a>
                                            <a href="{{ route('admin.employees.last-education.download', $employee) }}" style="color: #2563eb; font-size: 12px">Download</a>
                                        </div>
                                    </div>
                                    <label style="font-size: 12px; color: #dc2626; cursor: pointer;">
                                        <input type="checkbox" name="remove_last_education_file" value="1"> Hapus berkas ini
                                    </label>
                                </div>
                            @endif

                            <x-ui.file-input
                                label="{{ $employee->last_education_file_path ? 'Ganti Berkas Ijazah Terakhir' : 'Berkas Ijazah Pendidikan Terakhir' }}"
                                name="last_education_file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                hint="PDF atau scan ijazah (Maks 10 MB)."
                            />
                        </div>
                    </div>
                </div>
            </section>

            <div class="admin-actions" style="margin-bottom: 30px">
                <button class="button button--primary admin-button" type="submit">Perbarui Data Karyawan</button>
                <a class="button button--outline admin-button" href="{{ route('admin.employees.show', $employee) }}">Batal</a>
            </div>
        </div>
    </form>

    <!-- Modal Atur Posisi Foto Karyawan -->
    <x-ui.modal
        id="employee-photo-editor"
        title="Atur Posisi Foto Karyawan"
        description="Geser dan sesuaikan posisi foto agar wajah/profil karyawan terlihat pas pada bingkai lingkaran."
        size="large"
    >
        <div style="display: grid; gap: 16px; justify-items: center;">
            <div style="position: relative; width: 320px; height: 320px; background: #0f172a; border-radius: 12px; overflow: hidden; cursor: grab; box-shadow: 0 4px 12px rgba(0,0,0,0.15);" id="canvas-container">
                <canvas id="cropper-canvas" width="320" height="320" style="width: 100%; height: 100%; display: block;"></canvas>
                <!-- Circular guide mask -->
                <div style="position: absolute; inset: 0; pointer-events: none; border-radius: 50%; box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.65); border: 2px dashed rgba(255,255,255,0.75);"></div>
            </div>

            <!-- Controls -->
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: center; width: 100%; max-width: 400px;">
                <button class="button button--outline admin-button" type="button" id="btn-zoom-out" style="padding: 6px 12px; font-size: 14px;">&minus; Perkecil</button>
                <input type="range" id="zoom-range" min="0.5" max="3" step="0.05" value="1" style="flex: 1; accent-color: var(--admin-primary, #0284c7);">
                <button class="button button--outline admin-button" type="button" id="btn-zoom-in" style="padding: 6px 12px; font-size: 14px;">+ Perbesar</button>
                <button class="button button--outline admin-button" type="button" id="btn-reset-pos" style="padding: 6px 12px; font-size: 12px;">Reset</button>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 0; text-align: center;">
                Tahan dan geser (drag) foto di dalam kotak untuk mengubah posisinya.
            </p>
        </div>

        <x-slot:footer>
            <button class="button button--outline admin-button" type="button" data-modal-close id="btn-cancel-crop">Batal</button>
            <button class="button button--primary admin-button" type="button" id="btn-apply-crop">Gunakan Posisi Foto Ini</button>
        </x-slot:footer>
    </x-ui.modal>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const originalPhotoInput = document.getElementById('employee-original-photo-input');
            const croppedPhotoInput = document.getElementById('employee-cropped-photo-input');
            const previewImg = document.getElementById('photo-preview-img');
            const placeholder = document.getElementById('photo-placeholder');
            const btnOpenCropper = document.getElementById('btn-open-cropper');
            const canvasContainer = document.getElementById('canvas-container');
            const canvas = document.getElementById('cropper-canvas');
            const ctx = canvas ? canvas.getContext('2d') : null;
            const btnZoomIn = document.getElementById('btn-zoom-in');
            const btnZoomOut = document.getElementById('btn-zoom-out');
            const zoomRange = document.getElementById('zoom-range');
            const btnResetPos = document.getElementById('btn-reset-pos');
            const btnApplyCrop = document.getElementById('btn-apply-crop');
            const btnCancelCrop = document.getElementById('btn-cancel-crop');

            let rawImage = new Image();
            let imgLoaded = false;
            let imgState = {
                x: 0,
                y: 0,
                scale: 1,
                minScale: 0.2,
                maxScale: 4
            };
            let isDragging = false;
            let dragStart = { x: 0, y: 0 };

            @if ($employee->photo_path)
                // Pre-load existing photo for adjusting if user wants
                rawImage.crossOrigin = "anonymous";
                rawImage.onload = function () {
                    imgLoaded = true;
                    if (btnOpenCropper) btnOpenCropper.disabled = false;
                    resetImageState();
                };
                rawImage.src = "{{ $employee->originalPhotoUrl() ?: $employee->photoUrl() }}";
            @endif

            function resetImageState() {
                if (!imgLoaded) return;
                const canvasW = canvas.width;
                const canvasH = canvas.height;
                const imgW = rawImage.width;
                const imgH = rawImage.height;

                const scale = Math.max(canvasW / imgW, canvasH / imgH);
                imgState.scale = scale;
                imgState.minScale = scale * 0.5;
                imgState.maxScale = scale * 3.5;
                imgState.x = (canvasW - imgW * scale) / 2;
                imgState.y = (canvasH - imgH * scale) / 2;

                if (zoomRange) {
                    zoomRange.min = imgState.minScale;
                    zoomRange.max = imgState.maxScale;
                    zoomRange.value = scale;
                }
                drawCanvas();
            }

            function drawCanvas() {
                if (!ctx || !imgLoaded) return;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(
                    rawImage,
                    imgState.x,
                    imgState.y,
                    rawImage.width * imgState.scale,
                    rawImage.height * imgState.scale
                );
            }

            if (originalPhotoInput) {
                originalPhotoInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (event) {
                            rawImage = new Image();
                            rawImage.onload = function () {
                                imgLoaded = true;
                                if (btnOpenCropper) btnOpenCropper.disabled = false;
                                resetImageState();
                                generateCroppedBlob();
                                if (btnOpenCropper) btnOpenCropper.click();
                            };
                            rawImage.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            if (canvasContainer) {
                canvasContainer.addEventListener('mousedown', function (e) {
                    if (!imgLoaded) return;
                    isDragging = true;
                    dragStart.x = e.clientX - imgState.x;
                    dragStart.y = e.clientY - imgState.y;
                    canvasContainer.style.cursor = 'grabbing';
                });

                window.addEventListener('mousemove', function (e) {
                    if (!isDragging) return;
                    imgState.x = e.clientX - dragStart.x;
                    imgState.y = e.clientY - dragStart.y;
                    drawCanvas();
                });

                window.addEventListener('mouseup', function () {
                    if (isDragging) {
                        isDragging = false;
                        if (canvasContainer) canvasContainer.style.cursor = 'grab';
                    }
                });

                canvasContainer.addEventListener('touchstart', function (e) {
                    if (!imgLoaded || e.touches.length === 0) return;
                    isDragging = true;
                    dragStart.x = e.touches[0].clientX - imgState.x;
                    dragStart.y = e.touches[0].clientY - imgState.y;
                }, { passive: true });

                window.addEventListener('touchmove', function (e) {
                    if (!isDragging || e.touches.length === 0) return;
                    imgState.x = e.touches[0].clientX - dragStart.x;
                    imgState.y = e.touches[0].clientY - dragStart.y;
                    drawCanvas();
                }, { passive: true });

                window.addEventListener('touchend', function () {
                    isDragging = false;
                });
            }

            if (zoomRange) {
                zoomRange.addEventListener('input', function () {
                    if (!imgLoaded) return;
                    const oldScale = imgState.scale;
                    const newScale = parseFloat(zoomRange.value);
                    const centerX = canvas.width / 2;
                    const centerY = canvas.height / 2;

                    imgState.x = centerX - (centerX - imgState.x) * (newScale / oldScale);
                    imgState.y = centerY - (centerY - imgState.y) * (newScale / oldScale);
                    imgState.scale = newScale;
                    drawCanvas();
                });
            }

            if (btnZoomIn) {
                btnZoomIn.addEventListener('click', function () {
                    if (!imgLoaded || !zoomRange) return;
                    zoomRange.value = Math.min(parseFloat(zoomRange.max), parseFloat(zoomRange.value) + 0.15);
                    zoomRange.dispatchEvent(new Event('input'));
                });
            }

            if (btnZoomOut) {
                btnZoomOut.addEventListener('click', function () {
                    if (!imgLoaded || !zoomRange) return;
                    zoomRange.value = Math.max(parseFloat(zoomRange.min), parseFloat(zoomRange.value) - 0.15);
                    zoomRange.dispatchEvent(new Event('input'));
                });
            }

            if (btnResetPos) {
                btnResetPos.addEventListener('click', resetImageState);
            }

            function generateCroppedBlob(closeModalAfter = false) {
                if (!imgLoaded) return;

                const exportCanvas = document.createElement('canvas');
                exportCanvas.width = 800;
                exportCanvas.height = 800;
                const expCtx = exportCanvas.getContext('2d');

                const ratio = exportCanvas.width / canvas.width;
                expCtx.drawImage(
                    rawImage,
                    imgState.x * ratio,
                    imgState.y * ratio,
                    rawImage.width * imgState.scale * ratio,
                    rawImage.height * imgState.scale * ratio
                );

                exportCanvas.toBlob(function (blob) {
                    if (!blob) return;
                    const file = new File([blob], 'foto-karyawan-crop.jpg', { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    if (croppedPhotoInput) croppedPhotoInput.files = dt.files;

                    if (previewImg) {
                        previewImg.src = exportCanvas.toDataURL('image/jpeg', 0.92);
                        previewImg.style.display = 'block';
                    }
                    if (placeholder) placeholder.style.display = 'none';

                    if (closeModalAfter && btnCancelCrop) {
                        btnCancelCrop.click();
                    }
                }, 'image/jpeg', 0.92);
            }

            if (btnApplyCrop) {
                btnApplyCrop.addEventListener('click', function () {
                    generateCroppedBlob(true);
                });
            }
        });
    </script>
@endsection
