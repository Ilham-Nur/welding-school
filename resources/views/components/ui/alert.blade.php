@props([
    'type' => 'info',
    'title',
    'dismissible' => false,
])

<div {{ $attributes->class(["ui-alert", "ui-alert--{$type}"]) }} role="{{ $type === 'danger' ? 'alert' : 'status' }}">
    <span class="ui-alert__icon" aria-hidden="true">
        @switch($type)
            @case('success') <x-ui.icon name="check-circle" size="18" /> @break
            @case('warning') <x-ui.icon name="alert-triangle" size="18" /> @break
            @case('danger') <x-ui.icon name="x-circle" size="18" /> @break
            @default <x-ui.icon name="info" size="18" />
        @endswitch
    </span>
    <div class="ui-alert__content">
        <strong>{{ $title }}</strong>
        <div>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button class="ui-alert__close" type="button" data-alert-close aria-label="Tutup notifikasi">&times;</button>
    @endif
</div>
