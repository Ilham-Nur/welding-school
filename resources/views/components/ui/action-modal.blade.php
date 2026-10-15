@props([
    'id',
    'title',
    'action',
    'method' => 'PATCH',
    'confirmLabel' => 'Konfirmasi',
    'reasonLabel' => null,
    'placeholder' => 'Jelaskan alasan dan tindak lanjut yang diperlukan.',
    'danger' => true,
])

<x-ui.modal :id="$id" :title="$title">
    <form id="{{ $id }}-form" method="POST" action="{{ $action }}" class="ops-stack">
        @csrf
        @method($method)
        <input type="hidden" name="_action_modal" value="{{ $id }}">
        <div>{{ $slot }}</div>
        @if ($reasonLabel)
            <label class="admin-field">
                <span>{{ $reasonLabel }}</span>
                <textarea name="reason" rows="4" maxlength="1000" required autofocus placeholder="{{ $placeholder }}" aria-describedby="{{ $id }}-hint">{{ old('_action_modal') === $id ? old('reason') : '' }}</textarea>
                <small id="{{ $id }}-hint">Wajib diisi, maksimal 1.000 karakter. Alasan akan dicatat dalam riwayat operasional.</small>
            </label>
        @endif
    </form>
    <x-slot:footer>
        <button type="button" class="button button--outline admin-button" data-modal-close>Kembali</button>
        <button type="submit" form="{{ $id }}-form" @class(['button', 'admin-button', 'button--outline ops-action--danger' => $danger, 'button--primary' => !$danger])>{{ $confirmLabel }}</button>
    </x-slot:footer>
</x-ui.modal>
