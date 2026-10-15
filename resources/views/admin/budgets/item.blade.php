<tr data-budget-item data-index="{{ $index }}">
    <td class="rab-row-number" data-row-number></td>
    <td><textarea aria-label="Uraian biaya" name="items[{{ $index }}][description]" rows="2" required maxlength="255" placeholder="Nama komponen biaya">{{ $item['description'] ?? '' }}</textarea><small class="rab-row-caption" data-item-caption></small></td>
    <td><input aria-label="Qty / pengali" type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}" min="0" max="10000" step="0.001" required><small data-quantity-hint></small></td>
    <td><input aria-label="Frekuensi / pengali" type="number" name="items[{{ $index }}][frequency]" value="{{ $item['frequency'] }}" min="0" max="1000" step="0.001" required><small data-frequency-hint></small></td>
    <td><input aria-label="Harga satuan (Rp)" type="number" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] }}" min="0" max="100000000" required><small data-unit-price-hint></small></td>
    <td class="rab-money"><output data-line-total>—</output></td>
    <td><button type="button" class="rab-detail-toggle" data-item-details aria-expanded="false" aria-controls="rab-detail-{{ $index }}">Detail</button><button type="button" class="rab-remove" data-remove-item aria-label="Hapus komponen">Hapus</button></td>
</tr>
<tr class="rab-detail-row" data-item-detail="{{ $index }}" id="rab-detail-{{ $index }}" hidden><td colspan="7">
    <div class="rab-item-grid">
        <label>Kategori<input name="items[{{ $index }}][category]" value="{{ $item['category'] ?? '' }}" required maxlength="100" list="rab-category-options"></label>
        <label>Jenis komponen<select name="items[{{ $index }}][stock_kind]"><option value="none">Biaya / jasa / alat pakai ulang</option><option value="material" @selected(($item['stock_kind'] ?? '') === 'material')>Material habis pakai (cek stok)</option></select></label>
        <label>Barang gudang<select name="items[{{ $index }}][storage_item_id]"><option value="">Pilih barang</option>@foreach($storageItems as $storageItem)<option value="{{ $storageItem->id }}" @selected(($item['storage_item_id'] ?? null) == $storageItem->id)>{{ $storageItem->name }} · {{ $storageItem->unit?->symbol }}</option>@endforeach</select></label>
        <label>Lokasi gudang<select name="items[{{ $index }}][storage_location_id]"><option value="">Pilih gudang</option>@foreach($storageLocations as $storageLocation)<option value="{{ $storageLocation->id }}" @selected(($item['storage_location_id'] ?? null) == $storageLocation->id)>{{ $storageLocation->name }}</option>@endforeach</select></label>
        <label>Konversi ke satuan gudang<input type="number" name="items[{{ $index }}][stock_unit_factor]" value="{{ $item['stock_unit_factor'] ?? 1 }}" min="0.001" max="100000" step="0.001"><small>1 satuan pemakaian RAB = angka ini × satuan gudang.</small></label>
        <label>Satuan<input name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? 'paket' }}" required maxlength="50"></label>
        <label>Cakupan<select name="items[{{ $index }}][scope]"><option value="batch" @selected(($item['scope'] ?? '') === 'batch')>Per batch</option><option value="shared" @selected(($item['scope'] ?? '') === 'shared')>Bersama (sekali)</option></select></label>
        <label>Batch khusus<select name="items[{{ $index }}][batch_id]"><option value="">Semua batch terpilih</option>@foreach($choiceBatches as $batch)<option value="{{ $batch->id }}" @selected(($item['batch_id'] ?? null) == $batch->id)>{{ $batch->code }}</option>@endforeach</select></label>
        <label>Dasar Qty<select name="items[{{ $index }}][quantity_basis]">@foreach(\App\Services\Training\BudgetCalculator::QUANTITIES as $value=>$label)<option value="{{ $value }}" @selected(($item['quantity_basis'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Dasar frekuensi<select name="items[{{ $index }}][frequency_basis]">@foreach(\App\Services\Training\BudgetCalculator::FREQUENCIES as $value=>$label)<option value="{{ $value }}" @selected(($item['frequency_basis'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="rab-wide">Dasar / catatan<input name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? '' }}" maxlength="1000"></label>
    </div>
    <p class="rab-detail-help">Qty dan frekuensi pada tabel adalah pengali dasar yang dipilih. Untuk bahan praktik, Qty dapat diisi kebutuhan kg per peserta per hari.</p>
    <ul class="rab-breakdown" data-line-breakdown></ul>
</td></tr>
