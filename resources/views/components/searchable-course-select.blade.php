@props([
    'courses',
    'selected' => null,
    'name' => 'mata_kuliah_id',
    'inputId' => 'mata-kuliah-search',
])

@php
    $selectedCourse = $courses->first(fn ($course) => (string) $course->id === (string) $selected);
    $selectedLabel = $selectedCourse
        ? $selectedCourse->kode_mk.' - '.$selectedCourse->nama_mk.' ('.$selectedCourse->sks.' SKS)'
        : '';
@endphp

<div class="manual-course-picker" data-course-picker>
    <div class="manual-course-input-wrap">
        <span class="manual-course-search-icon" aria-hidden="true"><x-layout-icon name="search" /></span>
        <input
            id="{{ $inputId }}"
            type="text"
            class="form-control manual-course-search"
            value="{{ $selectedLabel }}"
            placeholder="Ketik kode atau nama mata kuliah"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            required
            data-course-search
        >
        <input type="hidden" name="{{ $name }}" value="{{ $selected }}" data-course-value>
    </div>

    <div class="manual-course-results" role="listbox" hidden data-course-results>
        @foreach($courses as $course)
            @php($courseLabel = $course->kode_mk.' - '.$course->nama_mk.' ('.$course->sks.' SKS)')
            <button
                type="button"
                class="manual-course-option{{ (string) $selected === (string) $course->id ? ' is-selected' : '' }}"
                role="option"
                aria-selected="{{ (string) $selected === (string) $course->id ? 'true' : 'false' }}"
                data-course-option
                data-course-id="{{ $course->id }}"
                data-course-label="{{ $courseLabel }}"
                data-course-search-text="{{ $course->kode_mk }} {{ $course->nama_mk }} {{ $course->prodi?->nama_prodi }}"
            >
                {{ $courseLabel }}{{ $course->prodi?->nama_prodi ? ' - '.$course->prodi->nama_prodi : '' }}
            </button>
        @endforeach
        <div class="manual-course-empty" role="status" hidden data-course-empty>Mata kuliah tidak ditemukan</div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-course-picker]').forEach(function (picker) {
                    const search = picker.querySelector('[data-course-search]');
                    const value = picker.querySelector('[data-course-value]');
                    const results = picker.querySelector('[data-course-results]');
                    const empty = picker.querySelector('[data-course-empty]');
                    const options = Array.from(picker.querySelectorAll('[data-course-option]'));
                    const form = picker.closest('form');
                    let selectedLabel = search.value;

                    const close = () => {
                        results.hidden = true;
                        search.setAttribute('aria-expanded', 'false');
                    };

                    const filter = () => {
                        const query = search.value === selectedLabel ? '' : search.value.trim().toLocaleLowerCase('id-ID');
                        let matches = 0;

                        options.forEach((option) => {
                            const visible = option.dataset.courseSearchText.toLocaleLowerCase('id-ID').includes(query);
                            option.hidden = ! visible;
                            if (visible) matches += 1;
                        });

                        empty.hidden = matches !== 0;
                        results.hidden = false;
                        search.setAttribute('aria-expanded', 'true');
                    };

                    const choose = (option) => {
                        value.value = option.dataset.courseId;
                        search.value = option.dataset.courseLabel;
                        selectedLabel = option.dataset.courseLabel;
                        search.setCustomValidity('');
                        options.forEach((item) => {
                            const isSelected = item === option;
                            item.classList.toggle('is-selected', isSelected);
                            item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                        });
                        close();
                    };

                    search.addEventListener('focus', filter);
                    search.addEventListener('input', function () {
                        if (search.value !== selectedLabel) value.value = '';
                        search.setCustomValidity('');
                        filter();
                    });
                    search.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape' || event.key === 'Tab') close();
                    });
                    options.forEach((option) => option.addEventListener('click', () => choose(option)));
                    document.addEventListener('mousedown', (event) => {
                        if (! picker.contains(event.target)) close();
                    });
                    form?.addEventListener('submit', function (event) {
                        if (! value.value) {
                            event.preventDefault();
                            search.setCustomValidity('Pilih mata kuliah dari daftar hasil pencarian.');
                            search.reportValidity();
                            filter();
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
