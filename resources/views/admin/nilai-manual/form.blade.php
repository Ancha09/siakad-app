@extends('layouts.admin')

@php
    $editing = isset($khs);
    $recordKrs = $editing ? $khs->krs : null;
    $selectedStudentId = old('mahasiswa_id', $recordKrs?->mahasiswa_id);
    $selectedStudentModel = $mahasiswas->first(fn ($m) => (string) $m->id === (string) $selectedStudentId);
    $selectedStudentLabel = $selectedStudentModel
        ? $selectedStudentModel->nim.' — '.$selectedStudentModel->nama.($selectedStudentModel->is_active ? '' : ' (nonaktif)')
        : '';
    $selectedCourse = $recordKrs?->mata_kuliah_id ?? $recordKrs?->jadwal?->mata_kuliah_id;
    $selectedCourseId = old('mata_kuliah_id', $selectedCourse);
    $selectedCourseModel = $mataKuliahs->first(fn ($mataKuliah) => (string) $mataKuliah->id === (string) $selectedCourseId);
    $selectedCourseLabel = $selectedCourseModel
        ? $selectedCourseModel->kode_mk.' - '.$selectedCourseModel->nama_mk.' ('.$selectedCourseModel->sks.' SKS)'
        : '';
@endphp

@section('title', $editing ? 'Edit Nilai Lama' : 'Input Nilai Lama')

