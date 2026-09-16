<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;

class KrsController extends Controller
{
    // ===================== INDEX =====================

    public function index(Request $request)
    {
        // ===================== DATA FAKULTAS =====================

        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        // ===================== DATA PRODI =====================

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        // ===================== DATA KELAS =====================

        $kelases = Kelas::with([
            'prodi.fakultas',
        ])
            ->orderBy('angkatan', 'desc')
            ->orderBy('nama_kelas')
            ->get();

        // ===================== DATA DOSEN =====================

        $dosens = Dosen::orderBy('nama')
            ->get();

        // ===================== DATA MAHASISWA =====================

        $mahasiswas = Mahasiswa::with([
            'prodi.fakultas',
            'kelas',
        ])
            ->orderBy('nama')
            ->get();

        // ===================== QUERY KRS =====================

        $query = Krs::with([
            'mahasiswa.prodi.fakultas',
            'mahasiswa.kelas',
            'jadwal.mataKuliah.prodi.fakultas',
            'jadwal.dosen',
            'jadwal.ruangan',
            'jadwal.kelas.prodi.fakultas',
        ])->where('is_manual', false);

        // ===================== SEARCH =====================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // Cari mahasiswa
                $q->whereHas('mahasiswa', function ($mhs) use ($search) {

                    $mhs->whereLike('nim', '%'.$search.'%')
                        ->orWhereLike('nama', '%'.$search.'%');

                })

                // Cari mata kuliah
                    ->orWhereHas('jadwal.mataKuliah', function ($mk) use ($search) {

                        $mk->whereLike('kode_mk', '%'.$search.'%')
                            ->orWhereLike('nama_mk', '%'.$search.'%');

                    })

                // Cari dosen
                    ->orWhereHas('jadwal.dosen', function ($dosen) use ($search) {

                        $dosen->whereLike('nama', '%'.$search.'%'
                        );

                    });

            });
        }

        // ===================== FILTER FAKULTAS =====================

        if ($request->filled('fakultas_id')) {

            $query->whereHas('mahasiswa.prodi', function ($q) use ($request) {

                $q->where(
                    'fakultas_id',
                    $request->fakultas_id
                );

            });
        }

        // ===================== FILTER PRODI =====================

        if ($request->filled('prodi_id')) {

            $query->whereHas('mahasiswa', function ($q) use ($request) {

                $q->where(
                    'prodi_id',
                    $request->prodi_id
                );

            });
        }

        // ===================== FILTER KELAS =====================

        if ($request->filled('kelas_id')) {

            $query->whereHas('mahasiswa', function ($q) use ($request) {

                $q->where(
                    'kelas_id',
                    $request->kelas_id
                );

            });
        }

        // ===================== FILTER ANGKATAN =====================

        if ($request->filled('angkatan')) {

            $query->whereHas('mahasiswa.kelas', function ($q) use ($request) {

                $q->where(
                    'angkatan',
                    $request->angkatan
                );

            });
        }

        // ===================== FILTER DOSEN =====================

        if ($request->filled('dosen_id')) {

            $query->whereHas('jadwal', function ($q) use ($request) {

                $q->where(
                    'dosen_id',
                    $request->dosen_id
                );

            });
        }

        // ===================== FILTER MAHASISWA =====================

        if ($request->filled('mahasiswa_id')) {

            $query->where(
                'mahasiswa_id',
                $request->mahasiswa_id
            );
        }

        // ===================== FILTER STATUS =====================

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        // ===================== FILTER SEMESTER AKADEMIK =====================

        if ($request->filled('semester_akademik')) {

            $query->where(
                'semester_akademik',
                $request->semester_akademik
            );
        }

        // ===================== FILTER TAHUN AKADEMIK =====================

        if ($request->filled('tahun_akademik')) {

            $query->where(
                'tahun_akademik',
                $request->tahun_akademik
            );
        }

        // ===================== HASIL =====================

        $krs = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ===================== TAHUN AKADEMIK =====================

        $tahunAkademiks = Krs::select('tahun_akademik')
            ->whereNotNull('tahun_akademik')
            ->distinct()
            ->orderBy('tahun_akademik', 'desc')
            ->pluck('tahun_akademik');

        // ===================== ANGKATAN =====================

        $angkatans = Kelas::select('angkatan')
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderBy('angkatan', 'desc')
            ->pluck('angkatan');

        // ===================== RETURN VIEW =====================

        return view(
            'admin.krs.index',
            compact(
                'krs',
                'fakultas',
                'prodis',
                'kelases',
                'dosens',
                'mahasiswas',
                'tahunAkademiks',
                'angkatans'
            )
        );
    }

    // ===================== CREATE =====================

    public function create()
    {
        $mahasiswas = Mahasiswa::with([
            'prodi',
            'kelas',
        ])
            ->orderBy('nama')
            ->get();

        $jadwals = Jadwal::with([
            'mataKuliah',
            'dosen',
            'ruangan',
            'kelas.prodi',
        ])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view(
            'admin.krs.create',
            compact(
                'mahasiswas',
                'jadwals'
            )
        );
    }

    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'jadwal_id' => 'required|exists:jadwals,id',
            'status' => 'required|in:Diambil,Disetujui,Ditolak',
            'tahun_akademik' => 'required',
            'semester_akademik' => 'required|in:Ganjil,Genap',
        ]);

        // Cek duplikasi

        $cek = Krs::where(
            'mahasiswa_id',
            $request->mahasiswa_id
        )
            ->where(
                'jadwal_id',
                $request->jadwal_id
            )
            ->first();

        if ($cek) {

            return back()
                ->withInput()
                ->withErrors([
                    'jadwal_id' => 'Mahasiswa sudah mengambil jadwal ini.',
                ]);
        }

        Krs::create([
            'mahasiswa_id' => $request->mahasiswa_id,
            'jadwal_id' => $request->jadwal_id,
            'status' => $request->status,
            'tahun_akademik' => $request->tahun_akademik,
            'semester_akademik' => $request->semester_akademik,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.krs'))
            ->with(
                'success',
                'Data KRS berhasil ditambahkan.'
            );
    }

    // ===================== EDIT =====================

    public function edit(Krs $kr)
    {
        abort_if($kr->is_manual, 404);
        $mahasiswas = Mahasiswa::with([
            'prodi',
            'kelas',
        ])
            ->orderBy('nama')
            ->get();

        $jadwals = Jadwal::with([
            'mataKuliah',
            'dosen',
            'ruangan',
            'kelas.prodi',
        ])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view(
            'admin.krs.edit',
            [
                'krs' => $kr,
                'mahasiswas' => $mahasiswas,
                'jadwals' => $jadwals,
            ]
        );
    }

    // ===================== UPDATE =====================

    public function update(
        Request $request,
        Krs $kr
    ) {

        abort_if($kr->is_manual, 404);

        $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'jadwal_id' => 'required|exists:jadwals,id',
            'status' => 'required|in:Diambil,Disetujui,Ditolak',
            'tahun_akademik' => 'required',
            'semester_akademik' => 'required|in:Ganjil,Genap',
        ]);

        // Cek duplikasi

        $cek = Krs::where(
            'mahasiswa_id',
            $request->mahasiswa_id
        )
            ->where(
                'jadwal_id',
                $request->jadwal_id
            )
            ->where(
                'id',
                '!=',
                $kr->id
            )
            ->first();

        if ($cek) {

            return back()
                ->withInput()
                ->withErrors([
                    'jadwal_id' => 'Mahasiswa sudah mengambil jadwal ini.',
                ]);
        }

        $kr->update([
            'mahasiswa_id' => $request->mahasiswa_id,
            'jadwal_id' => $request->jadwal_id,
            'status' => $request->status,
            'tahun_akademik' => $request->tahun_akademik,
            'semester_akademik' => $request->semester_akademik,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.krs'))
            ->with(
                'success',
                'Data KRS berhasil diperbarui.'
            );
    }

    // ===================== DELETE =====================

    public function destroy(Krs $kr)
    {
        abort_if($kr->is_manual, 404);
        $kr->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.krs'))
            ->with(
                'success',
                'Data KRS berhasil dihapus.'
            );
    }
}
