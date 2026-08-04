@props([
    'id',
    'title',
    'description' => null,
    'size' => 'medium',
])

<dialog id="{{ $id }}" class="ui-dialog ui-dialog--{{ $size }}" aria-labelledby="{{ $id }}-title">
    <div class="ui-dialog__panel">
        <header class="ui-dialog__header">
            <div>
                <span class="eyebrow">{{ config('branding.name') }}</span>
                <h2 id="{{ $id }}-title">{{ $title }}</h2>
                @if ($description)
                    <p>{{ $description }}</p>
                @endif
            </div>
            <button class="ui-dialog__close" type="button" data-modal-close aria-label="Tutup modal">&times;</button>
        </header>
        <div class="ui-dialog__body">{{ $slot }}</div>
        @isset($footer)
            <footer class="ui-dialog__footer">{{ $footer }}</footer>
        @endisset
    </div>
</dialog>
