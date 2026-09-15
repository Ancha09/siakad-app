<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

class MahasiswaController extends Controller
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
            'prodi',
            'dosenWali',
        ])
            ->orderBy('angkatan', 'desc')
            ->orderBy('nama_kelas')
            ->get();

        // ===================== QUERY MAHASISWA =====================

        $query = Mahasiswa::with([
            'prodi.fakultas',
            'kelas.dosenWali',
        ]);

        // ===================== SEARCH =====================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->whereLike('nim', '%'.$search.'%')
                    ->orWhereLike('nama', '%'.$search.'%');

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
            'dosenWali',
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
        $data = $request->validate([
            'nim' => ['required', 'string', 'max:255', 'unique:mahasiswas,nim', 'unique:users,login'],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'telepon' => ['nullable', 'string', 'max:255'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'prodi_id' => ['required', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        try {
            DB::transaction(function () use ($data) {
                $user = User::create([
                    'name' => $data['nama'],
                    'login' => $data['nim'],
                    'email' => $data['email'] ?? null,
                    'role' => 'mahasiswa',
                    'is_active' => true,
                    'password' => Hash::make($data['password']),
                ]);

                Mahasiswa::create([
                    ...collect($data)->except(['password', 'password_confirmation'])->all(),
                    'user_id' => $user->id,
                    'is_active' => true,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Data mahasiswa gagal disimpan. Silakan coba kembali.');
        }

        return redirect()->route('admin.mahasiswa')->with('success', 'Data mahasiswa dan akun login berhasil ditambahkan.');
    }

    // ===================== EDIT =====================

    public function edit(Mahasiswa $mahasiswa)
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        $kelases = Kelas::with([
            'prodi',
            'dosenWali',
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
        $data = $request->validate([
            'nim' => ['required', 'string', 'max:255', Rule::unique('mahasiswas', 'nim')->ignore($mahasiswa->id), Rule::unique('users', 'login')->ignore($mahasiswa->user_id)],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($mahasiswa->user_id)],
            'telepon' => ['nullable', 'string', 'max:255'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'prodi_id' => ['required', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => [Rule::requiredIf($mahasiswa->user_id === null), 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($data, $mahasiswa, $request) {
            $active = array_key_exists('is_active', $data) ? $request->boolean('is_active') : $mahasiswa->is_active;
            $userData = [
                'name' => $data['nama'], 'login' => $data['nim'], 'email' => $data['email'] ?? null,
                'role' => 'mahasiswa', 'is_active' => $active,
            ];
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user = $mahasiswa->user;
            if ($user) {
                $user->update($userData);
            } else {
                $user = User::create($userData);
            }

            $mahasiswa->update([
                ...collect($data)->except(['password', 'is_active'])->all(),
                'user_id' => $user->id,
                'is_active' => $active,
            ]);
        });

        return redirect()->route('admin.mahasiswa')->with('success', 'Data mahasiswa dan akun login berhasil diperbarui.');
    }

    // ===================== DELETE =====================

    public function destroy(Mahasiswa $mahasiswa)
    {
        DB::transaction(function () use ($mahasiswa) {
            $mahasiswa->update(['is_active' => false]);
            $mahasiswa->user?->update(['is_active' => false]);
        });

        return redirect()->route('admin.mahasiswa')->with('success', 'Mahasiswa dinonaktifkan tanpa menghapus riwayat akademik.');
    }
}