@section('content')
<div class="page-card">
    <div class="page-card-head"><h2>{{ $editing ? 'Edit' : 'Input' }} Nilai Lama / Manual</h2></div>
    <div class="page-card-body">
        @if($errors->any())
            <div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin-bottom:20px"><strong>Periksa input:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ $editing ? route('admin.nilai-manual.update', $khs) : route('admin.nilai-manual.store') }}">
            @csrf @if($editing) @method('PUT') @endif
            <input type="hidden" name="return_url" value="{{ $returnUrl }}">
            <div class="krs-form-grid">
                <div class="form-group">
                    <label for="mahasiswa-search">Mahasiswa *</label>
                    <div class="manual-student-picker" data-student-picker>
                        <div class="manual-student-input-wrap">
                            <span class="manual-student-search-icon" aria-hidden="true"><x-layout-icon name="search" /></span>
                            <input
                                id="mahasiswa-search"
                                type="text"
                                class="form-control manual-student-search"
                                value="{{ $selectedStudentLabel }}"
                                placeholder="Ketik NIM atau nama mahasiswa"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-controls="mahasiswa-results"
                                aria-expanded="false"
                                required
                                data-student-search
                            >
                            <input type="hidden" name="mahasiswa_id" value="{{ $selectedStudentId }}" data-student-value>
                        </div>

                        <div id="mahasiswa-results" class="manual-student-results" role="listbox" hidden data-student-results>
                            @foreach($mahasiswas as $m)
                                @php($mLabel = $m->nim.' — '.$m->nama.($m->is_active ? '' : ' (nonaktif)'))
                                <button
                                    id="mahasiswa-option-{{ $m->id }}"
                                    type="button"
                                    class="manual-student-option{{ (string) $selectedStudentId === (string) $m->id ? ' is-selected' : '' }}"
                                    role="option"
                                    aria-selected="{{ (string) $selectedStudentId === (string) $m->id ? 'true' : 'false' }}"
                                    data-student-option
                                    data-student-id="{{ $m->id }}"
                                    data-student-label="{{ $mLabel }}"
                                    data-student-search-text="{{ $m->nim }} {{ $m->nama }}"
                                    data-student-angkatan="{{ $m->angkatan }}"
                                    data-student-prodi-id="{{ $m->prodi_id }}"
                                >
                                    {{ $mLabel }}
                                </button>
                            @endforeach
                            <div class="manual-student-empty" role="status" hidden data-student-empty>Mahasiswa tidak ditemukan</div>
                        </div>
                    </div>
                    <small>Cari menggunakan NIM atau nama mahasiswa, lalu pilih salah satu hasil.</small>
                </div>
                <div class="form-group"><label>Angkatan</label><input type="number" name="angkatan" class="form-control" min="1900" value="{{ old('angkatan', $recordKrs?->angkatan ?? $recordKrs?->mahasiswa?->angkatan) }}"></div>
                <div class="form-group"><label>Semester Mahasiswa</label><input type="number" name="semester" class="form-control" min="1" max="14" value="{{ old('semester', $recordKrs?->semester) }}"></div>
                <div class="form-group"><label>Tahun Ajaran *</label><input type="text" name="tahun_akademik" class="form-control" placeholder="2020/2021" value="{{ old('tahun_akademik', $khs->tahun_akademik ?? '') }}" required></div>
                <div class="form-group"><label>Semester Akademik *</label><select name="semester_akademik" class="form-control" required><option value="">Pilih semester</option>@foreach(['Ganjil','Genap'] as $semester)<option value="{{ $semester }}" @selected(old('semester_akademik', $khs->semester_akademik ?? '') === $semester)>{{ $semester }}</option>@endforeach</select></div>
                <div class="form-group"><label>Program Studi</label><select name="prodi_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(old('prodi_id', $recordKrs?->prodi_id ?? $recordKrs?->mahasiswa?->prodi_id) == $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach</select></div>
                <div class="form-group">
                    <label for="mata-kuliah-search">Mata Kuliah *</label>
                    <div class="manual-course-picker" data-course-picker>
                        <div class="manual-course-input-wrap">
                            <span class="manual-course-search-icon" aria-hidden="true"><x-layout-icon name="search" /></span>
                            <input
                                id="mata-kuliah-search"
                                type="text"
                                class="form-control manual-course-search"
                                value="{{ $selectedCourseLabel }}"
                                placeholder="Ketik kode atau nama mata kuliah"
                                autocomplete="off"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-controls="mata-kuliah-results"
                                aria-expanded="false"
                                required
                                data-course-search
                            >
                            <input type="hidden" name="mata_kuliah_id" value="{{ $selectedCourseId }}" data-course-value>
                        </div>

                        <div id="mata-kuliah-results" class="manual-course-results" role="listbox" hidden data-course-results>
                            @foreach($mataKuliahs as $mk)
                                @php($courseLabel = $mk->kode_mk.' - '.$mk->nama_mk.' ('.$mk->sks.' SKS)')
                                <button
                                    id="mata-kuliah-option-{{ $mk->id }}"
                                    type="button"
                                    class="manual-course-option{{ (string) $selectedCourseId === (string) $mk->id ? ' is-selected' : '' }}"
                                    role="option"
                                    aria-selected="{{ (string) $selectedCourseId === (string) $mk->id ? 'true' : 'false' }}"
                                    data-course-option
                                    data-course-id="{{ $mk->id }}"
                                    data-course-label="{{ $courseLabel }}"
                                    data-course-search-text="{{ $mk->kode_mk }} {{ $mk->nama_mk }}"
                                >
                                    {{ $courseLabel }}
                                </button>
                            @endforeach
                            <div class="manual-course-empty" role="status" hidden data-course-empty>Mata kuliah tidak ditemukan</div>
                        </div>
                    </div>
                    <small>Cari menggunakan kode atau nama mata kuliah, lalu pilih salah satu hasil.</small>
                </div>
                <div class="form-group"><label>Dosen Pengampu</label><select name="dosen_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected(old('dosen_id', ($editing ? $khs->dosen_efektif?->id : null)) == $dosen->id)>{{ $dosen->nama }}</option>@endforeach</select><small>Pilih dosen yang benar untuk data historis; boleh berbeda dari dosen pada jadwal.</small></div>
                <div class="form-group"><label>Jadwal</label><select name="jadwal_id" class="form-control"><option value="">Tidak ada jadwal</option>@foreach($jadwals as $jadwal)<option value="{{ $jadwal->id }}" @selected(old('jadwal_id', $recordKrs?->jadwal_id) == $jadwal->id)>{{ $jadwal->mataKuliah?->kode_mk ?? '-' }} · {{ $jadwal->tahun_akademik ?? '-' }} · {{ $jadwal->dosen?->nama ?? 'tanpa dosen' }}</option>@endforeach</select><small>Jika dipilih, jadwal harus sesuai mata kuliah, periode, dan kelas. Jadwal asli tidak diubah.</small></div>
                <div class="form-group"><label>Kelas</label><select name="kelas_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($kelases as $kelas)<option value="{{ $kelas->id }}" @selected(old('kelas_id', $recordKrs?->kelas_id ?? $recordKrs?->jadwal?->kelas_id) == $kelas->id)>{{ $kelas->nama_kelas }}</option>@endforeach</select></div>
                <div class="form-group"><label>Nilai Angka *</label><input id="nilai_angka" type="number" step="0.01" min="0" max="100" name="nilai_angka" class="form-control" value="{{ old('nilai_angka', $khs->nilai_angka ?? '') }}" required></div>
                <div class="form-group"><label>Nilai Huruf</label><select id="nilai_huruf" name="nilai_huruf" class="form-control"><option value="">Hitung otomatis</option>@foreach($gradeLetters as $letter)<option value="{{ $letter }}" @selected(old('nilai_huruf', $khs->nilai_huruf ?? '') === $letter)>{{ $letter }}</option>@endforeach</select></div>
                <div class="form-group"><label>SKS</label><input type="number" min="1" max="30" name="sks" class="form-control" value="{{ old('sks', $khs->sks ?? '') }}" placeholder="Ikuti mata kuliah"></div>
                <div class="form-group"><label>Bobot (otomatis)</label><input id="bobot" type="number" step="0.01" min="0" max="4" name="bobot" class="form-control" value="{{ old('bobot', $khs->bobot ?? '') }}" placeholder="Mengikuti nilai huruf" readonly><small>Bobot selalu mengikuti pemetaan nilai huruf sistem.</small></div>
            </div>
            <p style="color:#64748b">* Wajib. Angkatan, semester mahasiswa, prodi, dosen, jadwal, kelas, nilai huruf, SKS, dan bobot boleh kosong.</p>
            <p style="color:#64748b">Dosen pengampu tersimpan khusus untuk entri nilai ini. Identitas akademik pada KRS manual berlaku bersama untuk mata kuliah/periode yang sama. Untuk mengganti nama pada master dosen, gunakan menu Data Dosen.</p>
            <button class="btn-primary">Simpan Nilai</button> <a href="{{ $returnUrl }}" class="btn-outline">Batal</a>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nilaiAngka = document.getElementById('nilai_angka');
        const nilaiHuruf = document.getElementById('nilai_huruf');
        const bobot = document.getElementById('bobot');
        const bobotHuruf = { A: 4, 'A-': 3.75, 'B+': 3.5, B: 3, 'B-': 2.75, 'C+': 2.5, C: 2, D: 1, E: 0 };
        const batasNilai = [[85, 'A'], [80, 'A-'], [75, 'B+'], [70, 'B'], [65, 'B-'], [60, 'C+'], [55, 'C'], [40, 'D'], [0, 'E']];

        function setupCombobox({
            picker,
            searchSelector,
            valueSelector,
            resultsSelector,
            emptySelector,
            optionSelector,
            idDataKey,
            labelDataKey,
            searchTextDataKey,
            validationMessage,
            onSelect,
        }) {
            if (! picker) return;
            const search = picker.querySelector(searchSelector);
            const value = picker.querySelector(valueSelector);
            const results = picker.querySelector(resultsSelector);
            const empty = picker.querySelector(emptySelector);
            const options = Array.from(picker.querySelectorAll(optionSelector));
            const form = picker.closest('form');
            let selectedLabel = search.value;
            let activeIndex = -1;

            const visibleOptions = () => options.filter((option) => ! option.hidden);

            const setOpen = (open) => {
                results.hidden = ! open;
                search.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (! open) {
                    activeIndex = -1;
                    search.removeAttribute('aria-activedescendant');
                    options.forEach((option) => option.classList.remove('is-active'));
                }
            };

            const setActive = (nextIndex) => {
                const visible = visibleOptions();
                options.forEach((option) => option.classList.remove('is-active'));
                if (visible.length === 0) {
                    activeIndex = -1;
                    search.removeAttribute('aria-activedescendant');
                    return;
                }

                activeIndex = (nextIndex + visible.length) % visible.length;
                const activeOption = visible[activeIndex];
                activeOption.classList.add('is-active');
                search.setAttribute('aria-activedescendant', activeOption.id);
                activeOption.scrollIntoView({ block: 'nearest' });
            };

            const filterOptions = () => {
                const query = search.value === selectedLabel
                    ? ''
                    : search.value.trim().toLocaleLowerCase('id-ID');
                let matchCount = 0;

                options.forEach((option) => {
                    const text = option.dataset[searchTextDataKey] || '';
                    const matches = text.toLocaleLowerCase('id-ID').includes(query);
                    option.hidden = ! matches;
                    if (matches) matchCount += 1;
                });

                empty.hidden = matchCount !== 0;
                activeIndex = -1;
                options.forEach((option) => option.classList.remove('is-active'));
                search.removeAttribute('aria-activedescendant');
                setOpen(true);
            };

            const chooseOption = (option) => {
                value.value = option.dataset[idDataKey];
                search.value = option.dataset[labelDataKey];
                selectedLabel = option.dataset[labelDataKey];
                search.setCustomValidity('');
                options.forEach((item) => {
                    const selected = item === option;
                    item.classList.toggle('is-selected', selected);
                    item.setAttribute('aria-selected', selected ? 'true' : 'false');
                });
                setOpen(false);
                search.focus();
                if (typeof onSelect === 'function') {
                    onSelect(option);
                }
            };

            search.addEventListener('focus', filterOptions);
            search.addEventListener('input', () => {
                if (search.value !== selectedLabel) {
                    value.value = '';
                    options.forEach((option) => {
                        option.classList.remove('is-selected');
                        option.setAttribute('aria-selected', 'false');
                    });
                }
                search.setCustomValidity('');
                filterOptions();
            });

            search.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (results.hidden) filterOptions();
                    const direction = event.key === 'ArrowDown' ? 1 : -1;
                    setActive(activeIndex < 0 ? (direction > 0 ? 0 : visibleOptions().length - 1) : activeIndex + direction);
                } else if (event.key === 'Enter' && ! results.hidden && activeIndex >= 0) {
                    event.preventDefault();
                    chooseOption(visibleOptions()[activeIndex]);
                } else if (event.key === 'Escape' || event.key === 'Tab') {
                    setOpen(false);
                }
            });

            options.forEach((option) => option.addEventListener('click', () => chooseOption(option)));

            document.addEventListener('mousedown', (event) => {
                if (! picker.contains(event.target)) setOpen(false);
            });

            form?.addEventListener('submit', (event) => {
                if (! value.value) {
                    event.preventDefault();
                    search.setCustomValidity(validationMessage);
                    search.reportValidity();
                    filterOptions();
                }
            });
        }

        // Setup Mahasiswa Combobox
        setupCombobox({
            picker: document.querySelector('[data-student-picker]'),
            searchSelector: '[data-student-search]',
            valueSelector: '[data-student-value]',
            resultsSelector: '[data-student-results]',
            emptySelector: '[data-student-empty]',
            optionSelector: '[data-student-option]',
            idDataKey: 'studentId',
            labelDataKey: 'studentLabel',
            searchTextDataKey: 'studentSearchText',
            validationMessage: 'Pilih mahasiswa dari daftar hasil pencarian.',
            onSelect: (option) => {
                const angkatanInput = document.querySelector('input[name="angkatan"]');
                if (angkatanInput && ! angkatanInput.value && option.dataset.studentAngkatan) {
                    angkatanInput.value = option.dataset.studentAngkatan;
                }
                const prodiSelect = document.querySelector('select[name="prodi_id"]');
                if (prodiSelect && ! prodiSelect.value && option.dataset.studentProdiId) {
                    prodiSelect.value = option.dataset.studentProdiId;
                }
            },
        });

        // Setup Mata Kuliah Combobox
        setupCombobox({
            picker: document.querySelector('[data-course-picker]'),
            searchSelector: '[data-course-search]',
            valueSelector: '[data-course-value]',
            resultsSelector: '[data-course-results]',
            emptySelector: '[data-course-empty]',
            optionSelector: '[data-course-option]',
            idDataKey: 'courseId',
            labelDataKey: 'courseLabel',
            searchTextDataKey: 'courseSearchText',
            validationMessage: 'Pilih mata kuliah dari daftar hasil pencarian.',
        });

        nilaiAngka?.addEventListener('input', function () {
            const angka = Number(nilaiAngka.value);
            if (nilaiAngka.value === '' || Number.isNaN(angka)) {
                nilaiHuruf.value = '';
                bobot.value = '';
                return;
            }

            const hasil = batasNilai.find(([minimum]) => angka >= minimum);
            nilaiHuruf.value = hasil?.[1] ?? 'E';
            bobot.value = bobotHuruf[nilaiHuruf.value].toFixed(2);
        });

        nilaiHuruf?.addEventListener('change', function () {
            bobot.value = nilaiHuruf.value === '' ? '' : bobotHuruf[nilaiHuruf.value].toFixed(2);
        });
    });
</script>
@endpush
