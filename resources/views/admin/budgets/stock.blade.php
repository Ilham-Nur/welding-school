<section class="admin-panel"><header class="admin-panel__header"><div><h2>Kebutuhan material & stok gudang</h2><p>Stok tercatat saat diperiksa, belum dikurangi reservasi RAB lain. Pemeriksaan ini tidak mencadangkan atau mengeluarkan barang.</p></div></header>
<div class="admin-panel__body">
<p>Hubungkan komponen material ke barang dan lokasi gudang melalui Detail. Konversi satuan menentukan kebutuhan dalam satuan gudang. Biaya RAB tetap menghitung seluruh pemakaian material, termasuk yang sudah tersedia.</p>
@if(isset($stock))
    @if($stock['unmapped'])<p>Belum dapat diperiksa: {{ implode(', ', $stock['unmapped']) }}.</p>@endif
    @if(!$stock['rows'])<p>Belum ada material yang terhubung ke gudang. Ini belum menyatakan stok mencukupi.</p>@endif
@else<p data-stock-status>Lengkapi RAB untuk memeriksa stok.</p>@endif
</div>
<x-ui.table class="admin-table-wrap rab-stock-table">
    <thead>
        <tr>
            <th scope="col">Material / gudang</th>
            <th scope="col">Satuan</th>
            <th scope="col" class="rab-stock-number">Kebutuhan</th>
            <th scope="col" class="rab-stock-number">Stok tercatat</th>
            <th scope="col" class="rab-stock-number">Kekurangan</th>
        </tr>
    </thead>
    <tbody @if(!isset($stock)) data-stock-rows @endif>
        @forelse (($stock['rows'] ?? []) as $row)
            <tr>
                <td><strong>{{ $row['item'] }}</strong><small>{{ $row['location'] }}</small></td>
                <td>{{ $row['unit'] }}</td>
                <td class="rab-stock-number">{{ number_format($row['required'],3,',','.') }}</td>
                <td class="rab-stock-number">{{ number_format($row['available'],3,',','.') }}</td>
                <td class="rab-stock-number">{{ number_format($row['shortage'],3,',','.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><small>Belum ada baris material untuk ditampilkan.</small></td></tr>
        @endforelse
    </tbody>
</x-ui.table>
<div class="admin-panel__body">
@if(isset($stock))<p>Rencana pengadaan: {{ $budget->inputs['procurement_notes'] ?? 'Belum diisi' }}</p>
@else<label class="ui-field admin-field"><span>Rencana pengadaan untuk kekurangan stok</span><textarea name="procurement_notes" rows="3" maxlength="2000" placeholder="Material, jumlah yang dibeli, estimasi biaya, dan waktu pengadaan">{{ old('procurement_notes', $inputs['procurement_notes'] ?? '') }}</textarea></label>@endif
<p>Jika stok kurang, rencana pengadaan wajib diisi sebelum pengajuan/persetujuan. Mesin dan alat pakai ulang diperiksa lewat kesiapan operasional serta jadwal; tabel ini untuk material habis pakai.</p>
</div></section>
