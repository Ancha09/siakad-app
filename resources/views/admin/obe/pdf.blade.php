<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kurikulum OBE &amp; RPS — {{ $prodiName }}</title>
    @include('admin.reports.pdf-style')
</head>
<body>
    @php
        $reportTitle = 'Laporan Kurikulum OBE & RPS — ' . $prodiName;
        $deskripsiFilter = $filterDescription;
    @endphp
    @include('admin.reports.pdf-header')

    <h2>1. Daftar Capaian Pembelajaran Lulusan (CPL)</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 90px;">Kode CPL</th>
                <th style="width: 220px;">Nama / Judul Singkat</th>
                <th>Deskripsi Capaian Pembelajaran</th>
                <th style="width: 100px;">Jml Sub-CPMK</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cpls as $cpl)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td><strong>{{ $cpl->kode_cpl }}</strong></td>
                    <td>{{ $cpl->nama_cpl ?: '-' }}</td>
                    <td>{{ $cpl->deskripsi ?: '-' }}</td>
                    <td class="center">{{ $cpl->sub_cpmks_count ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center muted">Belum ada data CPL pada program studi ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>2. Daftar Dokumen &amp; Target Porsi CPL pada RPS</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 90px;">Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th style="width: 55px;">SKS</th>
                <th style="width: 70px;">Semester</th>
                <th style="width: 100px;">Tahun Ajaran</th>
                <th style="width: 90px;">Passing Grade</th>
                <th>Target Porsi CPL</th>
                <th style="width: 75px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rpsItems as $rps)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td><strong>{{ $rps->mataKuliah?->kode_mk ?? '-' }}</strong></td>
                    <td>{{ $rps->mataKuliah?->nama_mk ?? '-' }}</td>
                    <td class="center">{{ $rps->mataKuliah?->sks ?? '-' }}</td>
                    <td class="center">{{ $rps->mataKuliah?->semester ?? '-' }}</td>
                    <td class="center">{{ $rps->tahun_akademik ?: 'Reguler' }}</td>
                    <td class="center">{{ $rps->target_passing_grade }}</td>
                    <td>
                        @if(! empty($rps->porsi_cpl) && is_array($rps->porsi_cpl))
                            @php
                                $parts = [];
                                foreach ($rps->porsi_cpl as $k => $v) {
                                    $parts[] = "{$k}: {$v}%";
                                }
                            @endphp
                            {{ implode(' | ', $parts) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{{ $rps->is_active ? 'Aktif' : 'Arsip' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center muted">Belum ada dokumen RPS pada program studi ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>3. Matriks CPMK &amp; Sub-CPMK Mata Kuliah</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 85px;">Kode MK</th>
                <th style="width: 160px;">Mata Kuliah</th>
                <th style="width: 85px;">Kode CPMK</th>
                <th style="width: 95px;">Kode Sub-CPMK</th>
                <th>Deskripsi Sub-CPMK</th>
                <th style="width: 85px;">CPL</th>
                <th style="width: 80px;">Bobot (%)</th>
            </tr>
        </thead>
        <tbody>
            @php $hasSubRows = false; @endphp
            @foreach($cpmks as $cpmk)
                @foreach($cpmk->subCpmks as $sub)
                    @php $hasSubRows = true; @endphp
                    <tr>
                        <td><strong>{{ $cpmk->mataKuliah?->kode_mk ?? '-' }}</strong></td>
                        <td>{{ $cpmk->mataKuliah?->nama_mk ?? '-' }}</td>
                        <td>{{ $cpmk->kode_cpmk }}</td>
                        <td><strong>{{ $sub->kode_sub_cpmk }}</strong></td>
                        <td>{{ $sub->deskripsi ?: '-' }}</td>
                        <td class="center">{{ $sub->cpl?->kode_cpl ?? '-' }}</td>
                        <td class="center">{{ $sub->bobot_default !== null ? $sub->bobot_default . '%' : '-' }}</td>
                    </tr>
                @endforeach
            @endforeach
            @if(! $hasSubRows)
                <tr>
                    <td colspan="7" class="center muted">Belum ada pemetaan Sub-CPMK pada program studi ini.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>

