<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // ===================== PROFIL =====================
    public function index()
    {
        $dosen = Dosen::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('dosen.profil.index', compact('dosen'));
    }

    // ===================== UPDATE FOTO PROFIL =====================
    public function updateFoto(Request $request)
    {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();

        // Kunci: Hanya bisa upload 1 kali jika belum direset admin
        if ($dosen->foto) {
            return back()->with('error', 'Foto profil sudah tersimpan dan terkunci. Perubahan hanya dapat dilakukan melalui permohonan reset ke Administrator.');
        }

        $request->validate([
            'foto' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,svg,heic,heif,webp',
                'max:10240', // 10 MB
            ],
        ], [
            'foto.required' => 'Silakan pilih file foto terlebih dahulu.',
            'foto.mimes' => 'Format foto harus berformat JPG, JPEG, PNG, SVG, HEIC, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal adalah 10 MB.',
        ]);

        $path = $request->file('foto')->store('dosen/foto', 'public');

        $dosen->update([
            'foto' => $path,
        ]);

        return redirect()
            ->route('dosen.profil')
            ->with('success', 'Foto profil berhasil disimpan dan dikunci.');
    }

    // ===================== UPDATE PROFIL =====================
    public function update(Request $request)
    {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'nama' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:20',
        ]);

        $nama = $request->filled('nama') ? $request->nama : $dosen->nama;
        $email = $request->filled('email') ? $request->email : $dosen->email;

        // Update data dosen
        $dosen->update([
            'nama' => $nama,
            'email' => $email,
            'telepon' => $request->telepon,
        ]);

        // Sinkronkan ke tabel users
        if ($dosen->user) {
            $dosen->user->update([
                'name' => $nama,
                'email' => $email,
            ]);
        }

        return redirect()
            ->route('dosen.profil')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    // ===================== UPDATE PASSWORD =====================
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required',
            'password_baru' => 'required|min:8|confirmed',
        ]);

        // Ambil User langsung supaya Intelephense mengenali model
        $user = User::findOrFail(Auth::id());

        // Cek password lama
        if (!Hash::check($request->password_lama, $user->password)) {
            return back()
                ->withErrors([
                    'password_lama' => 'Password lama tidak sesuai.'
                ])
                ->withInput();
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password_baru),
        ]);

        return redirect()
            ->route('dosen.profil')
            ->with('success_password', 'Password berhasil diubah.');
    }
}