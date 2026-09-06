<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Kelas;
use App\Models\Fakultas;
use App\Models\Prodi;
use Illuminate\Http\Request;

class JadwalController extends Controller
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


        // ===================== DATA DOSEN =====================

        $dosens = Dosen::orderBy('nama')
            ->get();


        // ===================== DATA KELAS =====================

        $kelases = Kelas::with([
            'prodi.fakultas'
        ])
        ->orderBy('angkatan', 'desc')
        ->orderBy('nama_kelas')
        ->get();


        // ===================== QUERY JADWAL =====================

        $query = Jadwal::with([
            'mataKuliah.prodi.fakultas',
            'dosen',
            'ruangan',
            'kelas.prodi.fakultas'
        ]);


        // ===================== SEARCH =====================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // Cari Mata Kuliah
                $q->whereHas('mataKuliah', function ($mk) use ($search) {

                    $mk->whereLike('nama_mk', '%' . $search . '%')
                       ->orWhereLike('kode_mk', '%' . $search . '%');

                })

                // Cari Dosen
                ->orWhereHas('dosen', function ($dosen) use ($search) {

                    $dosen->whereLike('nama', '%' . $search . '%'
                    );

                })

                // Cari Kelas
                ->orWhereHas('kelas', function ($kelas) use ($search) {

                    $kelas->whereLike('nama_kelas', '%' . $search . '%'
                    );

                });

            });
        }


        // ===================== FILTER FAKULTAS =====================

        if ($request->filled('fakultas_id')) {

            $query->whereHas(
                'kelas.prodi',
                function ($q) use ($request) {

                    $q->where(
                        'fakultas_id',
                        $request->fakultas_id
                    );

                }
            );
        }


        // ===================== FILTER PRODI =====================

        if ($request->filled('prodi_id')) {

            $query->whereHas(
                'kelas',
                function ($q) use ($request) {

                    $q->where(
                        'prodi_id',
                        $request->prodi_id
                    );

                }
            );
        }


        // ===================== FILTER DOSEN =====================

        if ($request->filled('dosen_id')) {

            $query->where(
                'dosen_id',
                $request->dosen_id
            );
        }


        // ===================== FILTER KELAS =====================

        if ($request->filled('kelas_id')) {

            $query->where(
                'kelas_id',
                $request->kelas_id
            );
        }


        // ===================== FILTER HARI =====================

        if ($request->filled('hari')) {

            $query->where(
                'hari',
                $request->hari
            );
        }


        // ===================== FILTER SEMESTER =====================

        if ($request->filled('semester_akademik')) {

            $query->where(
                'semester_akademik',
                $request->semester_akademik
            );
        }


        // ===================== HASIL =====================

        $jadwals = $query
            ->orderByRaw("
                CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 0 END
            ")
            ->orderBy('jam_mulai')
            ->paginate(10)
            ->withQueryString();


        return view(
            'admin.jadwal.index',
            compact(
                'jadwals',
                'fakultas',
                'prodis',
                'dosens',
                'kelases'
            )
        );
    }


    // ===================== CREATE =====================

    public function create()
    {
        $mataKuliahs = MataKuliah::with('prodi')
            ->orderBy('nama_mk')
            ->get();

        $dosens = Dosen::orderBy('nama')
            ->get();

        $ruangans = Ruangan::orderBy('nama_ruangan')
            ->get();

        $kelases = Kelas::with('prodi')
            ->orderBy('angkatan', 'desc')
            ->orderBy('nama_kelas')
            ->get();

        return view(
            'admin.jadwal.create',
            compact(
                'mataKuliahs',
                'dosens',
                'ruangans',
                'kelases'
            )
        );
    }


    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'mata_kuliah_id'    => 'required|exists:mata_kuliahs,id',
            'dosen_id'          => 'required|exists:dosens,id',
            'ruangan_id'        => 'required|exists:ruangans,id',
            'kelas_id'          => 'required|exists:kelas,id',
            'hari'              => 'required',
            'jam_mulai'         => 'required',
            'jam_selesai'       => 'required|after:jam_mulai',
            'tahun_akademik'    => 'nullable',
            'semester_akademik' => 'nullable|integer|min:1|max:14',
        ]);


        Jadwal::create([
            'mata_kuliah_id'    => $request->mata_kuliah_id,
            'dosen_id'          => $request->dosen_id,
            'ruangan_id'        => $request->ruangan_id,
            'kelas_id'          => $request->kelas_id,
            'hari'              => $request->hari,
            'jam_mulai'         => $request->jam_mulai,
            'jam_selesai'       => $request->jam_selesai,
            'tahun_akademik'    => $request->tahun_akademik,
            'semester_akademik' => $request->semester_akademik,
        ]);


        return redirect()
            ->route('admin.jadwal')
            ->with(
                'success',
                'Data jadwal berhasil ditambahkan.'
            );
    }


    // ===================== EDIT =====================

    public function edit(Jadwal $jadwal)
    {
        $mataKuliahs = MataKuliah::with('prodi')
            ->orderBy('nama_mk')
            ->get();

        $dosens = Dosen::orderBy('nama')
            ->get();

        $ruangans = Ruangan::orderBy('nama_ruangan')
            ->get();

        $kelases = Kelas::with('prodi')
            ->orderBy('angkatan', 'desc')
            ->orderBy('nama_kelas')
            ->get();

        return view(
            'admin.jadwal.edit',
            compact(
                'jadwal',
                'mataKuliahs',
                'dosens',
                'ruangans',
                'kelases'
            )
        );
    }


    // ===================== UPDATE =====================

    public function update(
        Request $request,
        Jadwal $jadwal
    ) {
        $request->validate([
            'mata_kuliah_id'    => 'required|exists:mata_kuliahs,id',
            'dosen_id'          => 'required|exists:dosens,id',
            'ruangan_id'        => 'required|exists:ruangans,id',
            'kelas_id'          => 'required|exists:kelas,id',
            'hari'              => 'required',
            'jam_mulai'         => 'required',
            'jam_selesai'       => 'required|after:jam_mulai',
            'tahun_akademik'    => 'nullable',
            'semester_akademik' => 'nullable|integer|min:1|max:14',
        ]);


        $jadwal->update([
            'mata_kuliah_id'    => $request->mata_kuliah_id,
            'dosen_id'          => $request->dosen_id,
            'ruangan_id'        => $request->ruangan_id,
            'kelas_id'          => $request->kelas_id,
            'hari'              => $request->hari,
            'jam_mulai'         => $request->jam_mulai,
            'jam_selesai'       => $request->jam_selesai,
            'tahun_akademik'    => $request->tahun_akademik,
            'semester_akademik' => $request->semester_akademik,
        ]);


        return redirect()
            ->route('admin.jadwal')
            ->with(
                'success',
                'Data jadwal berhasil diperbarui.'
            );
    }


    // ===================== DELETE =====================

    public function destroy(Jadwal $jadwal)
    {
        $jadwal->delete();

        return redirect()
            ->route('admin.jadwal')
            ->with(
                'success',
                'Data jadwal berhasil dihapus.'
            );
    }
}