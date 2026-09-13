<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DosenController extends Controller
{
    // ================= INDEX =================

    public function index(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $search = trim($data['q'] ?? '');
        $dosens = Dosen::with([
            'prodi',
        ])
        ->withCount('mahasiswaWali')
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->whereLike('nidn', '%'.$search.'%')
                    ->orWhereLike('nama', '%'.$search.'%')
                    ->orWhereLike('email', '%'.$search.'%')
                    ->orWhereLike('jabatan', '%'.$search.'%')
                    ->orWhereHas('prodi', fn ($query) => $query->whereLike('nama_prodi', '%'.$search.'%'));
            });
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('admin.dosen.index', compact('dosens'));
    }


    // ================= CREATE =================

    public function create()
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        return view('admin.dosen.create', compact('prodis'));
    }


    // ================= STORE =================

    public function store(Request $request)
    {
        $request->validate([
            'nidn'      => 'required|unique:dosens,nidn',
            'nama'      => 'required',
            'email'     => 'nullable|email|unique:users,email',
            'telepon'   => 'nullable',
            'jabatan'   => 'nullable',
            'golongan'  => 'nullable',
            'prodi_id'  => 'required|exists:prodis,id',
            'password'  => 'required|min:8|confirmed',
        ]);

        // Membuat akun login dosen
        $user = User::create([
            'name'      => $request->nama,
            'login'     => $request->nidn,
            'email'     => $request->email,
            'role'      => 'dosen',
            'password'  => Hash::make($request->password),
        ]);

        // Menyimpan data dosen
        Dosen::create([
            'nidn'      => $request->nidn,
            'nama'      => $request->nama,
            'email'     => $request->email,
            'telepon'   => $request->telepon,
            'jabatan'   => $request->jabatan,
            'golongan'  => $request->golongan,
            'prodi_id'  => $request->prodi_id,
            'user_id'   => $user->id,
        ]);

        return redirect()
            ->route('admin.dosen')
            ->with('success', 'Data dosen berhasil ditambahkan.');
    }


    // ================= EDIT =================

    public function edit(Dosen $dosen)
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        // Semua mahasiswa untuk pengaturan Dosen Wali
        $mahasiswas = Mahasiswa::with('prodi')
            ->orderBy('nama')
            ->get();

        return view('admin.dosen.edit', compact(
            'dosen',
            'prodis',
            'mahasiswas'
        ));
    }


    // ================= UPDATE =================

    public function update(Request $request, Dosen $dosen)
    {
        $request->validate([
            'nidn'      => 'required|unique:dosens,nidn,' . $dosen->id,
            'nama'      => 'required',
            'email'     => 'nullable|email',
            'telepon'   => 'nullable',
            'jabatan'   => 'nullable',
            'golongan'  => 'nullable',
            'prodi_id'  => 'required|exists:prodis,id',
            'password'  => 'nullable|min:8|confirmed',
            'mahasiswa_wali' => 'nullable|array',
            'mahasiswa_wali.*' => 'exists:mahasiswas,id',
        ]);


        // Update data dosen
        $dosen->update([
            'nidn'      => $request->nidn,
            'nama'      => $request->nama,
            'email'     => $request->email,
            'telepon'   => $request->telepon,
            'jabatan'   => $request->jabatan,
            'golongan'  => $request->golongan,
            'prodi_id'  => $request->prodi_id,
        ]);


        // Update akun login dosen
        if ($dosen->user) {

            $dosen->user->name  = $request->nama;
            $dosen->user->login = $request->nidn;
            $dosen->user->email = $request->email;

            if ($request->filled('password')) {
                $dosen->user->password = Hash::make(
                    $request->password
                );
            }

            $dosen->user->save();
        }


        /*
        |--------------------------------------------------------------------------
        | ATUR DOSEN WALI
        |--------------------------------------------------------------------------
        |
        | Mahasiswa yang dicentang akan mendapatkan dosen ini
        | sebagai dosen wali.
        |
        | Mahasiswa yang sebelumnya menjadi wali dosen ini tetapi
        | tidak dicentang lagi akan dilepas dari dosen tersebut.
        |
        */

        // Lepaskan mahasiswa yang sebelumnya menjadi wali dosen ini
        Mahasiswa::where('dosen_wali_id', $dosen->id)
            ->update([
                'dosen_wali_id' => null
            ]);


        // Ambil mahasiswa yang dipilih
        $mahasiswaWali = $request->input(
            'mahasiswa_wali',
            []
        );


        // Tetapkan dosen sebagai wali
        if (!empty($mahasiswaWali)) {

            Mahasiswa::whereIn(
                'id',
                $mahasiswaWali
            )
            ->update([
                'dosen_wali_id' => $dosen->id
            ]);
        }


        return redirect()
            ->route('admin.dosen')
            ->with(
                'success',
                'Data dosen dan mahasiswa wali berhasil diperbarui.'
            );
    }


    // ================= DELETE =================

    public function destroy(Dosen $dosen)
    {
        // Lepaskan mahasiswa wali terlebih dahulu
        Mahasiswa::where(
            'dosen_wali_id',
            $dosen->id
        )->update([
            'dosen_wali_id' => null
        ]);


        // Hapus akun user
        if ($dosen->user) {
            $dosen->user->delete();
        }


        // Hapus data dosen
        $dosen->delete();


        return redirect()
            ->route('admin.dosen')
            ->with(
                'success',
                'Data dosen berhasil dihapus.'
            );
    }
}
