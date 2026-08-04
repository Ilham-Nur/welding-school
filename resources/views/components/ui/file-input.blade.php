@props([
    'label',
    'name',
    'accept' => '.pdf,.jpg,.jpeg,.png',
    'hint' => 'PDF, JPG, atau PNG. Maksimal 5 MB.',
    'required' => false,
])

@php
    $fieldId = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div class="ui-field ui-field--file">
    <span class="ui-field__label">
        {{ $label }}
        @if ($required)<em>Wajib</em>@endif
    </span>
    <label @class(['ui-file-drop', 'is-invalid' => $hasError]) data-file-drop>
        <input
            id="{{ $fieldId }}"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
            @required($required)
            {{ $attributes->except('id') }}
        >
        <span class="ui-file-drop__icon" aria-hidden="true"><x-ui.icon name="upload" size="22" /></span>
        <strong>Pilih file atau tarik ke area ini</strong>
        <small data-file-label>{{ $hint }}</small>
    </label>
    @error($name)
        <small id="{{ $fieldId }}-error" class="ui-field__error">{{ $message }}</small>
    @enderror
</div>
