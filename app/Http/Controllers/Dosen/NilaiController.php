<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NilaiController extends Controller
{
    // ===================== DAFTAR JADWAL DOSEN =====================
    public function index()
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $jadwals = Jadwal::with([
                'mataKuliah',
                'ruangan',
                'dosen',
            ])
            ->where('dosen_id', $dosen->id)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view('dosen.nilai.index', compact('jadwals'));
    }
// ===================== REKAP NILAI PER KELAS =====================
public function rekap()
{
    $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

    $jadwals = Jadwal::with(['mataKuliah', 'ruangan'])
        ->where('dosen_id', $dosen->id)
        ->orderBy('hari')
        ->orderBy('jam_mulai')
        ->get();

    foreach ($jadwals as $jadwal) {

        $khs = Khs::whereHas('krs', function ($q) use ($jadwal) {
            $q->where('jadwal_id', $jadwal->id);
        })->get();

        $jadwal->jumlah = $khs->count();
        $jadwal->rata = $khs->count() ? round($khs->avg('nilai_angka'), 2) : 0;
        $jadwal->tertinggi = $khs->count() ? $khs->max('nilai_angka') : 0;
        $jadwal->terendah = $khs->count() ? $khs->min('nilai_angka') : 0;

        // Lulus jika nilai >= 60
        $jadwal->lulus = $khs->where('nilai_angka', '>=', 60)->count();
        $jadwal->tidak_lulus = $khs->where('nilai_angka', '<', 60)->count();
    }

    return view('dosen.nilai.rekap', compact('jadwals'));
}
    // ===================== FORM INPUT / EDIT NILAI =====================
    public function show(Jadwal $jadwal)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        // Cegah dosen membuka jadwal milik dosen lain
        if ($jadwal->dosen_id != $dosen->id) {
            abort(403);
        }

        $krs = Krs::with(['mahasiswa', 'khs'])
            ->where('jadwal_id', $jadwal->id)
            ->where('status', 'Disetujui')
            ->get();

        return view('dosen.nilai.input', compact('jadwal', 'krs'));
    }

    // ===================== SIMPAN / UPDATE NILAI =====================
    public function store(Request $request)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'krs_id' => ['required', 'array', 'min:1'],
            'krs_id.*' => ['required', 'integer', 'distinct', 'exists:krs,id'],
            'nilai_angka' => ['required', 'array'],
            'nilai_angka.*' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        if (count($validated['krs_id']) !== count($validated['nilai_angka'])) {
            throw ValidationException::withMessages([
                'nilai_angka' => 'Jumlah nilai tidak sesuai dengan jumlah mahasiswa.',
            ]);
        }

        $krsIds = collect(array_values($validated['krs_id']))->map(fn ($id) => (int) $id);
        $nilaiAngka = array_values($validated['nilai_angka']);
        $krsById = Krs::with('jadwal')
            ->whereIn('id', $krsIds)
            ->where('status', 'Disetujui')
            ->whereHas('jadwal', fn ($query) => $query->where('dosen_id', $dosen->id))
            ->get()
            ->keyBy('id');

        // Tolak seluruh permintaan apabila satu saja KRS bukan dari jadwal dosen ini.
        abort_unless($krsById->count() === $krsIds->count(), 403);

        $adaInputBaru = false;
        $adaPerubahan = false;

        DB::transaction(function () use ($krsIds, $nilaiAngka, $krsById, &$adaInputBaru, &$adaPerubahan) {
            foreach ($krsIds as $i => $krsId) {
                $nilai = $nilaiAngka[$i];

                if ($nilai === null || $nilai === '') {
                    continue;
                }

                // Konversi nilai angka ke huruf & bobot
                if ($nilai >= 85) {
                    $huruf = 'A';
                    $bobot = 4.00;
                } elseif ($nilai >= 80) {
                    $huruf = 'A-';
                    $bobot = 3.75;
                } elseif ($nilai >= 75) {
                    $huruf = 'B+';
                    $bobot = 3.50;
                } elseif ($nilai >= 70) {
                    $huruf = 'B';
                    $bobot = 3.00;
                } elseif ($nilai >= 65) {
                    $huruf = 'B-';
                    $bobot = 2.75;
                } elseif ($nilai >= 60) {
                    $huruf = 'C+';
                    $bobot = 2.50;
                } elseif ($nilai >= 55) {
                    $huruf = 'C';
                    $bobot = 2.00;
                } elseif ($nilai >= 40) {
                    $huruf = 'D';
                    $bobot = 1.00;
                } else {
                    $huruf = 'E';
                    $bobot = 0.00;
                }

                $krs = $krsById->get((int) $krsId);

                // Insert jika belum ada, update jika sudah ada
                $khs = Khs::updateOrCreate(
                    ['krs_id' => $krs->id],
                    [
                        'nilai_angka' => $nilai,
                        'nilai_huruf' => $huruf,
                        'bobot' => $bobot,
                        'tahun_akademik' => $krs->tahun_akademik,
                        'semester_akademik' => $krs->semester_akademik,
                    ]
                );

                if ($khs->wasRecentlyCreated) {
                    $adaInputBaru = true;
                } else {
                    $adaPerubahan = true;
                }
            }
        });

        if ($adaInputBaru && !$adaPerubahan) {
            return redirect()
                ->route('dosen.nilai')
                ->with('success', 'Nilai berhasil diinput.');
        }

        if ($adaPerubahan && !$adaInputBaru) {
            return redirect()
                ->route('dosen.nilai')
                ->with('success', 'Nilai berhasil diubah.');
        }

        return redirect()
            ->route('dosen.nilai')
            ->with('success', 'Nilai berhasil disimpan.');
    }
}
