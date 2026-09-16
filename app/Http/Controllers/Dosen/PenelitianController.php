<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Penelitian;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PenelitianController extends Controller
{
    public function index(Request $request)
    {
        $dosen = $this->currentDosen();
        $baseQuery = Penelitian::where('dosen_id', $dosen->id);

        $tahunTersedia = (clone $baseQuery)->select('tahun')->distinct()->orderByDesc('tahun')->pluck('tahun');
        $statistik = [
            'total' => (clone $baseQuery)->count(),
            'penelitian' => (clone $baseQuery)->where('jenis', 'Penelitian')->count(),
            'pengabdian' => (clone $baseQuery)->where('jenis', 'Pengabdian')->count(),
            'selesai' => (clone $baseQuery)->whereIn('status', ['Selesai', 'Terbit'])->count(),
        ];

        $penelitians = $baseQuery
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->input('search');
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereLike('judul', '%'.$search.'%')
                        ->orWhereLike('ringkasan', '%'.$search.'%')
                        ->orWhereLike('sumber_dana', '%'.$search.'%');
                });
            })
            ->when($request->filled('jenis'), fn ($query) => $query->where('jenis', $request->input('jenis')))
            ->when($request->filled('tahun'), fn ($query) => $query->where('tahun', $request->integer('tahun')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('tahun')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('dosen.penelitian', compact('dosen', 'penelitians', 'tahunTersedia', 'statistik'));
    }

    public function store(Request $request)
    {
        $dosen = $this->currentDosen();
        $data = $this->validated($request);
        $data['dosen_id'] = $dosen->id;
        $data['hasil_path'] = $this->storeDocument($request, 'hasil', $dosen);
        $data['artikel_path'] = $this->storeDocument($request, 'artikel', $dosen);
        unset($data['hasil'], $data['artikel']);

        Penelitian::create($data);

        return redirect()->to(app(LegacyListNavigation::class)->returnUrl(request(), 'dosen.penelitian'))->with('success', 'Karya Penelitian & P3M berhasil ditambahkan.');
    }

    public function update(Request $request, Penelitian $penelitian)
    {
        $dosen = $this->currentDosen();
        $this->ensureOwner($penelitian, $dosen);
        $data = $this->validated($request);

        foreach (['hasil' => 'hasil_path', 'artikel' => 'artikel_path'] as $input => $column) {
            if (!$request->hasFile($input)) {
                continue;
            }

            $oldPath = $penelitian->{$column};
            $data[$column] = $this->storeDocument($request, $input, $dosen);
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        }
        unset($data['hasil'], $data['artikel']);

        $penelitian->update($data);

        return redirect()->to(app(LegacyListNavigation::class)->returnUrl(request(), 'dosen.penelitian'))->with('success', 'Karya berhasil diperbarui.');
    }

    public function destroy(Penelitian $penelitian)
    {
        $dosen = $this->currentDosen();
        $this->ensureOwner($penelitian, $dosen);
        $paths = array_filter([$penelitian->hasil_path, $penelitian->artikel_path]);
        $penelitian->delete();
        Storage::disk('local')->delete($paths);

        return redirect()->to(app(LegacyListNavigation::class)->returnUrl(request(), 'dosen.penelitian'))->with('success', 'Karya berhasil dihapus.');
    }

    public function download(Penelitian $penelitian, string $dokumen)
    {
        $dosen = $this->currentDosen();
        $this->ensureOwner($penelitian, $dosen);
        abort_unless(in_array($dokumen, ['hasil', 'artikel'], true), 404);

        $path = $dokumen === 'hasil' ? $penelitian->hasil_path : $penelitian->artikel_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = ucfirst($dokumen).'-'.Str::slug($penelitian->judul).'.'.$extension;

        return Storage::disk('local')->download($path, $filename);
    }

    private function currentDosen(): Dosen
    {
        return Dosen::where('user_id', Auth::id())->firstOrFail();
    }

    private function ensureOwner(Penelitian $penelitian, Dosen $dosen): void
    {
        abort_unless($penelitian->dosen_id === $dosen->id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(['Penelitian', 'Pengabdian'])],
            'tahun' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'sumber_dana' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Draft', 'Berjalan', 'Selesai', 'Terbit'])],
            'ringkasan' => ['nullable', 'string', 'max:5000'],
            'link_artikel' => ['nullable', 'url:http,https', 'max:2048'],
            'hasil' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'artikel' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ], [
            'link_artikel.url' => 'Link artikel harus berupa alamat http atau https yang valid.',
            'hasil.mimes' => 'Dokumen hasil harus berupa PDF, DOC, atau DOCX.',
            'artikel.mimes' => 'Dokumen artikel harus berupa PDF, DOC, atau DOCX.',
            'hasil.max' => 'Ukuran dokumen hasil maksimal 10 MB.',
            'artikel.max' => 'Ukuran dokumen artikel maksimal 10 MB.',
        ]);
    }

    private function storeDocument(Request $request, string $input, Dosen $dosen): ?string
    {
        if (!$request->hasFile($input)) {
            return null;
        }

        $path = $request->file($input)->store('penelitian/'.$dosen->id, 'local');
        abort_unless($path, 500, 'Dokumen gagal disimpan.');

        return $path;
    }
}
