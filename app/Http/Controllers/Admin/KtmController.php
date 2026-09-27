<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\KtmService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class KtmController extends Controller
{
    public function __construct(private readonly KtmService $ktmService) {}

    public function index(Request $request)
    {
        $query = Mahasiswa::query()->with('prodi.fakultas');

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(fn ($builder) => $builder
                ->whereLike('nim', '%'.$search.'%')
                ->orWhereLike('nama', '%'.$search.'%'));
        }

        if ($request->filled('prodi_id')) {
            $query->where('prodi_id', $request->integer('prodi_id'));
        }

        if ($request->status === 'locked') {
            $query->where('ktm_photo_locked', true);
        } elseif ($request->status === 'empty') {
            $query->where('ktm_photo_locked', false);
        }

        $mahasiswas = $query->orderBy('nama')->paginate(15)->withQueryString();
        $prodis = Prodi::query()->orderBy('nama_prodi')->get();

        return view('admin.ktm.index', compact('mahasiswas', 'prodis'));
    }

    public function show(Mahasiswa $mahasiswa)
    {
        $mahasiswa->load('prodi.fakultas', 'ktmPhotoResetBy');

        return view('admin.ktm.show', compact('mahasiswa'));
    }

    public function image(Mahasiswa $mahasiswa): Response
    {
        $mahasiswa->load('prodi.fakultas');
        abort_unless(
            $mahasiswa->ktm_photo_locked
                && $mahasiswa->ktm_photo_path
                && Storage::disk(KtmService::DISK)->exists($mahasiswa->ktm_photo_path),
            404,
            'KTM belum tersedia.'
        );

        return response($this->ktmService->renderCard($mahasiswa, $mahasiswa->ktm_photo_path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function reset(Request $request, Mahasiswa $mahasiswa)
    {
        $this->ktmService->reset($mahasiswa, $request->user());

        return redirect()->route('admin.ktm.index', $request->except('_token', '_method'))
            ->with('success', 'Foto KTM '.$mahasiswa->nama.' berhasil direset. Mahasiswa dapat upload ulang.');
    }
}
