@php($brand = config('branding'))
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Inspeksi {{ $asset->asset_code }} | {{ $brand['name'] }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/components.css') }}?v={{ filemtime(public_path('templates/welding-school/components.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
    </head>
    <body class="asset-inspection-page">
        <main class="asset-inspection-shell">
            <header class="asset-inspection-brand">
                <a href="{{ auth()->user()->isAdmin() && auth()->user()->can('assets.view') ? route('admin.assets.dashboard') : route('home') }}">
                    <img src="{{ asset($brand['logo']) }}" alt="Logo {{ $brand['name'] }}">
                    <span><strong>{{ $brand['name'] }}</strong><small>Inspeksi dan maintenance aset</small></span>
                </a>
                <span class="asset-inspection-user">{{ auth()->user()->name }}</span>
            </header>

            <section @class(['asset-inspection-summary', 'has-photo' => $asset->photoUrl()])>
                @if ($asset->photoUrl())
                    <img class="asset-inspection-summary__photo" src="{{ $asset->photoUrl() }}" alt="Foto {{ $asset->equipment_name }}">
                @endif
                <div>
                    <small>ASSET ID</small>
                    <h1>{{ $asset->asset_code }}</h1>
                    <p>{{ $asset->equipment_name }}</p>
                </div>
                <dl>
                    <div><dt>Lokasi</dt><dd>{{ $asset->location }}</dd></div>
                    <div><dt>Jadwal</dt><dd>{{ $asset->inspectionIntervalLabel() }}</dd></div>
                    <div><dt>Kondisi saat ini</dt><dd>{{ $asset->conditionLabel() }}</dd></div>
                    <div><dt>Status saat ini</dt><dd>{{ $asset->statusLabel() }}</dd></div>
                </dl>
            </section>

            @if (session('success'))
                <button type="button" hidden data-flash-toast data-toast="{{ session('success') }}" data-toast-type="success"></button>
            @endif

            @if ($errors->any())
                <button type="button" hidden data-flash-toast data-toast="{{ implode(' • ', $errors->all()) }}" data-toast-type="danger"></button>
            @endif

            <form class="asset-inspection-form" method="POST" action="{{ route('assets.inspections.store', ['asset' => $asset->public_id]) }}">
                @csrf

                <section class="asset-inspection-panel">
                    <header>
                        <div>
                            <span>1</span>
                            <div><h2>Checklist maintenance</h2><p>Jawab seluruh item sesuai kondisi alat yang diperiksa.</p></div>
                        </div>
                        <small>{{ $asset->checklistItems->count() }} item</small>
                    </header>
                    <div class="asset-inspection-checklist">
                        @forelse ($asset->checklistItems as $item)
                            <fieldset>
                                <legend><span>{{ $loop->iteration }}</span>{{ $item->label }}</legend>
                                <div class="asset-inspection-answer">
                                    <label>
                                        <input type="radio" name="answers[{{ $item->id }}]" value="1" @checked((string) old("answers.{$item->id}") === '1') required>
                                        <span>Ya</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="answers[{{ $item->id }}]" value="0" @checked((string) old("answers.{$item->id}") === '0') required>
                                        <span>Tidak</span>
                                    </label>
                                </div>
                            </fieldset>
                        @empty
                            <p class="asset-inspection-empty">Daftar pemeriksaan belum tersedia. Hubungi pengelola aset.</p>
                        @endforelse
                    </div>
                </section>

                <section class="asset-inspection-panel">
                    <header>
                        <div>
                            <span>2</span>
                            <div><h2>Hasil kondisi alat</h2><p>Perbarui kondisi dan status berdasarkan pemeriksaan.</p></div>
                        </div>
                    </header>
                    <div class="asset-inspection-fields">
                        <label>
                            <span>Kondisi alat</span>
                            <select name="condition" required>
                                @foreach (\App\Models\Asset::CONDITIONS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('condition', $asset->condition) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span>Status alat</span>
                            <select name="status" required>
                                @foreach (\App\Models\Asset::STATUSES as $value => $label)
                                    @if ($value !== 'under_calibration' || $asset->requires_calibration)
                                        <option value="{{ $value }}" @selected(old('status', $asset->status) === $value)>{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </label>
                        <label class="asset-inspection-fields__full">
                            <span>Catatan inspeksi</span>
                            <textarea name="notes" rows="4" maxlength="2000" placeholder="Tuliskan temuan, tindakan, atau kebutuhan perbaikan jika ada.">{{ old('notes') }}</textarea>
                        </label>
                    </div>
                </section>

                <div class="asset-inspection-submit">
                    <p>Inspeksi berikutnya akan dijadwalkan otomatis pada {{ today()->addMonthsNoOverflow((int) $asset->inspection_interval_months)->translatedFormat('d F Y') }}.</p>
                    <button type="submit" @disabled($asset->checklistItems->isEmpty())>Simpan hasil inspeksi</button>
                </div>
            </form>

            <section class="asset-inspection-history">
                <header><h2>Riwayat inspeksi</h2><p>Lima pemeriksaan terbaru untuk aset ini.</p></header>
                @forelse ($asset->inspections as $inspection)
                    <article>
                        <div class="asset-inspection-history__heading">
                            <div>
                                <strong>{{ $inspection->inspected_at->translatedFormat('d F Y, H:i') }} WIB</strong>
                                <small>Diperiksa oleh {{ $inspection->inspector?->name ?? 'Pengguna yang sudah tidak aktif' }}</small>
                            </div>
                            <span>{{ \App\Models\Asset::CONDITIONS[$inspection->condition] ?? $inspection->condition }}</span>
                        </div>
                        <ul>
                            @foreach ($inspection->results as $result)
                                <li class="{{ $result->is_ok ? 'is-ok' : 'is-not-ok' }}"><span>{{ $result->is_ok ? 'Ya' : 'Tidak' }}</span>{{ $result->item_label }}</li>
                            @endforeach
                        </ul>
                        @if ($inspection->notes)
                            <p>{{ $inspection->notes }}</p>
                        @endif
                    </article>
                @empty
                    <p class="asset-inspection-empty">Belum ada riwayat inspeksi.</p>
                @endforelse
            </section>
        </main>
        <x-ui.loading />
        <x-ui.toast-stack />
        <script src="{{ asset('templates/welding-school/components.js') }}?v={{ filemtime(public_path('templates/welding-school/components.js')) }}" defer></script>
        <script src="{{ asset('templates/welding-school/loading.js') }}?v={{ filemtime(public_path('templates/welding-school/loading.js')) }}" defer></script>
    </body>
</html>
