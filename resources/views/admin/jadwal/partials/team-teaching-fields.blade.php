@php
    $selectedPendamping = old(
        'dosen_pendamping_ids',
        isset($jadwal) && $jadwal->relationLoaded('dosens')
            ? $jadwal->dosens->where('pivot.peran', 'pendamping')->pluck('id')->all()
            : []
    );
    if (!is_array($selectedPendamping)) {
        $selectedPendamping = [];
    }
@endphp

<div class="form-group" style="grid-column: 1 / -1; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:10px;">
        <div>
            <label style="font-weight:600;font-size:13.5px;color:#1e293b;margin:0;">
                Dosen Pendamping / Team Teaching (Opsional)
            </label>
            <small style="color:#64748b;display:block;margin-top:2px;">
                Tambahkan dosen kedua atau dosen tim teaching jika mata kuliah ini diampu oleh lebih dari 1 dosen.
            </small>
        </div>
        <button type="button" id="btnAddDosenPendamping" class="btn-outline" style="font-size:12px;padding:6px 12px;background:#ffffff;cursor:pointer;">
            <x-layout-icon name="plus" />
            <span>Tambah Dosen Pendamping</span>
        </button>
    </div>

    <div id="dosenPendampingContainer" style="display:flex;flex-direction:column;gap:10px;">
        @foreach($selectedPendamping as $index => $selectedId)
            <div class="dosen-pendamping-row" style="display:flex;gap:10px;align-items:center;">
                <select name="dosen_pendamping_ids[]" class="form-control select-pendamping" style="flex:1;">
                    <option value="">-- Pilih Dosen Pendamping --</option>
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id }}" {{ (string)$selectedId === (string)$dosen->id ? 'selected' : '' }}>
                            {{ $dosen->nama }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn-outline" style="color:#dc2626;border-color:#fca5a5;padding:8px 12px;background:#ffffff;cursor:pointer;" onclick="removeDosenPendampingRow(this)" title="Hapus Dosen Pendamping">
                    <x-layout-icon name="trash" />
                    <span>Hapus</span>
                </button>
            </div>
        @endforeach
    </div>

    <div id="emptyPendampingNotice" style="{{ count($selectedPendamping) > 0 ? 'display:none;' : '' }}padding:12px;text-align:center;color:#94a3b8;font-size:12.5px;background:#ffffff;border:1px solid #e2e8f0;border-radius:6px;margin-top:4px;">
        Belum ada dosen pendamping ditambahkan (opsional). Klik tombol <strong>"Tambah Dosen Pendamping"</strong> di atas jika mata kuliah ini diampu oleh lebih dari 1 dosen.
    </div>
</div>

<template id="dosenPendampingTemplate">
    <div class="dosen-pendamping-row" style="display:flex;gap:10px;align-items:center;">
        <select name="dosen_pendamping_ids[]" class="form-control select-pendamping" style="flex:1;">
            <option value="">-- Pilih Dosen Pendamping --</option>
            @foreach($dosens as $dosen)
                <option value="{{ $dosen->id }}">{{ $dosen->nama }}</option>
            @endforeach
        </select>
        <button type="button" class="btn-outline" style="color:#dc2626;border-color:#fca5a5;padding:8px 12px;background:#ffffff;cursor:pointer;" onclick="removeDosenPendampingRow(this)" title="Hapus Dosen Pendamping">
            <x-layout-icon name="trash" />
            <span>Hapus</span>
        </button>
    </div>
</template>

@push('scripts')
<script>
function removeDosenPendampingRow(btn) {
    const row = btn.closest('.dosen-pendamping-row');
    if (row) {
        row.remove();
    }
    checkEmptyPendampingNotice();
}

function checkEmptyPendampingNotice() {
    const container = document.getElementById('dosenPendampingContainer');
    const notice = document.getElementById('emptyPendampingNotice');
    if (container && notice) {
        const rows = container.querySelectorAll('.dosen-pendamping-row');
        notice.style.display = rows.length === 0 ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const btnAdd = document.getElementById('btnAddDosenPendamping');
    const container = document.getElementById('dosenPendampingContainer');
    const template = document.getElementById('dosenPendampingTemplate');

    if (btnAdd && container && template) {
        btnAdd.addEventListener('click', function () {
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
            checkEmptyPendampingNotice();
        });
    }

    checkEmptyPendampingNotice();
});
</script>
@endpush
