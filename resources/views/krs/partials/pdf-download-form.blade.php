@php($buttonLabel = $buttonLabel ?? 'Download KRS PDF')

<form method="POST" action="{{ $action }}" class="krs-pdf-download-form">
    @csrf
    <input type="hidden" name="tahun_akademik" value="{{ $tahunAkademik }}">
    <input type="hidden" name="semester_akademik" value="{{ $semesterAkademik }}">
    <button type="submit" class="btn-outline icon-button">
        <x-layout-icon name="download" /> {{ $buttonLabel }}
    </button>
</form>
