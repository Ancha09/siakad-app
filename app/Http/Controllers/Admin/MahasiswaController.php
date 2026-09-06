<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MahasiswaController extends Controller
{
    // ===================== INDEX =====================

   public function index(Request $request)
{
    // ===================== DATA FAKULTAS =====================

    $fakultas = \App\Models\Fakultas::orderBy('nama_fakultas')
        ->get();


    // ===================== DATA PRODI =====================

    $prodis = Prodi::with('fakultas')
        ->orderBy('nama_prodi')
        ->get();


    // ===================== DATA KELAS =====================

    $kelases = Kelas::with([
        'prodi',
        'dosenWali'
    ])
    ->orderBy('angkatan', 'desc')
    ->orderBy('nama_kelas')
    ->get();


    // ===================== QUERY MAHASISWA =====================

    $query = Mahasiswa::with([
        'prodi.fakultas',
        'kelas.dosenWali'
    ]);


    // ===================== SEARCH =====================

    if ($request->filled('search')) {

        $search = $request->search;

        $query->where(function ($q) use ($search) {

            $q->whereLike('nim', '%' . $search . '%')
              ->orWhereLike('nama', '%' . $search . '%');

        });
    }


    // ===================== FILTER FAKULTAS =====================

    if ($request->filled('fakultas_id')) {

        $query->whereHas('prodi', function ($q) use ($request) {

            $q->where('fakultas_id', $request->fakultas_id);

        });
    }


    // ===================== FILTER PRODI =====================

    if ($request->filled('prodi_id')) {

        $query->where(
            'prodi_id',
            $request->prodi_id
        );
    }


    // ===================== FILTER KELAS =====================

    if ($request->filled('kelas_id')) {

        $query->where(
            'kelas_id',
            $request->kelas_id
        );
    }


    // ===================== FILTER ANGKATAN =====================

    if ($request->filled('angkatan')) {

        $query->where(
            'angkatan',
            $request->angkatan
        );
    }


    // ===================== FILTER SEMESTER =====================

    if ($request->filled('semester')) {

        $query->where(
            'semester',
            $request->semester
        );
    }


    // ===================== HASIL =====================

    $mahasiswas = $query
        ->latest()
        ->paginate(10)
        ->withQueryString();


    // ===================== DATA ANGKATAN =====================

    $angkatans = Mahasiswa::select('angkatan')
        ->whereNotNull('angkatan')
        ->distinct()
        ->orderBy('angkatan', 'desc')
        ->pluck('angkatan');


    return view(
        'admin.mahasiswa.index',
        compact(
            'mahasiswas',
            'fakultas',
            'prodis',
            'kelases',
            'angkatans'
        )
    );
}

    // ===================== CREATE =====================

    public function create()
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        $kelases = Kelas::with([
            'prodi',
            'dosenWali'
        ])
        ->orderBy('angkatan', 'desc')
        ->orderBy('nama_kelas')
        ->get();

        return view(
            'admin.mahasiswa.create',
            compact('prodis', 'kelases')
        );
    }


    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'nim'        => 'required|unique:mahasiswas,nim',
            'nama'       => 'required',
            'email'      => 'nullable|email|unique:users,email',
            'telepon'    => 'nullable',
            'angkatan'   => 'nullable',
            'semester'   => 'nullable',
            'prodi_id'   => 'required|exists:prodis,id',

            // Kelas boleh kosong
            'kelas_id'   => 'nullable|exists:kelas,id',

            'password'   => 'required|min:8|confirmed',
        ]);


        DB::beginTransaction();

        try {

            // ================= USER =================

            $user = User::create([
                'name'      => $request->nama,
                'login'     => $request->nim,
                'email'     => $request->email,
                'role'      => 'mahasiswa',
                'password'  => Hash::make($request->password),
            ]);


            // ================= MAHASISWA =================

            Mahasiswa::create([
                'nim'        => $request->nim,
                'nama'       => $request->nama,
                'email'      => $request->email,
                'telepon'    => $request->telepon,
                'angkatan'   => $request->angkatan,
                'semester'   => $request->semester,
                'prodi_id'   => $request->prodi_id,

                // KELAS
                'kelas_id'   => $request->kelas_id,

                'user_id'    => $user->id,
            ]);


            DB::commit();


            return redirect()
                ->route('admin.mahasiswa')
                ->with(
                    'success',
                    'Data mahasiswa berhasil ditambahkan.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menambahkan mahasiswa: ' . $e->getMessage()
                );
        }
    }


    // ===================== EDIT =====================

    public function edit(Mahasiswa $mahasiswa)
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        $kelases = Kelas::with([
            'prodi',
            'dosenWali'
        ])
        ->orderBy('angkatan', 'desc')
        ->orderBy('nama_kelas')
        ->get();

        return view(
            'admin.mahasiswa.edit',
            compact(
                'mahasiswa',
                'prodis',
                'kelases'
            )
        );
    }


    // ===================== UPDATE =====================

    public function update(
        Request $request,
        Mahasiswa $mahasiswa
    ) {
        $request->validate([
            'nim'        => 'required|unique:mahasiswas,nim,' . $mahasiswa->id,
            'nama'       => 'required',
            'email'      => 'nullable|email',
            'telepon'    => 'nullable',
            'angkatan'   => 'nullable',
            'semester'   => 'nullable',
            'prodi_id'   => 'required|exists:prodis,id',

            // Kelas boleh kosong
            'kelas_id'   => 'nullable|exists:kelas,id',

            'password'   => 'nullable|min:8|confirmed',
        ]);


        // ================= MAHASISWA =================

        $mahasiswa->update([
            'nim'        => $request->nim,
            'nama'       => $request->nama,
            'email'      => $request->email,
            'telepon'    => $request->telepon,
            'angkatan'   => $request->angkatan,
            'semester'   => $request->semester,
            'prodi_id'   => $request->prodi_id,

            // KELAS
            'kelas_id'   => $request->kelas_id,
        ]);


        // ================= USER =================

        if ($mahasiswa->user) {

            $mahasiswa->user->update([
                'name'  => $request->nama,
                'login' => $request->nim,
                'email' => $request->email,
            ]);


            if ($request->filled('password')) {

                $mahasiswa->user->update([
                    'password' => Hash::make(
                        $request->password
                    ),
                ]);
            }
        }


        return redirect()
            ->route('admin.mahasiswa')
            ->with(
                'success',
                'Data mahasiswa berhasil diperbarui.'
            );
    }


    // ===================== DELETE =====================

    public function destroy(Mahasiswa $mahasiswa)
    {
        if ($mahasiswa->user) {
            $mahasiswa->user->delete();
        }

        $mahasiswa->delete();

        return redirect()
            ->route('admin.mahasiswa')
            ->with(
                'success',
                'Data mahasiswa berhasil dihapus.'
            );
    }
}