@php
    $currentAccessMode = old(
        'access_mode',
        isset($periodeKrs) ? ($periodeKrs->access_mode ?? 'selected') : 'closed'
    );
    $checkedStudentIds = collect(old('mahasiswa_ids', $selectedAccessIds ?? []))->map(fn ($id) => (int) $id);
@endphp

<section style="margin-top:28px;padding:20px;border:1px solid #dbe3ee;border-radius:12px;background:#f8fafc;">
    <h3 style="margin:0 0 6px;">Pengaturan Akses Mahasiswa</h3>
    <p style="margin:0 0 18px;color:#64748b;">Tentukan mahasiswa yang boleh mengisi KRS selama tanggal periode aktif.</p>

    <div class="form-group" style="max-width:620px;">
        <label for="access_mode">Mode Akses</label>
        <select id="access_mode" name="access_mode" class="form-control" required>
            <option value="closed" @selected($currentAccessMode === 'closed')>Ditutup untuk semua mahasiswa</option>
            <option value="all" @selected($currentAccessMode === 'all')>Dibuka untuk semua mahasiswa aktif</option>
            <option value="selected" @selected($currentAccessMode === 'selected')>Dibuka hanya untuk mahasiswa tertentu</option>
            <option value="all_except" @selected($currentAccessMode === 'all_except')>Dibuka untuk semua kecuali mahasiswa tertentu</option>
        </select>
        <small id="access-mode-help" style="color:#64748b;"></small>
    </div>

    <div id="student-access-picker" style="margin-top:18px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:14px;">
            <div class="form-group">
                <label for="student-access-search">Cari nama/NIM</label>
                <input id="student-access-search" type="search" class="form-control" placeholder="Ketik nama atau NIM">
            </div>
            <div class="form-group">
                <label for="student-access-prodi">Program Studi</label>
                <select id="student-access-prodi" class="form-control">
                    <option value="">Semua prodi</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}">{{ $prodi->nama_prodi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="student-access-kelas">Kelas</label>
                <select id="student-access-kelas" class="form-control">
                    <option value="">Semua kelas</option>
                    @foreach($kelases as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="student-access-angkatan">Angkatan</label>
                <select id="student-access-angkatan" class="form-control">
                    <option value="">Semua angkatan</option>
                    @foreach($angkatans as $angkatan)
                        <option value="{{ $angkatan }}">{{ $angkatan }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
            <button id="select-filtered-students" type="button" class="btn-outline">Pilih Semua Hasil Filter</button>
            <button id="clear-student-selection" type="button" class="btn-outline">Kosongkan Pilihan</button>
            <strong id="student-selection-summary" style="margin-left:auto;"></strong>
        </div>

        <div id="student-access-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:8px;max-height:420px;overflow:auto;padding:10px;border:1px solid #dbe3ee;border-radius:8px;background:#fff;">
            @forelse($mahasiswas as $mahasiswa)
                <label
                    class="student-access-row"
                    data-search="{{ Illuminate\Support\Str::lower($mahasiswa->nim.' '.$mahasiswa->nama) }}"
                    data-prodi="{{ $mahasiswa->prodi_id }}"
                    data-kelas="{{ $mahasiswa->kelas_id }}"
                    data-angkatan="{{ $mahasiswa->angkatan ?? $mahasiswa->kelas?->angkatan }}"
                    style="display:flex;gap:10px;align-items:flex-start;padding:10px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;"
                >
                    <input class="student-access-input" type="checkbox" name="mahasiswa_ids[]" value="{{ $mahasiswa->id }}" @checked($checkedStudentIds->contains($mahasiswa->id))>
                    <span>
                        <strong>{{ $mahasiswa->nim }} — {{ $mahasiswa->nama }}</strong><br>
                        <small style="color:#64748b;">{{ $mahasiswa->prodi?->nama_prodi ?? '-' }} · {{ $mahasiswa->kelas?->nama_kelas ?? '-' }} · Angkatan {{ $mahasiswa->angkatan ?? $mahasiswa->kelas?->angkatan ?? '-' }}</small>
                    </span>
                </label>
            @empty
                <p style="color:#64748b;">Belum ada mahasiswa aktif.</p>
            @endforelse
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mode = document.getElementById('access_mode');
        const picker = document.getElementById('student-access-picker');
        const help = document.getElementById('access-mode-help');
        const search = document.getElementById('student-access-search');
        const prodi = document.getElementById('student-access-prodi');
        const kelas = document.getElementById('student-access-kelas');
        const angkatan = document.getElementById('student-access-angkatan');
        const rows = [...document.querySelectorAll('.student-access-row')];
        const inputs = [...document.querySelectorAll('.student-access-input')];
        const summary = document.getElementById('student-selection-summary');
        const descriptions = {
            closed: 'Semua mahasiswa ditolak sampai mode diubah atau akses dibuka dari detail periode.',
            all: 'Semua mahasiswa aktif mendapat akses selama periode aktif.',
            selected: 'Checkbox menunjukkan mahasiswa yang diizinkan mengisi KRS.',
            all_except: 'Checkbox menunjukkan mahasiswa yang dikecualikan dari pengisian KRS.'
        };

        function updateSummary() {
            const total = inputs.filter((input) => input.checked).length;
            summary.textContent = `${total} mahasiswa dipilih`;
        }

        function updateMode() {
            const needsSelection = ['selected', 'all_except'].includes(mode.value);
            picker.style.display = needsSelection ? 'block' : 'none';
            help.textContent = descriptions[mode.value];
            updateSummary();
        }

        function filterRows() {
            const keyword = search.value.trim().toLocaleLowerCase('id');
            rows.forEach((row) => {
                const visible = (!keyword || row.dataset.search.includes(keyword))
                    && (!prodi.value || row.dataset.prodi === prodi.value)
                    && (!kelas.value || row.dataset.kelas === kelas.value)
                    && (!angkatan.value || row.dataset.angkatan === angkatan.value);
                row.style.display = visible ? 'flex' : 'none';
            });
        }

        mode.addEventListener('change', updateMode);
        [search, prodi, kelas, angkatan].forEach((field) => field.addEventListener('input', filterRows));
        inputs.forEach((input) => input.addEventListener('change', updateSummary));
        document.getElementById('select-filtered-students').addEventListener('click', function () {
            rows.filter((row) => row.style.display !== 'none').forEach((row) => {
                row.querySelector('.student-access-input').checked = true;
            });
            updateSummary();
        });
        document.getElementById('clear-student-selection').addEventListener('click', function () {
            inputs.forEach((input) => input.checked = false);
            updateSummary();
        });

        updateMode();
        filterRows();
    });
</script>
@endpush
