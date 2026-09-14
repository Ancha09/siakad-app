<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Prodi;
use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class DosenController extends Controller
{
    // ================= INDEX =================

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'fakultas_id' => ['nullable', 'integer', 'exists:fakultas,id'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
        ]);

        $search = trim($data['q'] ?? '');
        $fakultasId = $data['fakultas_id'] ?? null;
        $prodiId = $data['prodi_id'] ?? null;

        $fakultas = Fakultas::orderBy('nama_fakultas')->get();
        $prodis = Prodi::with('fakultas')->orderBy('nama_prodi')->get();

        $dosens = Dosen::with([
            'prodi.fakultas',
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
        ->when($fakultasId, function ($query) use ($fakultasId) {
            $query->whereHas('prodi', fn ($query) => $query->where('fakultas_id', $fakultasId));
        })
        ->when($prodiId, fn ($query) => $query->where('prodi_id', $prodiId))
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('admin.dosen.index', compact('dosens', 'fakultas', 'prodis'));
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
        $data = $request->validate([
            'nidn' => ['required', 'string', 'max:255', 'unique:dosens,nidn', 'unique:users,login'],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telepon' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'golongan' => ['nullable', 'string', 'max:255'],
            'prodi_id' => ['required', 'integer', 'exists:prodis,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nidn.unique' => 'NIDN sudah digunakan sebagai NIDN atau login akun lain.',
        ]);

        try {
            DB::transaction(function () use ($data) {
                // Membuat akun login dosen
                $user = User::create([
                    'name' => $data['nama'],
                    'login' => $data['nidn'],
                    'email' => $data['email'] ?? null,
                    'role' => 'dosen',
                    'password' => Hash::make($data['password']),
                ]);

                // Menyimpan data dosen
                Dosen::create([
                    'nidn' => $data['nidn'],
                    'nama' => $data['nama'],
                    'email' => $data['email'] ?? null,
                    'telepon' => $data['telepon'] ?? null,
                    'jabatan' => $data['jabatan'] ?? null,
                    'golongan' => $data['golongan'] ?? null,
                    'prodi_id' => $data['prodi_id'],
                    'user_id' => $user->id,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Data dosen gagal disimpan. Silakan coba kembali.');
        }

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
