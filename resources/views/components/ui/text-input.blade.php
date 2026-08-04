@props([
    'label',
    'name',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'hint' => null,
    'required' => false,
    'wrapperClass' => null,
])

@php
    $fieldId = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<label @class(['ui-field', $wrapperClass])>
    <span class="ui-field__label">
        {{ $label }}
        @if ($required)<em>Wajib</em>@endif
    </span>
    <input
        id="{{ $fieldId }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @class(['is-invalid' => $hasError])
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        @required($required)
        {{ $attributes->except('id') }}
    >
    @if ($hint)<small>{{ $hint }}</small>@endif
    @error($name)
        <small id="{{ $fieldId }}-error" class="ui-field__error">{{ $message }}</small>
    @enderror
</label>
