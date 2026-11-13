<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }} STTMI</title>
    @include('admin.reports.pdf-style')
    <style>
        .progress-track {
            background: #e2e8f0;
            height: 7px;
            border-radius: 3px;
            overflow: hidden;
            width: 75px;
        }
        .progress-fill {
            height: 7px;
            border-radius: 3px;
        }
        .progress-text {
            font-size: 8px;
            font-weight: bold;
            color: #1e293b;
            white-space: nowrap;
        }
        .mk-kode {
            font-size: 9px;
            font-weight: bold;
            color: #0f2a55;
        }
        .mk-nama {
            font-size: 8px;
            color: #475569;
        }
    </style>
</head>
<body>
    @include('admin.reports.pdf-header')

    <table class="summary">
        <tr>
            <td><span>Total Presensi</span><strong>{{ $summary['total'] }}</strong></td>
            <td><span>Hadir</span><strong style="color:#059669;">{{ $summary['hadir'] }}</strong></td>
            <td><span>Izin</span><strong style="color:#ca8a04;">{{ $summary['izin'] }}</strong></td>
            <td><span>Sakit</span><strong style="color:#2563eb;">{{ $summary['sakit'] }}</strong></td>
            <td><span>Alpha</span><strong style="color:#dc2626;">{{ $summary['alpha'] }}</strong></td>
        </tr>
    </table>

    <h2>Progres Pertemuan Mata Kuliah (Target Standar: 16 Pertemuan)</h2>

    <table class="data">
        <thead>
            <tr>
                <th style="width:25px; text-align:center;">NO</th>
                <th style="width:26%;">MATA KULIAH</th>
                <th style="width:22%;">DOSEN PENGAMPU</th>
                <th style="width:18%;">KELAS / RUANGAN</th>
                <th style="width:17%;">JADWAL</th>
                <th style="width:17%;">STATUS PROGRES PERTEMUAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($progresMataKuliah as $jadwalItem)
                @php
                    $terlaksana = $jadwalItem->total_pertemuan ?? 0;
                    $pct = min(100, round(($terlaksana / 16) * 100));
                    $barColor = $pct >= 100 ? '#10b981' : ($pct >= 50 ? '#3b82f6' : '#f59e0b');
                    $kelasNama = $jadwalItem->kelas?->nama_kelas ?? $jadwalItem->kelas ?? '-';
                    $ruangNama = $jadwalItem->ruangan?->nama_ruangan ?? '-';
                    $kelasRuangan = ($kelasNama !== '-' || $ruangNama !== '-')
                        ? ($kelasNama . ' · ' . $ruangNama)
                        : '-';
                @endphp
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="mk-kode">{{ $jadwalItem->mataKuliah?->kode_mk ?? '-' }}</div>
                        <div class="mk-nama">{{ $jadwalItem->mataKuliah?->nama_mk ?? '-' }}</div>
                    </td>
                    <td>
                        {{ $jadwalItem->semua_dosen_nama ?: ($jadwalItem->dosen?->nama ?? '-') }}
                    </td>
                    <td>
                        {{ $kelasRuangan }}
                    </td>
                    <td>
                        {{ $jadwalItem->hari }}, {{ $jadwalItem->jam_mulai }} - {{ $jadwalItem->jam_selesai }}
                    </td>
                    <td>
                        <table style="width:100%; border:0; border-collapse:collapse; padding:0; margin:0;">
                            <tr>
                                <td style="border:0; padding:0; width:55px; vertical-align:middle;">
                                    <div class="progress-track" style="width:55px;">
                                        <div class="progress-fill" @style(['width: ' . max(4, $pct) . '%', 'background: ' . $barColor])></div>
                                    </div>
                                </td>
                                <td style="border:0; padding:0 0 0 6px; vertical-align:middle;" class="progress-text">
                                    Pertemuan {{ $terlaksana }} / 16
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center muted">Belum ada data jadwal mata kuliah sesuai filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
