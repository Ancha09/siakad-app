<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Ketercapaian CPL — {{ $prodiName }}</title>
    @include('admin.reports.pdf-style')
</head>
<body>
    @php
        $reportTitle = 'Laporan Analisis Ketercapaian CPL — ' . $prodiName;
        $deskripsiFilter = $filterDescription;
        $cpls = $analytics['cpls'] ?? [];
    @endphp
    @include('admin.reports.pdf-header')

    <h2>1. Ringkasan Capaian Pembelajaran Lulusan (Weighted Average &amp; % Ketercapaian)</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 36px;">No</th>
                <th style="width: 80px;">Kode CPL</th>
                <th>Deskripsi Capaian Pembelajaran</th>
                <th style="width: 75px;">Jml MK</th>
                <th style="width: 95px;">&Sigma; (w &times; SKS)</th>
                <th style="width: 110px;">Weighted Average</th>
                <th style="width: 110px;">Mhs Lolos (&ge; {{ (int) $ambang }})</th>
                <th style="width: 100px;">% Ketercapaian</th>
                <th style="width: 110px;">Status (Target {{ (int) $target }}%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cpls as $cpl)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td><strong>{{ $cpl['kode_cpl'] }}</strong></td>
                    <td>{{ $cpl['deskripsi'] ?: $cpl['nama_cpl'] }}</td>
                    <td class="center">{{ $cpl['course_count'] }}</td>
                    <td class="center">{{ number_format((float) $cpl['total_ws'], 2, ',', '.') }}</td>
                    <td class="center">
                        <strong>{{ $cpl['weighted_average'] !== null ? number_format((float) $cpl['weighted_average'], 2, ',', '.') : '—' }}</strong>
                    </td>
                    <td class="center">{{ $cpl['passed_students_count'] }} / {{ $cpl['evaluated_students_count'] }}</td>
                    <td class="center">
                        <strong>{{ $cpl['percent_achieved'] !== null ? number_format((float) $cpl['percent_achieved'], 1, ',', '.') . '%' : '—' }}</strong>
                    </td>
                    <td class="center">
                        @if($cpl['percent_achieved'] === null)
                            Belum Ada Nilai
                        @elseif($cpl['is_target_met'])
                            <strong>Tercapai</strong>
                        @else
                            Belum Tercapai
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center muted">Belum ada data CPL untuk program studi yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Rincian Kontribusi Mata Kuliah per CPL (w &times; SKS)</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 80px;">Kode CPL</th>
                <th style="width: 90px;">Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th style="width: 55px;">SKS</th>
                <th style="width: 95px;">Porsi CPL (w)</th>
                <th style="width: 85px;">w &times; SKS</th>
                <th style="width: 85px;">Jatah (%)</th>
                <th style="width: 105px;">Rata-rata Skor CPL</th>
                <th style="width: 110px;">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @php $hasCourseRows = false; @endphp
            @foreach($cpls as $cpl)
                @foreach($cpl['courses'] ?? [] as $m)
                    @php $hasCourseRows = true; @endphp
                    <tr>
                        <td><strong>{{ $cpl['kode_cpl'] }}</strong></td>
                        <td>{{ $m['kode_mk'] }}</td>
                        <td>{{ $m['nama_mk'] }}</td>
                        <td class="center">{{ $m['sks'] }}</td>
                        <td class="center">{{ number_format((float) $m['w_percent'], 2, ',', '.') }}% ({{ number_format((float) $m['w'], 4, ',', '.') }})</td>
                        <td class="center"><strong>{{ number_format((float) $m['ws'], 2, ',', '.') }}</strong></td>
                        <td class="center">{{ number_format((float) $m['jatah_percent'], 1, ',', '.') }}%</td>
                        <td class="center">{{ $m['avg_score'] !== null ? number_format((float) $m['avg_score'], 1, ',', '.') : '—' }}</td>
                        <td class="center">{{ $m['kontribusi_avg'] !== null ? number_format((float) $m['kontribusi_avg'], 1, ',', '.') : '—' }}</td>
                    </tr>
                @endforeach
            @endforeach
            @if(! $hasCourseRows)
                <tr>
                    <td colspan="9" class="center muted">Belum ada pemetaan mata kuliah ke CPL.</td>
                </tr>
            @endif
        </tbody>
    </table>

    @foreach($cpls as $cpl)
        @if(! empty($cpl['students']))
            <h2>3. Evaluasi Capaian Kohort Mahasiswa — {{ $cpl['kode_cpl'] }}</h2>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th style="width: 110px;">NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th style="width: 80px;">Angkatan</th>
                        <th style="width: 100px;">&Sigma; (w &times; SKS)</th>
                        <th style="width: 110px;">&Sigma; Kontribusi</th>
                        <th style="width: 120px;">Nilai Capaian {{ $cpl['kode_cpl'] }}</th>
                        <th style="width: 100px;">&ge; {{ (int) $ambang }}?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cpl['students'] as $st)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>{{ $st['nim'] }}</td>
                            <td>{{ $st['nama'] }}</td>
                            <td class="center">{{ $st['angkatan'] ?: '-' }}</td>
                            <td class="center">{{ number_format((float) $st['sum_ws'], 2, ',', '.') }}</td>
                            <td class="center">{{ number_format((float) $st['sum_kontribusi'], 1, ',', '.') }}</td>
                            <td class="center"><strong>{{ number_format((float) $st['score'], 1, ',', '.') }}</strong></td>
                            <td class="center">{{ $st['passed'] ? 'Ya' : 'Tidak' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>

