<div class="ui-field admin-field storage-item-field">
    <span class="ui-field__label">Consumable <em>Wajib</em></span>
    <div class="storage-item-picker" data-storage-item-picker data-storage-picker-style="select2">
        <select name="{{ $name }}" required data-storage-item-select aria-label="Pilih consumable">
            <option value="">Pilih barang</option>
            @foreach($items as $item)
                <option
                    value="{{ $item->id }}"
                    data-code="{{ $item->code }}"
                    data-name="{{ $item->name }}"
                    data-meta="{{ collect([$item->category, $item->unit, $item->notes ? \Illuminate\Support\Str::limit($item->notes, 70) : null])->filter()->join(' · ') }}"
                    data-search="{{ collect([$item->code, $item->name, $item->category, $item->unit, $item->notes])->filter()->join(' ') }}"
                    @selected((string) $selected === (string) $item->id)
                >{{ $item->code }} · {{ $item->name }} · {{ $item->category }} ({{ $item->unit }})</option>
            @endforeach
        </select>
    </div>
</div>
