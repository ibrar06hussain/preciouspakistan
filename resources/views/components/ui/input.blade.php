@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'placeholder' => null,
    'hint' => null,
])

@php
    $inputId = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $errorId = $inputId.'-error';
@endphp

<div class="space-y-1.5">
    <label for="{{ $inputId }}" class="block text-sm font-semibold text-slate-900">
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
        @endif
    </label>

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($required) required @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
        {{ $attributes->except(['id'])->merge([
            'class' => 'block w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-xs transition '
                .($hasError
                    ? 'border-red-500 bg-red-50/40 focus:border-red-600 focus:ring-2 focus:ring-red-200'
                    : 'border-slate-300 bg-white focus:border-emerald-700 focus:ring-2 focus:ring-emerald-200'),
        ]) }}
    >

    @if ($hint && ! $hasError)
        <p class="text-xs text-slate-600">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $errorId }}" class="text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
    @enderror
</div>
