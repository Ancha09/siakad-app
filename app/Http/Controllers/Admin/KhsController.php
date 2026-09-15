<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Prodi;
use Illuminate\Http\Request;

class KhsController extends Controller
{
    // =====================================================
    // INDEX
    // =====================================================

    public function index(Request $request)
    {
        // ===================== DATA FILTER =====================

        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        $dosens = Dosen::orderBy('nama')
            ->get();

        $kelases = Kelas::with([
            'prodi.fakultas',
        ])
            ->orderBy('angkatan', 'desc')
            ->orderBy('nama_kelas')
            ->get();

        // ===================== DATA ANGKATAN =====================

        $angkatans = Kelas::whereNotNull('angkatan')
            ->select('angkatan')
            ->distinct()
            ->orderBy('angkatan', 'desc')
            ->pluck('angkatan');

        // ===================== QUERY KHS =====================

        $query = Khs::with([
            'krs.mahasiswa.prodi',
            'krs.mahasiswa.kelas.prodi.fakultas',
            'krs.jadwal.mataKuliah.prodi.fakultas',
            'krs.mataKuliahManual.prodi.fakultas',
            'dosenManual', 'krs.dosenManual',
            'krs.prodiManual',
            'krs.jadwal.dosen',
            'krs.jadwal.ruangan',
        ]);

        // =====================================================
        // SEARCH
        // =====================================================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // ===================== MAHASISWA =====================

                $q->whereHas(
                    'krs.mahasiswa',
                    function ($mahasiswa) use ($search) {

                        $mahasiswa
                            ->whereLike('nim', '%'.$search.'%'
                            )
                            ->orWhereLike('nama', '%'.$search.'%'
                            );
                    }
                )

                // ===================== MATA KULIAH =====================
                    ->orWhereHas(
                        'krs.jadwal.mataKuliah',
                        function ($mk) use ($search) {

                            $mk
                                ->whereLike('kode_mk', '%'.$search.'%'
                                )
                                ->orWhereLike('nama_mk', '%'.$search.'%'
                                );
                        }
                    )
                    ->orWhereHas('krs.mataKuliahManual', function ($mk) use ($search) {
                        $mk->whereLike('kode_mk', '%'.$search.'%')->orWhereLike('nama_mk', '%'.$search.'%');
                    })
                    ->orWhereHas('dosenManual', 'krs.dosenManual', fn ($dosen) => $dosen->whereLike('nama', '%'.$search.'%'))

                // ===================== DOSEN =====================
                    ->orWhereHas(
                        'krs.jadwal.dosen',
                        function ($dosen) use ($search) {

                            $dosen->whereLike('nama', '%'.$search.'%'
                            );
                        }
                    );

            });
        }

        // =====================================================
        // FILTER FAKULTAS
        // =====================================================

        if ($request->filled('fakultas_id')) {

            $query->whereHas(
                'krs.mahasiswa.kelas.prodi',
                function ($q) use ($request) {

                    $q->where(
                        'fakultas_id',
                        $request->fakultas_id
                    );
                }
            );
        }

        // =====================================================
        // FILTER PROGRAM STUDI
        // =====================================================

        if ($request->filled('prodi_id')) {

            $query->whereHas('krs', fn ($q) => $q
                ->where('prodi_id', $request->prodi_id)
                ->orWhereHas('mahasiswa', fn ($mahasiswa) => $mahasiswa->where('prodi_id', $request->prodi_id)));
        }

        // =====================================================
        // FILTER KELAS
        // =====================================================

        if ($request->filled('kelas_id')) {

            $query->whereHas(
                'krs.mahasiswa',
                function ($q) use ($request) {

                    $q->where(
                        'kelas_id',
                        $request->kelas_id
                    );
                }
            );
        }

        // =====================================================
        // FILTER ANGKATAN
        // =====================================================

        if ($request->filled('angkatan')) {

            $query->whereHas(
                'krs.mahasiswa.kelas',
                function ($q) use ($request) {

                    $q->where(
                        'angkatan',
                        $request->angkatan
                    );
                }
            );
        }

        // =====================================================
        // FILTER DOSEN
        // =====================================================

        if ($request->filled('dosen_id')) {

            $query->forDosen((int) $request->dosen_id);
        }

        // =====================================================
        // FILTER TAHUN AKADEMIK
        // =====================================================

        if ($request->filled('tahun_akademik')) {

            $query->where(
                'tahun_akademik',
                $request->tahun_akademik
            );
        }

        // =====================================================
        // FILTER SEMESTER
        // =====================================================

        if ($request->filled('semester_akademik')) {

            $query->where(
                'semester_akademik',
                $request->semester_akademik
            );
        }

        // =====================================================
        // HASIL DATA
        // =====================================================

        $khs = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // =====================================================
        // RETURN VIEW
        // =====================================================

        return view(
            'admin.khs.index',
            compact(
                'khs',
                'fakultas',
                'prodis',
                'dosens',
                'kelases',
                'angkatans'
            )
        );
    }

    // =====================================================
    // CREATE
    // =====================================================
    // Untuk sementara tetap ada agar tidak merusak route lama.
    // Tombol tambah tidak ditampilkan di halaman admin.
    // =====================================================

    public function create()
    {
        $krs = Krs::with([
            'mahasiswa.kelas',
            'mahasiswa.prodi',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
            'khs',
        ])
            ->where('status', 'Disetujui')
            ->whereDoesntHave('khs')
            ->latest()
            ->get();

        return view(
            'admin.khs.create',
            compact('krs')
        );
    }

    // =====================================================
    // STORE
    // =====================================================

    public function store(Request $request)
    {
        $request->validate([
            'krs_id' => [
                'required',
                'exists:krs,id',
            ],

            'nilai_angka' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        // ===================== AMBIL KRS =====================

        $krs = Krs::with([
            'mahasiswa',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
            ->findOrFail($request->krs_id);

        // ===================== CEK STATUS KRS =====================

        if ($krs->status !== 'Disetujui') {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KHS hanya dapat dibuat untuk KRS yang sudah disetujui.'
                );
        }

        // ===================== CEK DUPLIKAT =====================

        $sudahAda = Khs::where(
            'krs_id',
            $krs->id
        )->exists();

        if ($sudahAda) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KRS tersebut sudah memiliki data KHS.'
                );
        }

        // ===================== KONVERSI NILAI =====================

        [$huruf, $bobot] = $this->konversiNilai(
            $request->nilai_angka
        );

        // ===================== SIMPAN =====================

        Khs::create([
            'krs_id' => $krs->id,
            'nilai_angka' => $request->nilai_angka,
            'nilai_huruf' => $huruf,
            'bobot' => $bobot,
            'tahun_akademik' => $krs->tahun_akademik,
            'semester_akademik' => $krs->semester_akademik,
        ]);

        return redirect()
            ->route('admin.khs')
            ->with(
                'success',
                'Data KHS berhasil ditambahkan.'
            );
    }

    // =====================================================
    // EDIT
    // =====================================================

    public function edit(Khs $kh)
    {
        $krs = Krs::with([
            'mahasiswa.kelas',
            'mahasiswa.prodi',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
            'khs',
        ])
            ->where('status', 'Disetujui')
            ->where(function ($query) use ($kh) {

                $query
                    ->whereDoesntHave('khs')
                    ->orWhereHas(
                        'khs',
                        function ($q) use ($kh) {

                            $q->where(
                                'id',
                                $kh->id
                            );
                        }
                    );
            })
            ->latest()
            ->get();

        return view(
            'admin.khs.edit',
            [
                'khs' => $kh,
                'krs' => $krs,
            ]
        );
    }

    // =====================================================
    // UPDATE
    // =====================================================

    public function update(
        Request $request,
        Khs $kh
    ) {

        $request->validate([
            'krs_id' => [
                'required',
                'exists:krs,id',
            ],

            'nilai_angka' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        $krs = Krs::with([
            'mahasiswa',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
            ->findOrFail($request->krs_id);

        // ===================== CEK STATUS =====================

        if ($krs->status !== 'Disetujui') {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KHS hanya dapat menggunakan KRS yang sudah disetujui.'
                );
        }

        // ===================== CEK DUPLIKAT =====================

        $sudahAda = Khs::where(
            'krs_id',
            $krs->id
        )
            ->where(
                'id',
                '!=',
                $kh->id
            )
            ->exists();

        if ($sudahAda) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KRS tersebut sudah digunakan oleh KHS lain.'
                );
        }

        // ===================== KONVERSI NILAI =====================

        [$huruf, $bobot] = $this->konversiNilai(
            $request->nilai_angka
        );

        // ===================== UPDATE =====================

        $kh->update([
            'krs_id' => $krs->id,
            'nilai_angka' => $request->nilai_angka,
            'nilai_huruf' => $huruf,
            'bobot' => $bobot,
            'tahun_akademik' => $krs->tahun_akademik,
            'semester_akademik' => $krs->semester_akademik,
        ]);

        return redirect()
            ->route('admin.khs')
            ->with(
                'success',
                'Data KHS berhasil diperbarui.'
            );
    }

    // =====================================================
    // DELETE
    // =====================================================

    public function destroy(Khs $kh)
    {
        $kh->delete();

        return redirect()
            ->route('admin.khs')
            ->with(
                'success',
                'Data KHS berhasil dihapus.'
            );
    }

    // =====================================================
    // KONVERSI NILAI
    // =====================================================

    private function konversiNilai($nilai)
    {
        $nilai = (float) $nilai;

        if ($nilai >= 85) {
            return ['A', 4.00];

        } elseif ($nilai >= 80) {
            return ['A-', 3.75];

        } elseif ($nilai >= 75) {
            return ['B+', 3.50];

        } elseif ($nilai >= 70) {
            return ['B', 3.00];

        } elseif ($nilai >= 65) {
            return ['B-', 2.75];

        } elseif ($nilai >= 60) {
            return ['C+', 2.50];

        } elseif ($nilai >= 55) {
            return ['C', 2.00];

        } elseif ($nilai >= 40) {
            return ['D', 1.00];

        } else {
            return ['E', 0.00];
        }
    }
}
