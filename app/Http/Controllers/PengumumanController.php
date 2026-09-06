<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $pengumumans = Pengumuman::terlihat($request->user())->denganStatusBaca($request->user())
            ->orderByDesc('penting')->orderByDesc('terbit_pada')->orderByDesc('id')->paginate(12);

        return view('pengumuman.index', ['pengumumans' => $pengumumans, 'role' => $request->user()->role]);
    }

    public function read(Request $request, int $pengumuman)
    {
        $item = Pengumuman::terlihat($request->user())->findOrFail($pengumuman);
        DB::table('pengumuman_reads')->insertOrIgnore([
            'pengumuman_id' => $item->id, 'user_id' => $request->user()->id, 'read_at' => now(),
        ]);

        return back()->with('success', 'Pengumuman ditandai sudah dibaca.');
    }
}
