<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\KtmService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class KtmController extends Controller
{
    public function __construct(private readonly KtmService $ktmService) {}

    public function index(Request $request)
    {
        $mahasiswa = $this->student($request)->load('prodi.fakultas');
        $temporaryPath = $mahasiswa->ktm_photo_locked ? null : $this->ktmService->temporaryPath($mahasiswa);

        return view('mahasiswa.ktm.index', compact('mahasiswa', 'temporaryPath'));
    }

    public function upload(Request $request)
    {
        $mahasiswa = $this->student($request);

        if ($mahasiswa->ktm_photo_locked) {
            return back()->with('error', 'Foto KTM sudah dikirim dan tidak dapat diganti. Hubungi admin jika terdapat kesalahan.');
        }

        $validated = $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
            ],
        ], [
            'photo.required' => 'Foto KTM wajib dipilih.',
            'photo.image' => 'File wajib berupa gambar yang valid.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photo.mimetypes' => 'Tipe file foto tidak diizinkan.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        try {
            $this->ktmService->storeTemporary($mahasiswa, $validated['photo']);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('mahasiswa.ktm')->with('success', 'Foto berhasil diproses. Periksa preview KTM sebelum konfirmasi final.');
    }

    public function previewImage(Request $request): Response
    {
        $mahasiswa = $this->student($request)->load('prodi.fakultas');
        $path = $this->ktmService->temporaryPath($mahasiswa);
        abort_unless($path, 404, 'Foto preview tidak ditemukan.');

        return $this->imageResponse($this->ktmService->renderCard($mahasiswa, $path));
    }

    public function cancel(Request $request)
    {
        $mahasiswa = $this->student($request);
        abort_if($mahasiswa->ktm_photo_locked, 403, 'Foto KTM sudah dikunci.');

        $this->ktmService->cancelTemporary($mahasiswa);

        return redirect()->route('mahasiswa.ktm')->with('success', 'Foto preview dibatalkan. Anda dapat memilih foto lain.');
    }

    public function finalize(Request $request)
    {
        $request->validate([
            'confirm_final' => ['accepted'],
        ], [
            'confirm_final.accepted' => 'Anda harus menyetujui konfirmasi final KTM.',
        ]);

        try {
            $this->ktmService->finalize($this->student($request));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('mahasiswa.ktm')->with('success', 'Foto KTM berhasil dikirim dan sekarang terkunci.');
    }

    public function image(Request $request): Response
    {
        $mahasiswa = $this->finalStudent($request);

        return $this->imageResponse($this->ktmService->renderCard($mahasiswa, $mahasiswa->ktm_photo_path));
    }

    public function png(Request $request): Response
    {
        $mahasiswa = $this->finalStudent($request);
        $filename = 'KTM-'.$this->safeNim($mahasiswa).'.png';

        return $this->imageResponse($this->ktmService->renderCard($mahasiswa, $mahasiswa->ktm_photo_path), $filename);
    }

    public function pdf(Request $request)
    {
        $mahasiswa = $this->finalStudent($request);
        $imageData = 'data:image/png;base64,'.base64_encode(
            $this->ktmService->renderCard($mahasiswa, $mahasiswa->ktm_photo_path)
        );

        return Pdf::loadView('mahasiswa.ktm.pdf', compact('imageData'))
            ->setPaper([0, 0, 243, 153])
            ->download('KTM-'.$this->safeNim($mahasiswa).'.pdf');
    }

    private function student(Request $request): Mahasiswa
    {
        $mahasiswa = $request->user()?->mahasiswa()->first();
        abort_unless($mahasiswa, 403, 'Data mahasiswa tidak ditemukan.');

        return $mahasiswa;
    }

    private function finalStudent(Request $request): Mahasiswa
    {
        $mahasiswa = $this->student($request)->load('prodi.fakultas');
        abort_unless(
            $mahasiswa->ktm_photo_locked
                && $mahasiswa->ktm_photo_path
                && Storage::disk(KtmService::DISK)->exists($mahasiswa->ktm_photo_path),
            404,
            'KTM final belum tersedia.'
        );

        return $mahasiswa;
    }

    private function imageResponse(string $bytes, ?string $downloadName = null): Response
    {
        $response = response($bytes, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        if ($downloadName) {
            $response->header('Content-Disposition', 'attachment; filename="'.$downloadName.'"');
        }

        return $response;
    }

    private function safeNim(Mahasiswa $mahasiswa): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $mahasiswa->nim) ?: (string) $mahasiswa->getKey();
    }
}
