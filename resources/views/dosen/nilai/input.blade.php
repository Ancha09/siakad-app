@extends('layouts.dosen')

@section('title','Input / Edit Nilai')

@section('content')

<div class="page-card">

<div class="page-card-head">
    <h2>📝 Input / Edit Nilai Mahasiswa</h2>
</div>

<div class="page-card-body">

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:20px;">
            {{ session('success') }}
        </div>
    @endif

    <p><strong>Mata Kuliah:</strong> {{ $jadwal->mataKuliah->nama_mk ?? '-' }}</p>
    <p><strong>Kelas:</strong> {{ $jadwal->kelas }}</p>
    <p><strong>Jadwal:</strong> {{ $jadwal->hari }} | {{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}</p>

    <hr style="margin:20px 0;">

    <div style="margin-bottom:20px;">
        <strong>Konversi Nilai Mutu</strong>

        <table style="margin-top:10px;width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="border:1px solid #ddd;padding:8px;">Nilai Angka</th>
                    <th style="border:1px solid #ddd;padding:8px;">Nilai Huruf</th>
                    <th style="border:1px solid #ddd;padding:8px;">Mutu / Bobot</th>
                </tr>
            </thead>

            <tbody>
                <tr><td style="border:1px solid #ddd;padding:8px;">85 - 100</td><td style="border:1px solid #ddd;padding:8px;">A</td><td style="border:1px solid #ddd;padding:8px;">4.00</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">80 - 84</td><td style="border:1px solid #ddd;padding:8px;">A-</td><td style="border:1px solid #ddd;padding:8px;">3.75</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">75 - 79</td><td style="border:1px solid #ddd;padding:8px;">B+</td><td style="border:1px solid #ddd;padding:8px;">3.50</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">70 - 74</td><td style="border:1px solid #ddd;padding:8px;">B</td><td style="border:1px solid #ddd;padding:8px;">3.00</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">65 - 69</td><td style="border:1px solid #ddd;padding:8px;">B-</td><td style="border:1px solid #ddd;padding:8px;">2.75</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">60 - 64</td><td style="border:1px solid #ddd;padding:8px;">C+</td><td style="border:1px solid #ddd;padding:8px;">2.50</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">55 - 59</td><td style="border:1px solid #ddd;padding:8px;">C</td><td style="border:1px solid #ddd;padding:8px;">2.00</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">40 - 54</td><td style="border:1px solid #ddd;padding:8px;">D</td><td style="border:1px solid #ddd;padding:8px;">1.00</td></tr>
                <tr><td style="border:1px solid #ddd;padding:8px;">0 - 39</td><td style="border:1px solid #ddd;padding:8px;">E</td><td style="border:1px solid #ddd;padding:8px;">0.00</td></tr>
            </tbody>
        </table>
    </div>

    <form action="{{ route('dosen.nilai.store') }}" method="POST">

        @csrf

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Nilai Angka</th>
                        <th>Huruf</th>
                        <th>Mutu</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($krs as $item)

                    <tr>

                        <td>{{ $item->mahasiswa->nim }}</td>

                        <td>{{ $item->mahasiswa->nama }}</td>

                        <td>

                            <input
                                type="hidden"
                                name="krs_id[]"
                                value="{{ $item->id }}">

                            <input
                                type="number"
                                name="nilai_angka[]"
                                class="form-control nilai-input"
                                min="0"
                                max="100"
                                step="0.01"
                                value="{{ $item->khs->nilai_angka ?? '' }}">

                        </td>

                        <td class="nilai-huruf">
                            {{ $item->khs->nilai_huruf ?? '-' }}
                        </td>

                        <td class="nilai-bobot">
                            {{ $item->khs->bobot ?? '-' }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" style="text-align:center;padding:30px;">
                            Belum ada mahasiswa mengambil mata kuliah ini.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <br>

        <button type="submit" class="btn-primary">
            💾 Simpan Nilai
        </button>

        <a href="{{ route('dosen.nilai') }}" class="btn-outline">
            Kembali
        </a>

    </form>

</div>
</div>

<script>
document.querySelectorAll('.nilai-input').forEach(function(input){

    input.addEventListener('input', function(){

        const nilai = parseFloat(this.value);

        const row = this.closest('tr');
        const huruf = row.querySelector('.nilai-huruf');
        const bobot = row.querySelector('.nilai-bobot');

        if (isNaN(nilai)) {
            huruf.textContent = '-';
            bobot.textContent = '-';
            return;
        }

        if (nilai >= 85) {
            huruf.textContent = 'A';
            bobot.textContent = '4.00';
        } else if (nilai >= 80) {
            huruf.textContent = 'A-';
            bobot.textContent = '3.75';
        } else if (nilai >= 75) {
            huruf.textContent = 'B+';
            bobot.textContent = '3.50';
        } else if (nilai >= 70) {
            huruf.textContent = 'B';
            bobot.textContent = '3.00';
        } else if (nilai >= 65) {
            huruf.textContent = 'B-';
            bobot.textContent = '2.75';
        } else if (nilai >= 60) {
            huruf.textContent = 'C+';
            bobot.textContent = '2.50';
        } else if (nilai >= 55) {
            huruf.textContent = 'C';
            bobot.textContent = '2.00';
        } else if (nilai >= 40) {
            huruf.textContent = 'D';
            bobot.textContent = '1.00';
        } else {
            huruf.textContent = 'E';
            bobot.textContent = '0.00';
        }

    });

});
</script>

@endsection
