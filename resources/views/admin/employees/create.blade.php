@extends('layouts.admin')

@section('title', 'Tambah Karyawan Baru')
@section('eyebrow', 'Kepegawaian & SDM')
@section('heading', 'Tambah Karyawan')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Tambah Karyawan Baru</h1>
            <p>Masukkan identitas, posisi, informasi kontak, nomor BPJS, dan berkas kepegawaian.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.employees.index') }}">← Kembali ke daftar</a>
    </section>

    <form method="POST" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data" id="form-create-employee">
        @csrf

        <div style="display: grid; gap: 24px">
            <!-- 1. Informasi Kepegawaian -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>1. Informasi Kepegawaian</h2>
                        <p>Status kerja, penempatan jabatan, dan akun sistem.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Jabatan / Posisi</span>
                            <select name="position">
                                <option value="">-- Pilih Jabatan / Posisi --</option>
                                @foreach ($positions as $pos)
                                    <option value="{{ $pos->name }}" @selected(old('position') === $pos->name)>
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
                                    <option value="{{ $val }}" @selected(old('employment_status', 'kontrak') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-ui.text-input
                            label="Tanggal Masuk / Mulai Kerja"
                            name="hire_date"
                            type="date"
                            :value="old('hire_date')"
                        />

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Hubungkan Akun Login (Opsional)</span>
                            <select name="user_id">
                                <option value="">-- Tidak terhubung ke akun pengguna --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
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
                            :value="old('full_name')"
                            placeholder="Contoh: Wahyu Adi Kesuma"
                            maxlength="255"
                            required
                        />

                        <x-ui.text-input
                            label="Nomor KTP / NIK"
                            name="identity_number"
                            :value="old('identity_number')"
                            placeholder="16 digit nomor NIK"
                            maxlength="50"
                        />

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Jenis Kelamin</span>
                            <select name="gender">
                                <option value="">-- Pilih jenis kelamin --</option>
                                @foreach (\App\Models\Employee::GENDERS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('gender') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-ui.text-input
                            label="Tempat Lahir"
                            name="birth_place"
                            :value="old('birth_place')"
                            placeholder="Contoh: Palembang"
                            maxlength="255"
                        />

                        <x-ui.text-input
                            label="Tanggal Lahir"
                            name="birth_date"
                            type="date"
                            :value="old('birth_date')"
                        />

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Agama</span>
                            <select name="religion">
                                <option value="">-- Pilih agama --</option>
                                @foreach (\App\Models\Employee::RELIGIONS as $val => $label)
                                    <option value="{{ $val }}" @selected(old('religion') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Status Pernikahan</span>
                            <select name="marital_status">
                                <option value="">-- Pilih status --</option>
                                @foreach (\App\Models\Employee::MARITAL_STATUSES as $val => $label)
                                    <option value="{{ $val }}" @selected(old('marital_status') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <x-ui.text-input
                            label="Kewarganegaraan"
                            name="nationality"
                            :value="old('nationality', 'Indonesia')"
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
                                        <img id="photo-preview-img" src="" alt="Pratinjau Foto Karyawan" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                        <div id="photo-placeholder" style="display: flex; flex-direction: column; align-items: center; color: #64748b;">
                                            <x-ui.icon name="image" size="34" />
                                        </div>
                                    </div>
                                    <span style="font-size: 12px; color: #64748b; font-weight: 600">Pratinjau Foto</span>
                                </div>
                                <div style="flex: 1; min-width: 260px; display: grid; gap: 10px;">
                                    <x-ui.file-input
                                        label="Pilih Berkas Foto Karyawan"
                                        name="original_photo"
                                        id="employee-original-photo-input"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        hint="Format gambar JPG, PNG, atau WebP. Maksimal 10 MB."
                                    />
                                    <!-- Hidden input for adjusted/positioned photo -->
                                    <input type="file" name="photo" id="employee-cropped-photo-input" style="display: none;">

                                    <div>
                                        <button
                                            class="button button--outline admin-button"
                                            type="button"
                                            id="btn-open-cropper"
                                            data-modal-open="employee-photo-editor"
                                            disabled
                                            style="padding: 6px 14px; font-size: 13px;"
                                        >
                                            <x-ui.icon name="crop" size="14" /> Atur Posisi Foto
                                        </button>
                                        <small style="display: block; color: #64748b; margin-top: 4px; font-size: 12px">
                                            Foto asli tetap tersimpan utuh, sedangkan posisi yang diatur akan tampil sebagai foto profil karyawan.
                                        </small>
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
                            :value="old('phone')"
                            placeholder="Contoh: 0812-7006-2718"
                            maxlength="50"
                        />

                        <x-ui.text-input
                            label="Nama Kontak Darurat"
                            name="emergency_contact_name"
                            :value="old('emergency_contact_name')"
                            placeholder="Contoh: Hanum (Istri / Keluarga)"
                            maxlength="255"
                        />

                        <x-ui.text-input
                            label="Nomor Telepon Darurat"
                            name="emergency_contact_phone"
                            :value="old('emergency_contact_phone')"
                            placeholder="Contoh: 081267394003"
                            maxlength="50"
                        />

                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Alamat Lengkap</span>
                            <textarea
                                name="full_address"
                                rows="3"
                                placeholder="Contoh: Tanjung Buntung Blk A1 No 10 RT.08 RW.02"
                            >{{ old('full_address') }}</textarea>
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
                            :value="old('bpjs_ketenagakerjaan_number')"
                            placeholder="Nomor kartu BPJS TK"
                            maxlength="50"
                        />

                        <x-ui.text-input
                            label="Nomor BPJS Kesehatan"
                            name="bpjs_kesehatan_number"
                            :value="old('bpjs_kesehatan_number')"
                            placeholder="Nomor kartu BPJS Kesehatan"
                            maxlength="50"
                        />

                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Informasi Penting / Catatan Tambahan</span>
                            <textarea
                                name="important_information"
                                rows="3"
                                placeholder="Contoh: Sedang Melanjutkan pendidikan S1 (Ilmu Komunikasi)"
                            >{{ old('important_information') }}</textarea>
                        </label>
                    </div>
                </div>
            </section>

            <!-- 5. Pendidikan Terakhir & Riwayat Tambahan (Opsional) -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>5. Pendidikan Terakhir</h2>
                        <p>Jenjang pendidikan terakhir dan berkas ijazah.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Jenjang Pendidikan Terakhir"
                            name="last_education"
                            :value="old('last_education')"
                            placeholder="Contoh: S1 Ilmu Komunikasi / SMK Teknik Mesin"
                            maxlength="100"
                        />

                        <x-ui.file-input
                            label="Berkas Ijazah Pendidikan Terakhir"
                            name="last_education_file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            hint="PDF atau scan ijazah (Maks 10 MB)."
                        />
                    </div>

                    <!-- Riwayat Pendidikan Tambahan (Dynamic Repeater) -->
                    <div style="border-top: 1px dashed #e2e8f0; padding-top: 18px; margin-top: 20px">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <h3 style="font-size: 15px; color: #1e293b; margin-bottom: 2px">Riwayat Pendidikan Lainnya (Opsional)</h3>
                                <p style="font-size: 13px; color: #64748b; margin: 0">Tambahkan riwayat jenjang sekolah, universitas, atau pelatihan karyawan lainnya.</p>
                            </div>
                            <button class="button button--outline admin-button" type="button" id="btn-add-education">
                                + Tambah Riwayat Pendidikan
                            </button>
                        </div>

                        <div id="educations-container" style="display: grid; gap: 14px">
                            <!-- Baris pendidikan dinamis -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. Dokumen Tambahan (Dynamic Multi-Upload) -->
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>6. Dokumen Tambahan (Opsional)</h2>
                        <p>Unggah berbagai dokumen resmi karyawan sekaligus (KTP, NPWP, Sertifikat Welder 6G, Kontrak Kerja, SKCK, dll).</p>
                    </div>
                    <button class="button button--outline admin-button" type="button" id="btn-add-document">
                        + Tambah Dokumen Tambahan
                    </button>
                </header>
                <div class="admin-panel__body">
                    <div id="documents-container" style="display: grid; gap: 14px">
                        <!-- Baris dokumen dinamis -->
                    </div>
                    <p id="documents-empty-hint" style="color: #64748b; font-size: 13px; margin: 0">
                        Klik tombol <strong>+ Tambah Dokumen Tambahan</strong> di atas untuk mengunggah satu atau lebih berkas dokumen pendukung.
                    </p>
                </div>
            </section>

            <div class="admin-actions" style="margin-bottom: 30px">
                <button class="button button--primary admin-button" type="submit">Simpan Data Karyawan</button>
                <a class="button button--outline admin-button" href="{{ route('admin.employees.index') }}">Batal</a>
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
            // ==========================================
            // 1. Photo Upload & Position Canvas Editor
            // ==========================================
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

            function resetImageState() {
                if (!imgLoaded) return;
                const canvasW = canvas.width;
                const canvasH = canvas.height;
                const imgW = rawImage.width;
                const imgH = rawImage.height;

                // Cover canvas
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
                                generateCroppedBlob(); // Set default cropped
                                if (btnOpenCropper) btnOpenCropper.click(); // Auto open modal for comfort
                            };
                            rawImage.src = event.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Drag handling
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

                // Touch handling for mobile
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

            // Zoom controls
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

                    // Update main preview
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

            // ==========================================
            // 2. Dynamic Education Repeater
            // ==========================================
            const educationsContainer = document.getElementById('educations-container');
            const btnAddEducation = document.getElementById('btn-add-education');
            let educationIndex = 0;

            if (btnAddEducation && educationsContainer) {
                btnAddEducation.addEventListener('click', function () {
                    const idx = educationIndex++;
                    const card = document.createElement('div');
                    card.className = 'education-row-card';
                    card.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; position: relative;';
                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <strong style="color: #334155; font-size: 14px;">Riwayat Pendidikan #${idx + 1}</strong>
                            <button type="button" class="btn-remove-row button button--danger admin-button" style="padding: 4px 10px; font-size: 12px;">
                                Hapus
                            </button>
                        </div>
                        <div class="admin-form-grid">
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Nama Institusi / Sekolah / Kampus <em>Wajib</em></span>
                                <input type="text" name="educations[${idx}][institution_name]" placeholder="Contoh: Universitas Sriwijaya" required>
                            </label>
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Jenjang Pendidikan</span>
                                <input type="text" name="educations[${idx}][education_level]" placeholder="Contoh: S1, D3, SMA, SMK">
                            </label>
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Jurusan / Program Studi</span>
                                <input type="text" name="educations[${idx}][major]" placeholder="Contoh: Teknik Mesin">
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <label class="ui-field admin-field">
                                    <span class="ui-field__label">Tahun Masuk</span>
                                    <input type="text" name="educations[${idx}][start_year]" placeholder="2018">
                                </label>
                                <label class="ui-field admin-field">
                                    <span class="ui-field__label">Tahun Lulus</span>
                                    <input type="text" name="educations[${idx}][end_year]" placeholder="2022">
                                </label>
                            </div>
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Nilai Akhir / IPK</span>
                                <input type="text" name="educations[${idx}][grade]" placeholder="Contoh: 3.75">
                            </label>
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Upload Berkas Ijazah</span>
                                <input type="file" name="educations[${idx}][file]" accept=".pdf,.jpg,.jpeg,.png">
                                <small>PDF atau Gambar (Maks 10 MB).</small>
                            </label>
                        </div>
                    `;

                    card.querySelector('.btn-remove-row').addEventListener('click', function () {
                        card.remove();
                    });

                    educationsContainer.appendChild(card);
                });
            }

            // ==========================================
            // 3. Dynamic Documents Multi-Upload Repeater
            // ==========================================
            const documentsContainer = document.getElementById('documents-container');
            const btnAddDocument = document.getElementById('btn-add-document');
            const documentsEmptyHint = document.getElementById('documents-empty-hint');
            let documentIndex = 0;

            function addDocumentRow() {
                const idx = documentIndex++;
                if (documentsEmptyHint) documentsEmptyHint.style.display = 'none';

                const row = document.createElement('div');
                row.className = 'document-row-card';
                row.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: grid; grid-template-columns: 1fr 1fr auto; gap: 14px; align-items: flex-end;';
                row.innerHTML = `
                    <label class="ui-field admin-field" style="margin: 0">
                        <span class="ui-field__label">Label Dokumen <em>Wajib</em></span>
                        <input type="text" name="documents[${idx}][label]" placeholder="Contoh: KTP / NPWP / Sertifikat Welder 6G / SKCK" required>
                    </label>
                    <label class="ui-field admin-field" style="margin: 0">
                        <span class="ui-field__label">Berkas Dokumen <em>Wajib</em></span>
                        <input type="file" name="documents[${idx}][file]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip" required>
                    </label>
                    <div>
                        <button type="button" class="btn-remove-doc button button--danger admin-button" style="padding: 10px 14px; font-size: 12px; height: 44px;">
                            Hapus
                        </button>
                    </div>
                `;

                row.querySelector('.btn-remove-doc').addEventListener('click', function () {
                    row.remove();
                    if (documentsContainer.children.length === 0 && documentsEmptyHint) {
                        documentsEmptyHint.style.display = 'block';
                    }
                });

                documentsContainer.appendChild(row);
            }

            if (btnAddDocument && documentsContainer) {
                btnAddDocument.addEventListener('click', addDocumentRow);
            }
        });
    </script>
@endsection
