@php($brand = config('branding'))
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title>Label Aset · {{ $brand['name'] }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
        @vite('resources/js/assets.js')
    </head>
    <body class="asset-label-page">
        <header class="asset-print-toolbar">
            <div>
                <a href="{{ route('admin.assets.index') }}">← Kembali ke aset</a>
                <strong>Pratinjau label aset</strong>
                <span>{{ $assets->count() }} label · <span data-label-size-summary>Standar 90 x 42 mm</span></span>
            </div>
            <div class="asset-print-toolbar__actions">
                <label class="asset-label-size-picker">
                    <span>Ukuran label</span>
                    <select data-label-size-select>
                        <option value="standard">Standar 90 x 42 mm</option>
                        <option value="compact">Ringkas 60 x 31 mm</option>
                    </select>
                </label>
                <button type="button" data-print-labels disabled>
                    <x-ui.icon name="printer" size="17" /> Cetak label
                </button>
            </div>
        </header>

        <main class="asset-label-sheet" aria-label="Label aset siap cetak" data-label-sheet>
            @foreach ($assets as $asset)
                <article @class(['asset-sticker', 'asset-sticker--measuring' => $asset->requires_calibration])>
                    <header class="asset-sticker__header">
                        <svg class="asset-sticker__header-background" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                            <rect width="100" height="100" fill="#071b32" />
                        </svg>
                        <img src="{{ asset($brand['logo']) }}" alt="">
                        <div>
                            <strong>{{ $brand['name'] }}</strong>
                            <small>{{ $brand['service'] }}</small>
                        </div>
                    </header>

                    <div class="asset-sticker__body">
                        <dl class="asset-sticker__details">
                            <div>
                                <dt>ASSET ID</dt>
                                <dd>{{ $asset->asset_code }}</dd>
                            </div>
                            <div>
                                <dt>EQUIPMENT</dt>
                                <dd>{{ $asset->equipment_name }}</dd>
                            </div>
                            @if ($asset->requires_calibration)
                                <div>
                                    <dt>SERIAL NO</dt>
                                    <dd>{{ $asset->serial_number }}</dd>
                                </div>
                                <div>
                                    <dt>CAL. DATE</dt>
                                    <dd>{{ $asset->calibrated_at?->format('d-m-Y') ?? 'Belum diisi' }}</dd>
                                </div>
                                <div>
                                    <dt>DUE DATE</dt>
                                    <dd>{{ $asset->calibration_due_at?->format('d-m-Y') ?? 'Belum diisi' }}</dd>
                                </div>
                                <div>
                                    <dt>CERT. NO</dt>
                                    <dd>{{ $asset->certificate_number ?? 'Belum diisi' }}</dd>
                                </div>
                            @else
                                <div>
                                    <dt>LOCATION</dt>
                                    <dd>{{ $asset->location }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div class="asset-sticker__qr" data-qr-value="{{ route('assets.verify', ['asset' => $asset->public_id]) }}" data-qr-label="{{ $asset->asset_code }}">
                            <span>Menyiapkan QR…</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </main>
    </body>
</html>
