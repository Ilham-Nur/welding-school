@extends('layouts.admin')

@php
    $editing = $asset->exists;
    $selectedCategory = old('category_code', $asset->category_code ?? 'WLD');
    $requiresCalibration = (string) old('requires_calibration', $asset->exists ? (int) $asset->requires_calibration : 0);
    $checklistItems = old('checklist_items', $editing
        ? $asset->checklistItems->pluck('label')->all()
        : ['Periksa kondisi fisik alat', 'Pastikan fungsi alat berjalan normal']);
@endphp

@section('title', $editing ? 'Edit Aset' : 'Tambah Aset')
@section('eyebrow', 'Operasional akademi')
@section('heading', $editing ? 'Edit Aset' : 'Tambah Aset')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit data aset' : 'Registrasi aset baru' }}</h1>
            <p>Asset ID dibuat otomatis berdasarkan kategori dan tidak dapat diubah setelah tersimpan.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.assets.index') }}">← Kembali</a>
    </section>

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.assets.update', $asset) : route('admin.assets.store') }}" data-asset-form>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="admin-asset-form-layout">
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Identitas aset</h2>
                        <p>Pilih kategori yang paling sesuai. Kategori dan Asset ID akan dikunci setelah registrasi.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-asset-id-preview">
                        <span>ASSET ID</span>
                        <strong data-asset-id-preview>{{ $editing ? $asset->asset_code : 'ATP-'.$selectedCategory.'-###' }}</strong>
                        <small>{{ $editing ? 'Identitas permanen aset ini.' : 'Nomor urut final dibuat otomatis saat data disimpan.' }}</small>
                    </div>

                    <div class="admin-form-grid">
                        @if ($editing)
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Kategori</span>
                                <input type="text" value="{{ $asset->category_code }} | {{ $asset->categoryLabel() }}" disabled>
                                <small>Kategori dikunci agar Asset ID tetap konsisten.</small>
                            </label>
                        @else
                            <label class="ui-field admin-field">
                                <span class="ui-field__label">Kategori <em>Wajib</em></span>
                                <select name="category_code" required data-asset-category>
                                    @foreach (\App\Models\Asset::CATEGORIES as $code => $label)
                                        <option value="{{ $code }}" @selected($selectedCategory === $code)>{{ $code }} | {{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <x-ui.text-input
                            label="Nama alat / equipment"
                            name="equipment_name"
                            :value="$asset->equipment_name"
                            placeholder="Contoh: SMAW Welding Machine"
                            maxlength="255"
                            required
                        />
                        <x-ui.text-input
                            label="Merek"
                            name="brand"
                            :value="$asset->brand"
                            placeholder="Contoh: Lincoln Electric"
                            maxlength="120"
                        />
                        <x-ui.text-input
                            label="Model / type"
                            name="model"
                            :value="$asset->model"
                            placeholder="Contoh: Invertec V270-S"
                            maxlength="120"
                        />
                        <x-ui.text-input
                            label="Serial number"
                            name="serial_number"
                            :value="$asset->serial_number"
                            placeholder="Nomor seri dari pabrikan"
                            maxlength="100"
                            data-calibration-serial
                        />
                        <x-ui.text-input
                            label="Jumlah"
                            name="quantity"
                            type="number"
                            :value="$asset->quantity ?? 1"
                            min="1"
                            max="10000"
                            required
                        />
                        <x-ui.text-input
                            label="Tahun pembelian"
                            name="purchase_year"
                            type="number"
                            :value="$asset->purchase_year"
                            min="1950"
                            :max="now()->year + 1"
                            placeholder="2026"
                        />
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Foto aset</h2>
                        <p>Gunakan foto yang memperlihatkan alat secara jelas. Foto dapat dipilih dari galeri atau diambil langsung dari kamera HP.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="asset-photo-upload-layout">
                        <div class="asset-photo-preview-wrap">
                            @if ($asset->photoUrl())
                                <img class="asset-photo-preview" src="{{ $asset->photoUrl() }}" alt="Foto {{ $asset->equipment_name }}" data-asset-photo-preview>
                                <div class="asset-photo-placeholder" data-asset-photo-placeholder hidden>
                                    <x-ui.icon name="image" size="32" />
                                    <strong>Belum ada foto aset</strong>
                                </div>
                            @else
                                <img class="asset-photo-preview" alt="Pratinjau foto aset" data-asset-photo-preview hidden>
                                <div class="asset-photo-placeholder" data-asset-photo-placeholder>
                                    <x-ui.icon name="image" size="32" />
                                    <strong>Belum ada foto aset</strong>
                                    <small>Pilih dari galeri atau gunakan kamera HP.</small>
                                </div>
                            @endif
                        </div>

                        <div class="asset-photo-upload-controls">
                            <x-ui.file-input
                                label="Pilih dari galeri"
                                name="photo"
                                accept="image/*"
                                hint="JPG, PNG, atau WebP. Maksimal 8 MB."
                                data-asset-photo-input
                            />

                            <label class="asset-camera-input">
                                <input type="file" accept="image/*" capture="environment" data-asset-camera-input>
                                <span aria-hidden="true"><x-ui.icon name="camera" size="21" /></span>
                                <span>
                                    <strong>Ambil foto dari kamera</strong>
                                    <small>Pada HP, kamera belakang akan dibuka langsung.</small>
                                </span>
                            </label>

                            <div class="asset-photo-crop-action">
                                <span>
                                    <strong>Komposisi foto 4:3</strong>
                                    <small>Geser dan perbesar foto agar alat terlihat utuh dan mudah dikenali.</small>
                                </span>
                                <button
                                    class="button button--outline admin-button"
                                    type="button"
                                    data-modal-open="asset-photo-editor"
                                    data-asset-photo-crop-open
                                    @disabled(! $asset->photoUrl())
                                >
                                    Atur foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Penempatan dan monitoring</h2>
                        <p>Kondisi dan jadwal inspeksi akan diperbarui melalui halaman pemeriksaan aset.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Lokasi"
                            name="location"
                            :value="$asset->location"
                            placeholder="Contoh: Workshop Welding Bay 01"
                            maxlength="255"
                            required
                        />
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Kondisi <em>Wajib</em></span>
                            <select name="condition" required>
                                @foreach (\App\Models\Asset::CONDITIONS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('condition', $asset->condition ?? 'good') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Status alat <em>Wajib</em></span>
                            <select name="status" required data-asset-status>
                                @foreach (\App\Models\Asset::STATUSES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $asset->status ?? 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Interval inspeksi <em>Wajib</em></span>
                            <select name="inspection_interval_months" required>
                                @foreach (\App\Models\Asset::INSPECTION_INTERVALS as $months => $label)
                                    <option value="{{ $months }}" @selected((int) old('inspection_interval_months', $asset->inspection_interval_months ?? 1) === $months)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small>Jadwal dihitung otomatis sejak registrasi dan diperbarui setelah setiap inspeksi.</small>
                        </label>
                        <label class="ui-field admin-field ui-field--full">
                            <span class="ui-field__label">Wajib kalibrasi? <em>Wajib</em></span>
                            <select name="requires_calibration" required data-requires-calibration>
                                <option value="0" @selected($requiresCalibration === '0')>Tidak, gunakan label aset umum</option>
                                <option value="1" @selected($requiresCalibration === '1')>Ya, gunakan label kalibrasi</option>
                            </select>
                            <small>Kategori tidak menentukan kalibrasi. Setiap aset tetap dapat diwajibkan kalibrasi sesuai kebutuhannya.</small>
                        </label>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Daftar pemeriksaan</h2>
                        <p>Setiap item akan menjadi pilihan Ya atau Tidak saat QR dibuka oleh pengguna yang memiliki izin inspeksi.</p>
                    </div>
                    <button class="button button--outline admin-button" type="button" data-checklist-add>+ Tambah item</button>
                </header>
                <div class="admin-panel__body">
                    <div class="asset-checklist-editor" data-checklist-list>
                        @foreach ($checklistItems as $item)
                            <div class="asset-checklist-editor__row" data-checklist-row>
                                <span data-checklist-number>{{ $loop->iteration }}</span>
                                <input type="text" name="checklist_items[]" value="{{ $item }}" maxlength="255" placeholder="Contoh: Kabel dan konektor dalam kondisi baik" required>
                                <button type="button" data-checklist-remove>Hapus</button>
                            </div>
                        @endforeach
                    </div>
                    @if ($errors->has('checklist_items') || $errors->has('checklist_items.*'))
                        <small class="ui-field__error">{{ $errors->first('checklist_items') ?: $errors->first('checklist_items.*') }}</small>
                    @endif
                    <p class="asset-checklist-editor__hint">Minimal 1 item dan maksimal 30 item untuk setiap aset.</p>
                </div>
            </section>

            <section class="admin-panel admin-asset-calibration" data-calibration-fields @if ($requiresCalibration !== '1') hidden @endif>
                <header class="admin-panel__header">
                    <div>
                        <h2>Data kalibrasi</h2>
                        <p>Wajib lengkap saat status alat Aktif. Saat sedang dikalibrasi, tanggal dan sertifikat dapat dilengkapi kemudian.</p>
                    </div>
                    <x-admin.status-badge status="calibrated">Label kalibrasi</x-admin.status-badge>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input
                            label="Tanggal kalibrasi terakhir"
                            name="calibrated_at"
                            type="date"
                            :value="$asset->calibrated_at?->format('Y-m-d')"
                            data-calibration-active
                        />
                        <x-ui.text-input
                            label="Jatuh tempo kalibrasi"
                            name="calibration_due_at"
                            type="date"
                            :value="$asset->calibration_due_at?->format('Y-m-d')"
                            hint="Harus sama atau setelah tanggal kalibrasi."
                            data-calibration-active
                        />
                        <x-ui.text-input
                            wrapper-class="ui-field--full"
                            label="Nomor sertifikat kalibrasi"
                            name="certificate_number"
                            :value="$asset->certificate_number"
                            placeholder="Contoh: CAL-UT-2026-002"
                            maxlength="100"
                            data-calibration-active
                        />
                        <div class="ui-field--full asset-calibration-certificate">
                            <x-ui.file-input
                                label="File sertifikat kalibrasi"
                                name="calibration_certificate"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                hint="PDF, JPG, JPEG, atau PNG. Maksimal 10 MB."
                            />

                            @if ($asset->calibration_certificate_path)
                                <div class="asset-calibration-certificate__current">
                                    <span aria-hidden="true"><x-ui.icon name="file" size="22" /></span>
                                    <div>
                                        <strong>{{ $asset->calibration_certificate_name ?? 'Sertifikat kalibrasi' }}</strong>
                                        <small>
                                            File tersimpan
                                            @if ($asset->calibration_certificate_size)
                                                | {{ number_format($asset->calibration_certificate_size / 1024, 1) }} KB
                                            @endif
                                        </small>
                                    </div>
                                    <a class="button button--outline admin-button" href="{{ $asset->calibrationCertificateUrl() }}" target="_blank" rel="noopener">
                                        <x-ui.icon name="eye" size="16" /> Lihat file
                                    </a>
                                    <label>
                                        <input type="checkbox" name="remove_calibration_certificate" value="1">
                                        <span>Hapus file saat disimpan</span>
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Catatan internal</h2>
                        <p>Opsional dan tidak ditampilkan pada label atau halaman QR publik.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <label class="ui-field admin-field">
                        <span class="ui-field__label">Catatan</span>
                        <textarea name="notes" rows="4" maxlength="2000" placeholder="Kondisi khusus, informasi pembelian, atau catatan aset.">{{ old('notes', $asset->notes) }}</textarea>
                        @error('notes')<small class="ui-field__error">{{ $message }}</small>@enderror
                    </label>
                </div>
            </section>
        </div>

        <div class="admin-form-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.assets.index') }}">Batal</a>
            <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Generate Asset ID & simpan' }}</button>
        </div>
    </form>

    <x-ui.modal
        id="asset-photo-editor"
        title="Atur foto aset"
        description="Geser atau perbesar foto di dalam bingkai. Hasil rasio 4:3 akan digunakan pada daftar, halaman QR, dan formulir inspeksi."
        size="large"
    >
        <div class="asset-photo-cropper-layout">
            <div class="asset-photo-cropper-stage" data-asset-photo-cropper></div>
            <div class="asset-photo-cropper-controls" aria-label="Kontrol komposisi foto aset">
                <button class="button button--outline admin-button" type="button" data-asset-photo-crop-zoom-out aria-label="Perkecil foto">&minus; Perkecil</button>
                <button class="button button--outline admin-button" type="button" data-asset-photo-crop-zoom-in aria-label="Perbesar foto">+ Perbesar</button>
                <button class="button button--outline admin-button" type="button" data-asset-photo-crop-reset>Atur ulang</button>
            </div>
            <p class="asset-photo-cropper-status" data-asset-photo-crop-status aria-live="polite">Geser foto agar seluruh bagian penting alat terlihat.</p>
        </div>
        <x-slot:footer>
            <button class="button button--outline admin-button" type="button" data-modal-close data-asset-photo-crop-cancel>Batal</button>
            <button class="button button--primary admin-button" type="button" data-asset-photo-crop-apply disabled>Gunakan foto ini</button>
        </x-slot:footer>
    </x-ui.modal>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
@endpush

@push('scripts')
    @vite('resources/js/assets.js')
    @vite('resources/js/asset-photo.js')
@endpush
