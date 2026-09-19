@php
    $sharedSchedule = (bool) old('is_lintas_prodi', $jadwal->is_lintas_prodi ?? false);
@endphp

<div class="form-group" style="grid-column:1 / -1;">
    <input type="hidden" name="is_lintas_prodi" value="0">
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
        <input
            type="checkbox"
            name="is_lintas_prodi"
            value="1"
            data-shared-schedule-toggle
            {{ $sharedSchedule ? 'checked' : '' }}
            style="width:18px;height:18px;"
        >
        <span>Jadwal Lintas Prodi / Kelas Gabungan</span>
    </label>
    <small>Aktifkan hanya jika beberapa kode mata kuliah diajar dalam satu sesi yang sama.</small>
</div>

<div class="form-group" style="grid-column:1 / -1;{{ $sharedSchedule ? '' : 'display:none;' }}" data-shared-schedule-fields>
    <label for="group-key">Kode Grup Jadwal</label>
    <input
        id="group-key"
        type="text"
        name="group_key"
        class="form-control"
        maxlength="100"
        value="{{ old('group_key', $jadwal->group_key ?? '') }}"
        placeholder="Kosongkan untuk dibuat otomatis"
    >
    <small>Gunakan kode yang sama untuk semua baris jadwal dalam satu sesi. Jika kosong, kode dibuat otomatis dari detail sesi.</small>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.querySelector('[data-shared-schedule-toggle]');
                const fields = document.querySelector('[data-shared-schedule-fields]');
                if (! toggle || ! fields) return;

                const sync = () => fields.style.display = toggle.checked ? '' : 'none';
                toggle.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
@endonce
