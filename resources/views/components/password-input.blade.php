@props([
    'name' => 'password',
    'id' => null,
    'placeholder' => '',
    'required' => false,
    'autocomplete' => 'new-password',
])

@php
    $inputId = $id ?? 'pwd_' . str_replace(['[', ']', '.'], '_', $name) . '_' . \Illuminate\Support\Str::random(6);
@endphp

<div class="password-toggle-wrapper" style="position: relative; width: 100%;">
    <input
        type="password"
        name="{{ $name }}"
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => 'form-control']) }}
        style="padding-right: 42px;"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        autocomplete="{{ $autocomplete }}"
    >
    <button
        type="button"
        class="btn-toggle-password"
        data-target="{{ $inputId }}"
        tabindex="-1"
        title="Lihat / Sembunyikan Password"
        aria-label="Lihat / Sembunyikan Password"
        style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; padding: 6px; cursor: pointer; color: #64748b; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: color 0.15s, background-color 0.15s;"
        onmouseover="this.style.color='#1e293b'; this.style.backgroundColor='#f1f5f9';"
        onmouseout="this.style.color='#64748b'; this.style.backgroundColor='transparent';"
        onclick="(function(btn){
            const input = document.getElementById(btn.getAttribute('data-target'));
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const iconEye = btn.querySelector('.icon-eye');
            const iconEyeOff = btn.querySelector('.icon-eye-off');
            if (iconEye && iconEyeOff) {
                iconEye.style.display = isPassword ? 'none' : 'inline-block';
                iconEyeOff.style.display = isPassword ? 'inline-block' : 'none';
            }
        })(this)"
    >
        <span class="icon-eye" style="display: inline-block;">
            <x-layout-icon name="eye" style="width: 18px; height: 18px;" />
        </span>
        <span class="icon-eye-off" style="display: none;">
            <x-layout-icon name="eye-off" style="width: 18px; height: 18px;" />
        </span>
    </button>
</div>
