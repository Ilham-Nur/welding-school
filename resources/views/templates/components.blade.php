@extends('layouts.public')

@section('title', 'Katalog Komponen UI '.config('branding.name'))
@section('description', 'Katalog komponen Blade untuk pengembangan '.config('branding.name').'.')
@section('robots', 'noindex, nofollow')
@section('body-class', 'component-library-page')

@section('content')
    <main id="app" tabindex="-1">
        <section class="component-hero">
            <div class="page-shell component-hero__layout">
                <div>
                    <span class="eyebrow eyebrow--light">Blade UI Kit · Versi 1.0</span>
                    <h1>Komponen siap pakai untuk pengembangan aplikasi.</h1>
                    <p>
                        Pola tampilan inti {{ config('branding.name') }} sudah dipindahkan ke Blade.
                        Gunakan katalog ini sebagai sumber komponen, state, dan perilaku antarmuka.
                    </p>
                    <div class="component-hero__actions">
                        <a class="button button--primary button--large" href="#form-components">Lihat Komponen</a>
                        <a class="button component-button--dark button--large" href="{{ asset('templates/welding-school/index.html') }}" target="_blank" rel="noreferrer">Buka Template Asli ↗</a>
                    </div>
                </div>
                <dl class="component-stats">
                    <div><dt>9</dt><dd>Komponen inti</dd></div>
                    <div><dt>Blade</dt><dd>Siap digunakan ulang</dd></div>
                    <div><dt>MySQL</dt><dd>Sumber data utama</dd></div>
                </dl>
            </div>
        </section>

        <nav class="component-anchor-nav" aria-label="Daftar komponen">
            <div class="page-shell">
                <a href="#alerts">Alert</a>
                <a href="#form-components">Form & Input</a>
                <a href="#file-input">Input File</a>
                <a href="#table-components">Tabel</a>
                <a href="#pagination">Pagination</a>
                <a href="#dialogs">Modal</a>
                <a href="#loading">Loading</a>
                <a href="#toasts">Toast</a>
                <a href="#dialogs">Konfirmasi</a>
            </div>
        </nav>

        <div class="page-shell component-library">
            <section id="alerts" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Feedback</span>
                        <h2>Alert</h2>
                        <p>Pesan kontekstual untuk status sistem, validasi, dan informasi penting.</p>
                    </div>
                    <span class="component-code">&lt;x-ui.alert /&gt;</span>
                </div>

                <div class="component-stack">
                    <x-ui.alert type="success" title="Pendaftaran berhasil disimpan" dismissible>
                        Data peserta telah tersimpan dan siap masuk ke tahap verifikasi dokumen.
                    </x-ui.alert>
                    <x-ui.alert type="info" title="Jadwal telah diperbarui">
                        Batch Agustus dimulai pada 10 Agustus 2026 pukul 08.00 WIB.
                    </x-ui.alert>
                    <x-ui.alert type="warning" title="Dokumen hampir kedaluwarsa" dismissible>
                        Sertifikat K3 akan kedaluwarsa dalam 14 hari. Unggah dokumen terbaru.
                    </x-ui.alert>
                    <x-ui.alert type="danger" title="Pembayaran tidak dapat diproses">
                        Periksa kembali metode pembayaran atau hubungi administrator.
                    </x-ui.alert>
                </div>
            </section>

            <section id="form-components" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Data Entry</span>
                        <h2>Form dan input data</h2>
                        <p>Field dengan label, hint, state wajib, pilihan, dan validasi browser.</p>
                    </div>
                    <span class="component-code">&lt;x-ui.text-input /&gt;</span>
                </div>

                <form class="ui-form-card" data-demo-form>
                    <div class="ui-form-card__header">
                        <div>
                            <h3>Data calon peserta</h3>
                            <p>Contoh struktur formulir dua kolom yang responsif.</p>
                        </div>
                        <span class="badge badge--orange-soft">Contoh Form</span>
                    </div>

                    <div class="ui-form-grid">
                        <x-ui.text-input
                            label="Nama lengkap"
                            name="full_name"
                            placeholder="Sesuai kartu identitas"
                            hint="Gunakan nama tanpa gelar."
                            required
                        />
                        <x-ui.text-input
                            label="Alamat email"
                            name="email"
                            type="email"
                            placeholder="nama@email.com"
                            required
                        />
                        <x-ui.text-input
                            label="Nomor telepon"
                            name="phone"
                            type="tel"
                            placeholder="08xx xxxx xxxx"
                            required
                        />
                        <label class="ui-field">
                            <span class="ui-field__label">Program pelatihan <em>Wajib</em></span>
                            <select name="program" required>
                                <option value="">Pilih program</option>
                                <option>SMAW Welder 3G</option>
                                <option>FCAW Welder 3G</option>
                                <option>GTAW Welder 6G</option>
                            </select>
                        </label>
                        <x-ui.text-input
                            label="Tanggal lahir"
                            name="birth_date"
                            type="date"
                            required
                        />
                        <x-ui.text-input
                            label="Pengalaman kerja"
                            name="experience"
                            type="number"
                            value="0"
                            min="0"
                            max="50"
                            hint="Dalam satuan tahun."
                        />
                        <label class="ui-field ui-field--full">
                            <span class="ui-field__label">Alamat lengkap</span>
                            <textarea name="address" rows="4" placeholder="Nama jalan, kecamatan, kota, dan kode pos"></textarea>
                        </label>
                    </div>

                    <div class="ui-form-card__footer">
                        <label class="ui-check">
                            <input type="checkbox" name="agreement" required>
                            <span>Saya memastikan data yang dimasukkan sudah benar.</span>
                        </label>
                        <div>
                            <button class="button button--outline" type="reset">Bersihkan</button>
                            <button class="button button--primary" type="submit">Simpan Data <span>→</span></button>
                        </div>
                    </div>
                </form>
            </section>

            <section id="file-input" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Upload</span>
                        <h2>Input file</h2>
                        <p>Area unggah yang mendukung klik, drag-and-drop, nama file, dan validasi ukuran.</p>
                    </div>
                    <span class="component-code">&lt;x-ui.file-input /&gt;</span>
                </div>

                <div class="ui-form-card">
                    <div class="ui-form-grid">
                        <x-ui.file-input label="Kartu identitas" name="identity_card" required />
                        <x-ui.file-input label="Sertifikat pendukung" name="certificate" hint="Opsional · PDF maksimal 5 MB." />
                    </div>
                </div>
            </section>

            <section id="table-components" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Data Display</span>
                        <h2>Tabel data</h2>
                        <p>Tabel responsif dengan pencarian, filter status, empty state, dan aksi baris.</p>
                    </div>
                    <span class="component-code">&lt;x-ui.table /&gt;</span>
                </div>

                <div class="ui-data-card">
                    <form class="ui-table-toolbar" method="GET" action="{{ route('template.components') }}#table-components">
                        <label class="ui-search">
                            <span aria-hidden="true">⌕</span>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Cari kode, program, atau kategori..." aria-label="Cari program">
                        </label>
                        <label class="ui-filter">
                            <span>Status</span>
                            <select name="status" onchange="this.form.submit()">
                                <option value="">Semua status</option>
                                <option value="active" @selected($status === 'active')>Aktif</option>
                                <option value="draft" @selected($status === 'draft')>Draft</option>
                                <option value="closed" @selected($status === 'closed')>Ditutup</option>
                            </select>
                        </label>
                        <button class="button button--outline" type="submit">Terapkan</button>
                        @if ($search !== '' || $status !== '')
                            <a class="text-link" href="{{ route('template.components') }}#table-components">Reset</a>
                        @endif
                    </form>

                    <x-ui.table>
                        <thead>
                            <tr>
                                <th scope="col">Program</th>
                                <th scope="col">Kategori</th>
                                <th scope="col">Durasi</th>
                                <th scope="col">Biaya</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($programs as $program)
                                @php
                                    $statusLabel = match ($program->status) {
                                        'active' => 'Aktif',
                                        'closed' => 'Ditutup',
                                        default => 'Draft',
                                    };
                                    $statusClass = match ($program->status) {
                                        'active' => 'badge--green',
                                        'closed' => 'badge--red-soft',
                                        default => 'badge--gray',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $program->title }}</strong>
                                        <small>{{ $program->code }}</small>
                                    </td>
                                    <td>{{ $program->category }}</td>
                                    <td>{{ $program->duration_hours }} jam</td>
                                    <td>Rp{{ number_format($program->price, 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td>
                                        <button class="ui-row-action" type="button" data-toast="Program {{ $program->code }} dibuka untuk diedit." data-toast-type="info" aria-label="Buka aksi {{ $program->title }}">•••</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="ui-empty-state">
                                            <span aria-hidden="true">⌕</span>
                                            <strong>Program tidak ditemukan</strong>
                                            <p>Ubah kata kunci atau hapus filter untuk melihat data lainnya.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-ui.table>

                    <div id="pagination" class="ui-data-card__footer">
                        <p>Menampilkan {{ $programs->firstItem() ?? 0 }} sampai {{ $programs->lastItem() ?? 0 }} dari {{ $programs->total() }} program</p>
                        <x-ui.pagination :paginator="$programs" />
                    </div>
                </div>
            </section>

            <section id="dialogs" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Overlay</span>
                        <h2>Modal dan confirmation</h2>
                        <p>Dialog native yang aksesibel, dapat ditutup lewat tombol, backdrop, atau tombol Escape.</p>
                    </div>
                    <span class="component-code">&lt;x-ui.modal /&gt;</span>
                </div>

                <div class="component-demo-row">
                    <div class="component-demo-card">
                        <span class="component-demo-card__icon">▤</span>
                        <div>
                            <h3>Modal formulir</h3>
                            <p>Untuk tugas yang membutuhkan fokus tanpa berpindah halaman.</p>
                        </div>
                        <button class="button button--primary" type="button" data-modal-open="batch-modal">Buka Modal</button>
                    </div>
                    <div class="component-demo-card">
                        <span class="component-demo-card__icon component-demo-card__icon--danger">!</span>
                        <div>
                            <h3>Confirmation dialog</h3>
                            <p>Memastikan tindakan penting sebelum perubahan dijalankan.</p>
                        </div>
                        <button class="button button--outline ui-button--danger-outline" type="button" data-modal-open="delete-confirmation">Hapus Data</button>
                    </div>
                </div>
            </section>

            <section id="loading" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Process feedback</span>
                        <h2>Loading global</h2>
                        <p>Umpan balik bermerek untuk submit, upload, navigasi, AJAX, dan penyiapan file.</p>
                    </div>
                    <span class="component-code">window.AppLoading</span>
                </div>

                <div class="component-demo-row">
                    <div class="component-demo-card">
                        <span class="component-demo-card__icon">↻</span>
                        <div>
                            <h3>Proses halaman</h3>
                            <p>Overlay menjaga pengguna tetap mendapat konteks selama server menyelesaikan permintaan.</p>
                        </div>
                        <button
                            class="button button--primary"
                            type="button"
                            data-loading-preview
                            data-loading-title="Menyiapkan laporan"
                            data-loading-message="Data sedang diproses. Simulasi akan selesai otomatis."
                        >Lihat Loading</button>
                    </div>
                </div>
            </section>

            <section id="toasts" class="component-section">
                <div class="component-section__heading">
                    <div>
                        <span class="eyebrow">Notification</span>
                        <h2>Toast</h2>
                        <p>Notifikasi singkat non-blocking yang otomatis hilang dan dapat ditutup manual.</p>
                    </div>
                    <span class="component-code">data-toast="Pesan"</span>
                </div>

                <div class="component-demo-row component-demo-row--compact">
                    <button class="button button--outline" type="button" data-toast="Informasi terbaru berhasil dimuat." data-toast-type="info">Toast Info</button>
                    <button class="button button--outline" type="button" data-toast="Data program berhasil disimpan." data-toast-type="success">Toast Sukses</button>
                    <button class="button button--outline" type="button" data-toast="Periksa kembali kelengkapan dokumen." data-toast-type="warning">Toast Peringatan</button>
                    <button class="button button--outline" type="button" data-toast="Terjadi kesalahan saat memproses data." data-toast-type="danger">Toast Error</button>
                </div>
            </section>
        </div>
    </main>

    <x-ui.modal
        id="batch-modal"
        title="Tambah batch pelatihan"
        description="Lengkapi jadwal dan kapasitas kelas baru."
    >
        <form class="ui-form-grid" data-modal-form>
            <x-ui.text-input label="Nama batch" name="batch_name" value="Batch November 2026" required />
            <x-ui.text-input label="Tanggal mulai" name="start_date" type="date" required />
            <x-ui.text-input label="Kapasitas peserta" name="capacity" type="number" value="12" min="1" max="30" required />
            <label class="ui-field">
                <span class="ui-field__label">Lokasi workshop <em>Wajib</em></span>
                <select name="location" required>
                    <option>Workshop Batam Centre · Area 1</option>
                    <option>Workshop Batam Centre · Area 2</option>
                </select>
            </label>
        </form>
        <x-slot:footer>
            <button class="button button--outline" type="button" data-modal-close>Batal</button>
            <button class="button button--primary" type="button" data-modal-save>Simpan Batch</button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirmation id="delete-confirmation" title="Hapus program pelatihan?" confirm-label="Ya, hapus data">
        <p>Data yang dihapus tidak akan ditampilkan lagi pada katalog program. Tindakan ini tidak dapat dibatalkan.</p>
    </x-ui.confirmation>

    <x-ui.toast-stack />
@endsection

@push('scripts')
    <script src="{{ asset('templates/welding-school/components.js') }}" defer></script>
@endpush
