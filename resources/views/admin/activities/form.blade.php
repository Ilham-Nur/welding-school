@extends('layouts.admin')

@php($editing = $activity->exists)

@section('title', $editing ? 'Edit Aktivitas' : 'Upload Aktivitas')
@section('eyebrow', 'Konten publik')
@section('heading', $editing ? 'Edit Aktivitas' : 'Upload Aktivitas')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>{{ $editing ? 'Edit aktivitas' : 'Upload aktivitas baru' }}</h1>
            <p>Informasi ini akan digunakan pada kartu aktivitas, aktivitas unggulan, dan halaman detail.</p>
        </div>
        <a class="button button--outline admin-button" href="{{ route('admin.activities.index') }}">&larr; Kembali</a>
    </section>

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.activities.update', $activity) : route('admin.activities.store') }}" data-activity-form>
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="admin-activity-form-layout">
            <section class="admin-panel">
                <header class="admin-panel__header">
                    <div>
                        <h2>Isi aktivitas</h2>
                        <p>Gunakan ringkasan singkat untuk kartu dan isi lengkap untuk halaman detail.</p>
                    </div>
                </header>
                <div class="admin-panel__body">
                    <div class="admin-form-grid">
                        <x-ui.text-input wrapper-class="ui-field--full" label="Judul aktivitas" name="title" :value="$activity->title" placeholder="Contoh: Peserta Batch Agustus menyelesaikan uji kompetensi" maxlength="255" required />
                        <x-ui.text-input wrapper-class="ui-field--full" label="Kategori" name="category" :value="$activity->category" placeholder="Pelatihan, Safety, Alumni, atau Kolaborasi Industri" list="activity-categories" maxlength="80" required />
                        <datalist id="activity-categories">
                            <option value="Kegiatan Akademi"></option>
                            <option value="Pelatihan"></option>
                            <option value="Safety"></option>
                            <option value="Alumni"></option>
                            <option value="Kolaborasi Industri"></option>
                            <option value="Event"></option>
                        </datalist>
                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Ringkasan <em>Wajib</em></span>
                            <textarea name="excerpt" rows="4" maxlength="500" placeholder="Ringkasan singkat yang menarik untuk kartu aktivitas." required>{{ old('excerpt', $activity->excerpt) }}</textarea>
                            <small>Maksimal 500 karakter.</small>
                            @error('excerpt')<small class="ui-field__error">{{ $message }}</small>@enderror
                        </label>
                        <label class="ui-field admin-field admin-field--full">
                            <span class="ui-field__label">Isi lengkap <em>Wajib</em></span>
                            <textarea name="content" rows="12" maxlength="30000" placeholder="Tulis isi aktivitas. Pisahkan paragraf dengan satu baris kosong." required>{{ old('content', $activity->content) }}</textarea>
                            <small>Setiap paragraf yang dipisahkan baris kosong akan ditampilkan sebagai paragraf baru.</small>
                            @error('content')<small class="ui-field__error">{{ $message }}</small>@enderror
                        </label>
                    </div>
                </div>
            </section>

            <aside class="admin-activity-form-sidebar">
                <section class="admin-panel">
                    <header class="admin-panel__header">
                        <div>
                            <h2>Foto utama</h2>
                            <p>Rasio 16:9 disarankan agar kartu tampil optimal.</p>
                        </div>
                    </header>
                    <div class="admin-panel__body">
                        @if ($editing)
                            <img class="admin-activity-image-preview" src="{{ $activity->imageUrl() }}" alt="Pratinjau {{ $activity->title }}" style="object-position: {{ $activity->image_position }}" data-activity-image-preview>
                        @else
                            <img class="admin-activity-image-preview" alt="Pratinjau foto aktivitas" data-activity-image-preview hidden>
                        @endif

                        <x-ui.file-input label="Upload foto" name="image" accept=".jpg,.jpeg,.png,.webp" hint="JPG, PNG, atau WebP. Maksimal 8 MB." :required="! $editing" data-activity-image-input />

                        <input type="hidden" name="image_position" value="{{ old('image_position', $activity->image_position ?? '50% center') }}" data-activity-image-position>

                        <div class="admin-activity-crop-action">
                            <span>
                                <strong>Komposisi foto 16:9</strong>
                                <small>Geser dan perbesar foto agar subjek utama tetap terlihat pada kartu aktivitas.</small>
                            </span>
                            <button
                                class="button button--outline admin-button"
                                type="button"
                                data-modal-open="activity-image-focus"
                                data-activity-crop-open
                                @disabled(! $editing)
                            >
                                Atur posisi foto
                            </button>
                        </div>

                        <x-ui.text-input label="Teks alternatif foto" name="image_alt" :value="$activity->image_alt" placeholder="Deskripsi singkat isi foto" hint="Opsional. Jika kosong, judul aktivitas akan digunakan." maxlength="255" />
                    </div>
                </section>

                <section class="admin-panel">
                    <header class="admin-panel__header">
                        <div>
                            <h2>Publikasi</h2>
                            <p>Atur kapan aktivitas tersedia di situs.</p>
                        </div>
                    </header>
                    <div class="admin-panel__body admin-activity-publish-fields">
                        <label class="ui-field admin-field">
                            <span class="ui-field__label">Status <em>Wajib</em></span>
                            <select name="status" required>
                                <option value="draft" @selected(old('status', $activity->status ?? 'draft') === 'draft')>Draft</option>
                                <option value="published" @selected(old('status', $activity->status) === 'published')>Terbit</option>
                                <option value="archived" @selected(old('status', $activity->status) === 'archived')>Diarsipkan</option>
                            </select>
                        </label>
                        <x-ui.text-input label="Tanggal dan waktu terbit" name="published_at" type="datetime-local" :value="$activity->published_at?->format('Y-m-d\TH:i')" hint="Jika status Terbit dan kosong, waktu saat ini akan digunakan. Tanggal mendatang akan dijadwalkan." />
                        <label class="admin-activity-featured-check">
                            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $activity->is_featured))>
                            <span>
                                <strong>Jadikan aktivitas unggulan</strong>
                                <small>Aktivitas ini tampil sebagai sorotan utama. Hanya satu aktivitas dapat menjadi unggulan.</small>
                            </span>
                        </label>
                    </div>
                </section>
            </aside>
        </div>

        <div class="admin-form-actions admin-activity-form-actions">
            <a class="button button--outline admin-button" href="{{ route('admin.activities.index') }}">Batal</a>
            <button class="button button--primary admin-button" type="submit">{{ $editing ? 'Simpan perubahan' : 'Upload aktivitas' }}</button>
        </div>
    </form>

    <x-ui.modal
        id="activity-image-focus"
        title="Atur posisi foto"
        description="Geser atau perbesar foto di dalam bingkai. Area 16:9 ini akan digunakan pada kartu dan halaman aktivitas."
        size="large"
    >
        <div class="admin-activity-cropper-layout">
            <div class="admin-activity-cropper-stage" data-activity-cropper></div>
            <div class="admin-activity-cropper-controls" aria-label="Kontrol komposisi foto">
                <button class="button button--outline admin-button" type="button" data-activity-crop-zoom-out aria-label="Perkecil foto">&minus; Perkecil</button>
                <button class="button button--outline admin-button" type="button" data-activity-crop-zoom-in aria-label="Perbesar foto">+ Perbesar</button>
                <button class="button button--outline admin-button" type="button" data-activity-crop-reset>Atur ulang</button>
            </div>
            <p class="admin-activity-cropper-status" data-activity-crop-status aria-live="polite">Geser foto untuk menentukan bagian yang tetap terlihat.</p>
        </div>
        <x-slot:footer>
            <button class="button button--outline admin-button" type="button" data-modal-close data-activity-crop-cancel>Batal</button>
            <button class="button button--primary admin-button" type="button" data-activity-crop-apply disabled>Gunakan posisi ini</button>
        </x-slot:footer>
    </x-ui.modal>
@endsection

@push('scripts')
    @vite('resources/js/activity-focus.js')
@endpush
