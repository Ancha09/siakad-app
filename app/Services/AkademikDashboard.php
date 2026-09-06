<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Models\Presensi;
use App\Models\Prodi;
use Illuminate\Support\Collection;

class AkademikDashboard
{
    public function years(): Collection
    {
        $year = now()->month >= 7 ? now()->year : now()->year - 1;
        return Jadwal::distinct()->pluck('tahun_akademik')
            ->merge(Khs::distinct()->pluck('tahun_akademik'))
            ->merge(PeriodeKrs::distinct()->pluck('tahun_akademik'))
            ->push($year.'/'.($year + 1))
            ->filter(fn ($value) => preg_match('/^\d{4}\/\d{4}$/', (string) $value))
            ->unique()->sortDesc()->values();
    }

    public function summary(string $year): array
    {
        $start = (int) substr($year, 0, 4);
        $previousYear = ($start - 1).'/'.$start;
        $jadwals = Jadwal::where('tahun_akademik', $year)
            ->withCount(['presensiPertemuans as pertemuan_count' => fn ($q) => $q->whereDate('tanggal', '<=', today())])
            ->get();
        $activeLecturers = Dosen::whereHas('jadwals', fn ($q) => $q->where('tahun_akademik', $year))->count();
        $presensi = Presensi::whereDate('tanggal', '<=', today())
            ->whereHas('krs', fn ($q) => $q->where('status', 'Disetujui')
                ->whereHas('jadwal', fn ($j) => $j->where('tahun_akademik', $year)))
            ->select('status')->selectRaw('COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $attendanceTotal = $presensi->sum();
        $completed = $jadwals->sum(fn ($jadwal) => min(16, $jadwal->pertemuan_count));
        $planned = $jadwals->count() * 16;

        // Relasi KHS -> KRS -> mahasiswa & jadwal -> mata kuliah; nilai kosong tidak menjadi nol.
        $grades = Khs::with(['krs.mahasiswa', 'krs.jadwal.mataKuliah'])
            ->whereNotNull('bobot')->whereBetween('bobot', [0, 4])
            ->where('tahun_akademik', '<=', $year)
            ->whereHas('krs', fn ($q) => $q->where('status', 'Disetujui'))
            ->get()->filter(fn ($g) => $g->krs?->mahasiswa && ($g->krs?->jadwal?->mataKuliah?->sks ?? 0) > 0);
        $current = $this->snapshot($grades, $year);
        $previous = $this->snapshot($grades, $previousYear);
        $hasCurrentGrades = $grades->contains('tahun_akademik', $year);
        $hasPreviousGrades = $grades->contains('tahun_akademik', $previousYear);
        $delta = $hasCurrentGrades && $hasPreviousGrades && $current->isNotEmpty() && $previous->isNotEmpty()
            ? round($current->avg('ipk') - $previous->avg('ipk'), 2) : null;
        $prodis = Prodi::withCount(['mahasiswas', 'dosens'])->get()->map(function ($prodi) use ($current) {
            $students = $current->where('prodi_id', $prodi->id);
            $prodi->ipk = $students->isEmpty() ? null : $students->avg('ipk');
            $prodi->jumlah_bernilai = $students->count();
            return $prodi;
        });

        return compact('year', 'previousYear', 'activeLecturers', 'attendanceTotal', 'completed', 'planned',
            'delta', 'prodis', 'hasCurrentGrades', 'hasPreviousGrades') + [
            'totalLecturers' => Dosen::count(),
            'totalStudents' => Mahasiswa::count(),
            'totalClasses' => $jadwals->count(),
            'attendance' => $attendanceTotal ? round(($presensi['Hadir'] ?? 0) / $attendanceTotal * 100, 1) : null,
            'progress' => $planned ? round($completed / $planned * 100, 1) : null,
            'ipk' => $current->isEmpty() ? null : $current->avg('ipk'),
            'previousIpk' => $previous->isEmpty() ? null : $previous->avg('ipk'),
            'gradedStudents' => $current->count(),
            'previousGradedStudents' => $previous->count(),
        ];
    }

    public function snapshot(Collection $grades, string $year): Collection
    {
        return $grades->filter(fn ($g) => $g->tahun_akademik <= $year)
            ->groupBy('krs.mahasiswa_id')->map(function ($items) {
                $sks = $items->sum(fn ($g) => $g->krs->jadwal->mataKuliah->sks);
                $mutu = $items->sum(fn ($g) => $g->bobot * $g->krs->jadwal->mataKuliah->sks);
                return ['ipk' => $mutu / $sks, 'prodi_id' => $items->first()->krs->mahasiswa->prodi_id];
            });
    }
}
