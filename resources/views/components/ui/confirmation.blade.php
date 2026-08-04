@props([
    'id',
    'title' => 'Konfirmasi tindakan',
    'confirmLabel' => 'Ya, lanjutkan',
    'tone' => 'danger',
])

<dialog id="{{ $id }}" class="ui-dialog ui-dialog--small ui-confirmation" aria-labelledby="{{ $id }}-title">
    <div class="ui-dialog__panel">
        <div class="ui-confirmation__icon ui-confirmation__icon--{{ $tone }}" aria-hidden="true">
            <x-ui.icon :name="$tone === 'success' ? 'check-circle' : 'alert-triangle'" size="25" />
        </div>
        <h2 id="{{ $id }}-title">{{ $title }}</h2>
        <div class="ui-confirmation__copy">{{ $slot }}</div>
        <div class="ui-confirmation__actions">
            <button class="button button--outline" type="button" data-modal-close>Batal</button>
            <button
                class="button button--primary {{ $tone === 'danger' ? 'ui-button--danger' : '' }}"
                type="button"
                data-confirm-action
            >{{ $confirmLabel }}</button>
        </div>
    </div>
</dialog>
