<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Kuesioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluasiController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'dosen', 403);
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $query = Kuesioner::with(['krs.jadwal.mataKuliah', 'krs.jadwal.kelas'])
            ->whereHas('krs.jadwal', function (Builder $jadwal) use ($dosen, $request) {
                $jadwal->where('dosen_id', $dosen->id)
                    ->when($request->filled('mata_kuliah_id'), fn (Builder $q) => $q->where('mata_kuliah_id', $request->mata_kuliah_id));
            })
            ->whereHas('krs', function (Builder $krs) use ($request) {
                $krs->when($request->filled('tahun_akademik'), fn (Builder $q) => $q->where('tahun_akademik', $request->tahun_akademik))
                    ->when($request->filled('semester_akademik'), fn (Builder $q) => $q->where('semester_akademik', $request->semester_akademik));
            });

        $semuaJawaban = (clone $query)->get();
        $rataPertanyaan = collect(array_keys(Kuesioner::PERTANYAAN))
            ->mapWithKeys(fn (string $kolom) => [$kolom => $semuaJawaban->count() ? round($semuaJawaban->avg($kolom), 2) : 0]);

        $komentar = (clone $query)
            ->whereNotNull('komentar')
            ->where('komentar', '!=', '')
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $jadwals = Jadwal::where('dosen_id', $dosen->id)
            ->with('mataKuliah')
            ->get()
            ->unique('mata_kuliah_id');

        $tahunAkademik = Krs::whereHas('jadwal', fn (Builder $q) => $q->where('dosen_id', $dosen->id))
            ->distinct()
            ->orderByDesc('tahun_akademik')
            ->pluck('tahun_akademik');

        return view('dosen.evaluasi.index', [
            'jumlahResponden' => $semuaJawaban->count(),
            'rataRata' => $semuaJawaban->count() ? round($semuaJawaban->avg('rata_rata'), 2) : 0,
            'rataPertanyaan' => $rataPertanyaan,
            'komentar' => $komentar,
            'pertanyaan' => Kuesioner::PERTANYAAN,
            'jadwals' => $jadwals,
            'tahunAkademik' => $tahunAkademik,
        ]);
    }
}
