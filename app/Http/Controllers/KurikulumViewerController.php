<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Kurikulum;
use App\Models\KurikulumMataKuliah;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KurikulumViewerController extends Controller
{
    public function mahasiswa()
    {
        $pemilik = Mahasiswa::with('prodi')->where('user_id', Auth::id())->firstOrFail();

        return $this->catalog($pemilik->prodi_id, 'mahasiswa.kurikulum', 'mahasiswa.kurikulum.silabus');
    }

    public function dosen()
    {
        $pemilik = Dosen::with('prodi')->where('user_id', Auth::id())->firstOrFail();

        return $this->catalog($pemilik->prodi_id, 'dosen.kurikulum', 'dosen.kurikulum.silabus');
    }

    public function downloadMahasiswa(KurikulumMataKuliah $item)
    {
        return $this->downloadForProdi(
            $item,
            Mahasiswa::where('user_id', Auth::id())->value('prodi_id')
        );
    }

    public function downloadDosen(KurikulumMataKuliah $item)
    {
        return $this->downloadForProdi(
            $item,
            Dosen::where('user_id', Auth::id())->value('prodi_id')
        );
    }

    private function catalog(int $prodiId, string $view, string $silabusRoute)
    {
        $kurikulums = Kurikulum::with(['prodi', 'mataKuliahKurikulum.mataKuliah'])
            ->where('prodi_id', $prodiId)
            ->orderByRaw("CASE WHEN status = 'Aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('tahun_mulai')
            ->get();

        return view($view, compact('kurikulums', 'silabusRoute'));
    }

    private function downloadForProdi(KurikulumMataKuliah $item, ?int $prodiId)
    {
        $item->loadMissing(['kurikulum', 'mataKuliah']);
        abort_unless($prodiId && $item->kurikulum->prodi_id === $prodiId, 403);
        abort_unless($item->silabus_path && Storage::disk('local')->exists($item->silabus_path), 404);

        return Storage::disk('local')->download(
            $item->silabus_path,
            'Silabus-'.$item->mataKuliah->kode_mk.'.pdf'
        );
    }
}
