@php
    $result = $minimum['profit_at_target_price'];
    $tone = $result > 0 ? 'profit' : ($result < 0 ? 'loss' : 'even');
    $label = $result > 0 ? 'Untung' : ($result < 0 ? 'Rugi' : 'Impas');
@endphp
<div class="rab-financial-notes">
    <section class="rab-assumptions" aria-labelledby="rab-assumptions-title">
        <h3 id="rab-assumptions-title">Asumsi biaya & pembayaran</h3>
        <dl class="rab-assumption-grid">
            <div><dt>Markup layanan</dt><dd>{{ $budget->inputs['markup_percent'] }}%</dd></div>
            <div><dt>Cadangan</dt><dd>{{ $budget->inputs['contingency_percent'] }}%</dd></div>
            <div><dt>Tarif gateway</dt><dd>{{ $budget->inputs['gateway_percent'] }}% + {{ $money($budget->inputs['gateway_fixed']) }}<small>per transaksi</small></dd></div>
            <div><dt>Pajak biaya gateway</dt><dd>{{ $budget->inputs['gateway_tax_percent'] }}%</dd></div>
            <div><dt>Transaksi / peserta</dt><dd>{{ $budget->inputs['payments_per_participant'] }} kali</dd></div>
            <div><dt>Pajak harga jual</dt><dd>{{ $budget->inputs['sales_tax_percent'] }}%</dd></div>
        </dl>
        <div class="rab-gateway-note">
            <h4>Dasar estimasi gateway</h4>
            <p>{{ $budget->inputs['gateway_notes'] ?: 'Belum ada catatan.' }}</p>
        </div>
    </section>
    <section class="rab-minimum rab-minimum--{{ $tone }}" aria-labelledby="rab-minimum-title">
        <header class="rab-minimum-heading">
            <h3 id="rab-minimum-title">Simulasi minimum</h3>
            <span class="rab-result-badge">{{ $label }}</span>
        </header>
        <p class="rab-minimum-count"><strong>{{ $minimum['participants'] }}</strong> peserta</p>
        <p class="rab-minimum-price">Harga usulan tetap <strong>{{ $money($totals['price']) }}</strong> / peserta</p>
        <div class="rab-minimum-result">
            <span>Estimasi {{ $result < 0 ? 'kerugian' : ($result > 0 ? 'keuntungan' : 'hasil') }} setelah biaya & pajak</span>
            <strong>{{ $result < 0 ? '− ' : '' }}{{ $money(abs($result)) }}</strong>
        </div>
        <p class="rab-minimum-explanation">{{ $result < 0 ? 'Pendapatan pada jumlah peserta ini belum menutup biaya.' : ($result > 0 ? 'Pendapatan pada jumlah peserta ini masih melebihi biaya.' : 'Pendapatan pada jumlah peserta ini sama dengan biaya.') }}</p>
        <p class="rab-minimum-footnote">Simulasi perencanaan; minimum peserta bukan syarat pendaftaran atau pelaksanaan.</p>
    </section>
</div>
