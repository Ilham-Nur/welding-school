@props([
    'label',
    'name',
    'accept' => '.pdf,.jpg,.jpeg,.png',
    'hint' => 'PDF, JPG, atau PNG. Maksimal 5 MB.',
    'maxSizeMb' => 5,
    'required' => false,
])

@php
    $fieldId = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<div class="ui-field ui-field--file">
    <span class="ui-field__label" id="{{ $fieldId }}-label">
        {{ $label }}
        @if ($required)<em>Wajib</em>@endif
    </span>
    <label @class(['ui-file-drop', 'is-invalid' => $hasError]) data-file-drop data-max-size-mb="{{ $maxSizeMb }}">
        <input
            id="{{ $fieldId }}"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            aria-labelledby="{{ $fieldId }}-label"
            aria-describedby="{{ $fieldId }}-hint{{ $hasError ? ' '.$fieldId.'-error' : '' }}"
            @if ($hasError) aria-invalid="true" @endif
            @required($required)
            {{ $attributes->except(['id', 'aria-describedby', 'aria-labelledby']) }}
        >
        <span class="ui-file-drop__icon" aria-hidden="true"><x-ui.icon name="upload" size="22" /></span>
        <strong>Pilih file atau tarik ke area ini</strong>
        <small id="{{ $fieldId }}-hint" data-file-label>{{ $hint }}</small>
    </label>
    @error($name)
        <small id="{{ $fieldId }}-error" class="ui-field__error">{{ $message }}</small>
    @enderror
</div>
