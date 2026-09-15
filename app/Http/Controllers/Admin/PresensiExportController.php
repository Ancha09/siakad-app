<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Presensi;
use App\Models\Prodi;
use App\Services\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PresensiExportController extends Controller
{
    public function excel(Request $request, ReportExporter $exporter)
    {
        $data = $this->data($request);

        return $exporter->excel(
            'rekap-presensi-'.now()->format('Ymd-His').'.xlsx',
            'Rekap Presensi Mahasiswa',
            $this->deskripsiFilter($request),
            [[
                'title' => 'Rekap Presensi',
                'headings' => ['NIM', 'Nama Mahasiswa', 'Program Studi', 'Kelas', 'Mata Kuliah', 'Dosen', 'Pertemuan', 'Tanggal', 'Status'],
                'rows' => $data->map(fn (Presensi $item) => [
                    $item->krs?->mahasiswa?->nim ?? '-',
                    $item->krs?->mahasiswa?->nama ?? '-',
                    $item->krs?->prodi_efektif?->nama_prodi ?? '-',
                    $item->krs?->kelas_efektif?->nama_kelas ?? '-',
                    $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-',
                    $item->dosen_efektif?->nama ?? '-',
                    $item->pertemuan ? 'Pertemuan '.$item->pertemuan : '-',
                    $item->tanggal ? Carbon::parse($item->tanggal)->format('d-m-Y') : '-',
                    $item->status,
                ]),
            ]]
        );
    }

    public function pdf(Request $request)
    {
        $data = $this->data($request);

        return Pdf::loadView('admin.presensi.print', [
            'reportTitle' => 'Rekap Presensi Mahasiswa',
            'deskripsiFilter' => $this->deskripsiFilter($request),
            'presensis' => $data,
            'summary' => [
                'total' => $data->count(),
                'hadir' => $data->where('status', 'Hadir')->count(),
                'izin' => $data->where('status', 'Izin')->count(),
                'sakit' => $data->where('status', 'Sakit')->count(),
                'alpha' => $data->where('status', 'Alpha')->count(),
            ],
        ])->setPaper('a4', 'landscape')
            ->download('rekap-presensi-'.now()->format('Ymd-His').'.pdf');
    }

    private function data(Request $request): Collection
    {
        $request->validate([
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'pertemuan' => ['nullable', 'integer', 'min:1'],
        ]);

        return Presensi::with([
            'dosenManual', 'krs.mahasiswa.prodi', 'krs.mahasiswa.kelas',
            'krs.jadwal.mataKuliah', 'krs.jadwal.dosen',
            'krs.mataKuliahManual', 'krs.dosenManual',
            'krs.prodiManual', 'krs.kelasManual',
        ])
            ->when($request->filled('prodi_id'), fn (Builder $q) => $q->whereHas('krs', fn (Builder $krs) => $krs->where('prodi_id', $request->prodi_id)->orWhereHas('mahasiswa', fn (Builder $m) => $m->where('prodi_id', $request->prodi_id))))
            ->when($request->filled('kelas_id'), fn (Builder $q) => $q->whereHas('krs', fn (Builder $krs) => $krs->where('kelas_id', $request->kelas_id)->orWhereHas('mahasiswa', fn (Builder $m) => $m->where('kelas_id', $request->kelas_id))))
            ->when($request->filled('dosen_id'), fn (Builder $q) => $q->forDosen((int) $request->dosen_id))
            ->when($request->filled('mata_kuliah_id'), fn (Builder $q) => $q->whereHas('krs', fn (Builder $krs) => $krs->where('mata_kuliah_id', $request->mata_kuliah_id)->orWhereHas('jadwal', fn (Builder $j) => $j->where('mata_kuliah_id', $request->mata_kuliah_id))))
            ->when($request->filled('pertemuan'), fn (Builder $q) => $q->where('pertemuan', $request->pertemuan))
            ->orderByDesc('tanggal')->orderBy('pertemuan')->get();
    }

    private function deskripsiFilter(Request $request): string
    {
        return collect([
            $request->filled('prodi_id') ? 'Prodi '.Prodi::find($request->prodi_id)?->nama_prodi : null,
            $request->filled('kelas_id') ? 'Kelas '.Kelas::find($request->kelas_id)?->nama_kelas : null,
            $request->filled('dosen_id') ? 'Dosen '.Dosen::find($request->dosen_id)?->nama : null,
            $request->filled('mata_kuliah_id') ? 'Mata kuliah '.MataKuliah::find($request->mata_kuliah_id)?->nama_mk : null,
            $request->filled('pertemuan') ? 'Pertemuan '.$request->pertemuan : null,
        ])->filter()->implode(' | ') ?: 'Semua data';
    }
}
