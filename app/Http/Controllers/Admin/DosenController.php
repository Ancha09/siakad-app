<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
                    'is_active' => true,
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
                    'is_active' => true,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Data dosen gagal disimpan. Silakan coba kembali.');
        }

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.dosen'))
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
        $data = $request->validate([
            'nidn' => ['required', 'string', 'max:255', Rule::unique('dosens', 'nidn')->ignore($dosen->id), Rule::unique('users', 'login')->ignore($dosen->user_id)],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($dosen->user_id)],
            'telepon' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'golongan' => ['nullable', 'string', 'max:255'],
            'prodi_id' => ['required', 'integer', 'exists:prodis,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => [Rule::requiredIf($dosen->user_id === null), 'nullable', 'string', 'min:8', 'confirmed'],
            'mahasiswa_wali' => ['nullable', 'array'],
            'mahasiswa_wali.*' => ['integer', 'exists:mahasiswas,id'],
        ]);

        DB::transaction(function () use ($data, $dosen, $request) {
            $active = array_key_exists('is_active', $data) ? $request->boolean('is_active') : $dosen->is_active;
            $userData = [
                'name' => $data['nama'], 'login' => $data['nidn'], 'email' => $data['email'] ?? null,
                'role' => 'dosen', 'is_active' => $active,
            ];
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user = $dosen->user;
            if ($user) {
                $user->update($userData);
            } else {
                $user = User::create($userData);
            }

            $dosen->update([
                ...collect($data)->except(['password', 'mahasiswa_wali', 'is_active'])->all(),
                'user_id' => $user->id,
                'is_active' => $active,
            ]);

            Mahasiswa::where('dosen_wali_id', $dosen->id)->update(['dosen_wali_id' => null]);
            if (! empty($data['mahasiswa_wali'])) {
                Mahasiswa::whereIn('id', $data['mahasiswa_wali'])->update(['dosen_wali_id' => $dosen->id]);
            }
        });

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.dosen'))
            ->with(
                'success',
                'Data dosen dan mahasiswa wali berhasil diperbarui.'
            );
    }

    // ================= DELETE =================

    public function destroy(Dosen $dosen)
    {
        DB::transaction(function () use ($dosen) {
            $dosen->forceFill(['is_active' => false, 'skripsi_aktif' => false])->save();
            $dosen->user?->update(['is_active' => false]);
        });

        return redirect()->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.dosen'))->with('success', 'Dosen dinonaktifkan tanpa menghapus jadwal atau riwayat akademik.');
    }
}
