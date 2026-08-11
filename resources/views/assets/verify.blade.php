@php($brand = config('branding'))
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <meta name="description" content="Verifikasi aset {{ $asset->asset_code }} milik {{ $brand['name'] }}">
        <title>Verifikasi {{ $asset->asset_code }} | {{ $brand['name'] }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
    </head>
    <body class="asset-verification-page">
        <main class="asset-verification-card">
            <header class="asset-verification-card__brand">
                <img src="{{ asset($brand['logo']) }}" alt="Logo {{ $brand['name'] }}">
                <div>
                    <strong>{{ $brand['name'] }}</strong>
                    <small>{{ $brand['service'] }}</small>
                </div>
            </header>

            <section class="asset-verification-card__heading">
                <span aria-hidden="true"><x-ui.icon name="check-circle" size="23" /></span>
                <div>
                    <small>ASET TERVERIFIKASI</small>
                    <h1>{{ $asset->asset_code }}</h1>
                    <p>{{ $asset->equipment_name }}</p>
                </div>
            </section>

            @if ($asset->photoUrl())
                <figure class="asset-verification-photo">
                    <img src="{{ $asset->photoUrl() }}" alt="Foto {{ $asset->equipment_name }}">
                </figure>
            @endif

            <dl class="asset-verification-details">
                <div><dt>Kategori</dt><dd>{{ $asset->category_code }} | {{ $asset->categoryLabel() }}</dd></div>
                @if ($asset->brand || $asset->model)
                    <div><dt>Merek / model</dt><dd>{{ collect([$asset->brand, $asset->model])->filter()->join(' | ') }}</dd></div>
                @endif
                @if ($asset->serial_number)
                    <div><dt>Serial number</dt><dd>{{ $asset->serial_number }}</dd></div>
                @endif
                <div><dt>Jumlah</dt><dd>{{ number_format($asset->quantity) }} unit</dd></div>
                @if ($asset->purchase_year)
                    <div><dt>Tahun pembelian</dt><dd>{{ $asset->purchase_year }}</dd></div>
                @endif
                <div><dt>Status alat</dt><dd><span class="asset-verification-status asset-verification-status--{{ $asset->statusTone() }}">{{ $asset->statusLabel() }}</span></dd></div>
                <div><dt>Kondisi</dt><dd>{{ $asset->conditionLabel() }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $asset->location }}</dd></div>
                <div><dt>Interval inspeksi</dt><dd>{{ $asset->inspectionIntervalLabel() }}</dd></div>
                <div><dt>Inspeksi terakhir</dt><dd>{{ $asset->last_inspected_at?->translatedFormat('d F Y') ?? 'Belum dicatat' }}</dd></div>
                <div><dt>Inspeksi berikutnya</dt><dd>{{ $asset->next_inspection_at?->translatedFormat('d F Y') ?? 'Belum dijadwalkan' }}</dd></div>
            </dl>

            @if ($asset->requires_calibration)
                <section class="asset-verification-calibration">
                    <header>
                        <div>
                            <small>STATUS KALIBRASI</small>
                            <strong>{{ $asset->calibrationStatusLabel() }}</strong>
                        </div>
                        <span class="asset-verification-status asset-verification-status--{{ $asset->calibrationTone() }}">{{ strtoupper($asset->calibrationStatusLabel()) }}</span>
                    </header>
                    <dl class="asset-verification-details">
                        <div><dt>Kalibrasi terakhir</dt><dd>{{ $asset->calibrated_at?->translatedFormat('d F Y') ?? 'Belum dicatat' }}</dd></div>
                        <div><dt>Kalibrasi berikutnya</dt><dd>{{ $asset->calibration_due_at?->translatedFormat('d F Y') ?? 'Belum dijadwalkan' }}</dd></div>
                        <div><dt>Nomor sertifikat</dt><dd>{{ $asset->certificate_number ?? 'Belum tersedia' }}</dd></div>
                        @if ($asset->calibrationCertificateUrl())
                            <div>
                                <dt>File sertifikat</dt>
                                <dd>
                                    <a class="asset-verification-document" href="{{ $asset->calibrationCertificateUrl() }}" target="_blank" rel="noopener">
                                        <x-ui.icon name="file" size="16" /> Buka sertifikat kalibrasi
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif

            <footer>
                <span>Data terakhir diperbarui</span>
                <strong>{{ $asset->updated_at->translatedFormat('d F Y, H:i') }} WIB</strong>
                <small>{{ $brand['company'] }}</small>
            </footer>
        </main>
    </body>
</html>
