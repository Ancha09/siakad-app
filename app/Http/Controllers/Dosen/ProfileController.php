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

    // ===================== UPDATE PROFIL =====================
    public function update(Request $request)
    {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:20',
        ]);

        // Update data dosen
        $dosen->update([
            'nama' => $request->nama,
            'email' => $request->email,
            'telepon' => $request->telepon,
        ]);

        // Sinkronkan ke tabel users
        if ($dosen->user) {
            $dosen->user->update([
                'name' => $request->nama,
                'email' => $request->email,
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