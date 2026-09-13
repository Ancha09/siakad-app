<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanSkripsi;
use App\Models\PeriodeSkripsi;
use App\Models\Prodi;
use App\Models\RiwayatSkripsi;
use App\Models\TemplateBimbingan;
use App\Services\SkripsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SkripsiController extends Controller
{
    private function validateInput(Request $request, array $rules): array
    {
        return $request->validate($rules, [
            'required' => ':attribute wajib diisi.', 'required_if' => ':attribute wajib diisi saat menolak.',
            'exists' => ':attribute tidak valid.', 'in' => ':attribute tidak valid.',
            'max' => ':attribute terlalu panjang (maksimum :max karakter).',
            'date_format' => ':attribute harus berupa tanggal dan jam yang valid.',
            'after' => ':attribute harus sesudah waktu mulai.',
            'integer' => ':attribute tidak valid.', 'string' => ':attribute harus berupa teks.',
            'boolean' => ':attribute tidak valid.',
        ], ['periode_skripsi_id' => 'Periode', 'dosen_id' => 'Dosen', 'judul' => 'Judul', 'alasan' => 'Alasan', 'mulai' => 'Waktu mulai', 'berakhir' => 'Waktu berakhir']);
    }

    private function context(Request $request): array
    {
        $this->validateInput($request, ['periode_id' => 'nullable|integer|exists:periode_skripsis,id']);
        $periods = PeriodeSkripsi::orderByDesc('mulai')->get();
        $period = $request->filled('periode_id') ? $periods->firstWhere('id', (int) $request->periode_id)
            : ($periods->first(fn ($p) => $p->terbuka()) ?? $periods->first());

        return ['periods' => $periods, 'period' => $period, 'role' => $request->user()->role];
    }

    public function index(Request $request)
    {
        $context = $this->context($request);
        $periodId = $context['period']?->id ?? 0;
        $query = PengajuanSkripsi::with(['mahasiswa.prodi', 'dosen', 'pembuat'])->where('periode_skripsi_id', $periodId);
        if ($request->user()->role === 'mahasiswa') {
            $student = Mahasiswa::where('user_id', $request->user()->id)->firstOrFail();
            $query->where('mahasiswa_id', $student->id);
            $last = (clone $query)->latest('id')->first();
            $dosens = Dosen::pembimbingAktif()->when($last, fn ($q) => $q->where('id', '!=', $last->dosen_id))->orderBy('nama')->get();

            return view('skripsi.mahasiswa', $context + [
                'student' => $student, 'last' => $last, 'dosens' => $dosens,
                'templateBimbingan' => TemplateBimbingan::aktif(),
                'submissions' => $query->latest('id')->paginate(10)->withQueryString(),
            ]);
        }
        $dosen = Dosen::where('user_id', $request->user()->id)->firstOrFail();
        $this->validateInput($request, ['status' => 'nullable|in:Menunggu,Diterima,Ditolak,Dialihkan']);
        $query->where('dosen_id', $dosen->id);

        return view('skripsi.dosen', $context + [
            'accepted' => (clone $query)->diterima()->orderBy('id')->paginate(10, ['*'], 'bimbingan_page')->withQueryString(),
            'submissions' => $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest('id')->paginate(10)->withQueryString(),
        ]);
    }

    public function admin(Request $request)
    {
        $context = $this->context($request);
        $periodId = $context['period']?->id ?? 0;
        $this->validateInput($request, [
            'q' => 'nullable|string|max:200', 'prodi_id' => 'nullable|integer|exists:prodis,id',
            'dosen_id' => 'nullable|integer|exists:dosens,id',
            'status' => 'nullable|in:Belum mengajukan,Belum mendapat pembimbing,Menunggu,Diterima,Ditolak,Dialihkan',
        ]);
        $current = fn ($q) => $q->where('periode_skripsi_id', $periodId)
            ->whereRaw('pengajuan_skripsis.id = (select max(ps.id) from pengajuan_skripsis ps where ps.mahasiswa_id = pengajuan_skripsis.mahasiswa_id and ps.periode_skripsi_id = pengajuan_skripsis.periode_skripsi_id)');
        $students = Mahasiswa::with(['prodi', 'pengajuanSkripsi' => fn ($q) => $q->where('periode_skripsi_id', $periodId)->with('dosen')->latest('id')])
            ->where('semester', '>=', Mahasiswa::MIN_SEMESTER_SKRIPSI)
            ->when($request->filled('prodi_id'), fn ($q) => $q->where('prodi_id', $request->prodi_id))
            ->when($request->filled('q'), function ($q) use ($request, $periodId) {
                $q->where(fn ($q) => $q->whereLike('nama', '%'.$request->q.'%')->orWhereLike('nim', '%'.$request->q.'%')
                    ->orWhereHas('pengajuanSkripsi', fn ($q) => $q->where('periode_skripsi_id', $periodId)->whereLike('judul', '%'.$request->q.'%')));
            })
            ->when($request->filled('dosen_id'), fn ($q) => $q->whereHas('pengajuanSkripsi', function ($q) use ($current, $request) {
                $current($q)->where('dosen_id', $request->dosen_id);
            }));
        $summary = ['Belum mengajukan' => (clone $students)->whereDoesntHave('pengajuanSkripsi', fn ($q) => $q->where('periode_skripsi_id', $periodId))->count()];
        foreach (['Menunggu', 'Ditolak', 'Diterima'] as $status) {
            $summary[$status] = (clone $students)->whereHas('pengajuanSkripsi', fn ($q) => $current($q)->where('status', $status))->count();
        }
        $summary['Belum mendapat pembimbing'] = (clone $students)->whereDoesntHave('pengajuanSkripsi', fn ($q) => $q->where('periode_skripsi_id', $periodId)->diterima())->count();
        if ($request->status === 'Belum mengajukan') {
            $students->whereDoesntHave('pengajuanSkripsi', fn ($q) => $q->where('periode_skripsi_id', $periodId));
        } elseif ($request->status === 'Belum mendapat pembimbing') {
            $students->whereDoesntHave('pengajuanSkripsi', fn ($q) => $q->where('periode_skripsi_id', $periodId)->diterima());
        } elseif ($request->filled('status')) {
            $students->whereHas('pengajuanSkripsi', fn ($q) => $current($q)->where('status', $request->status));
        }

        // Separate submission history keeps previous destinations/statuses searchable.
        $submissions = PengajuanSkripsi::with(['mahasiswa.prodi', 'dosen', 'pembuat'])->where('periode_skripsi_id', $periodId)
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->whereLike('judul', '%'.$request->q.'%')->orWhereHas('mahasiswa', fn ($q) => $q->whereLike('nama', '%'.$request->q.'%')->orWhereLike('nim', '%'.$request->q.'%'))))
            ->when($request->filled('prodi_id'), fn ($q) => $q->whereHas('mahasiswa', fn ($q) => $q->where('prodi_id', $request->prodi_id)))
            ->when($request->filled('dosen_id'), fn ($q) => $q->where('dosen_id', $request->dosen_id))
            ->when(in_array($request->status, PengajuanSkripsi::STATUS, true), fn ($q) => $q->where('status', $request->status));
        if (in_array($request->status, ['Belum mengajukan', 'Belum mendapat pembimbing'], true)) {
            $submissions->whereIn('mahasiswa_id', (clone $students)->select('mahasiswas.id'));
        }

        return view('skripsi.admin', $context + [
            'summary' => $summary, 'students' => $students->orderBy('nama')->paginate(15, ['*'], 'mahasiswa_page')->withQueryString(),
            'submissions' => $submissions->latest('id')->paginate(15, ['*'], 'pengajuan_page')->withQueryString(),
            'dosens' => Dosen::pembimbingAktif()->orderBy('nama')->get(), 'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'allDosens' => Dosen::orderBy('nama')->get(),
            'templateBimbingan' => TemplateBimbingan::aktif(),
            'loads' => Dosen::with('prodi')->withCount([
                'pengajuanSkripsi as diterima_count' => fn ($q) => $q->where('periode_skripsi_id', $periodId)->diterima(),
                'pengajuanSkripsi as menunggu_count' => fn ($q) => $q->where('periode_skripsi_id', $periodId)->where('status', 'Menunggu'),
            ])->orderBy('nama')->paginate(15, ['*'], 'dosen_page')->withQueryString(),
            'audits' => RiwayatSkripsi::with('pelaku')->whereNull('pengajuan_skripsi_id')->where(fn ($q) => $q->where('periode_skripsi_id', $periodId)->orWhereNull('periode_skripsi_id'))->latest('id')->paginate(10, ['*'], 'riwayat_page')->withQueryString(),
        ]);
    }

    public function show(Request $request, PengajuanSkripsi $pengajuan)
    {
        Gate::authorize('view', $pengajuan);
        $pengajuan->load(['mahasiswa.prodi', 'dosen', 'periode', 'pembuat']);

        return view('skripsi.show', [
            'role' => $request->user()->role, 'submission' => $pengajuan,
            'audits' => $pengajuan->riwayat()->with('pelaku')->orderBy('id')->paginate(15),
        ]);
    }

    public function store(Request $request, SkripsiService $service)
    {
        $data = $this->validateInput($request, ['periode_skripsi_id' => 'required|integer|exists:periode_skripsis,id', 'judul' => 'required|string|max:1000', 'dosen_id' => 'required|integer|exists:dosens,id']);
        $service->submit($request->user(), $data);

        return back()->with('success', 'Pengajuan berhasil dikirim. Menunggu persetujuan dosen.');
    }

    public function updateTitle(Request $request, PengajuanSkripsi $pengajuan, SkripsiService $service)
    {
        Gate::authorize('view', $pengajuan);
        $data = $this->validateInput($request, ['judul' => 'required|string|max:1000']);
        $service->updateTitle($request->user(), $pengajuan, $data['judul']);

        return back()->with('success', 'Judul skripsi berhasil diperbarui dan dicatat dalam riwayat.');
    }

    public function uploadTemplate(Request $request, SkripsiService $service)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'template' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx',
        ], [
            'template.required' => 'Pilih berkas template kartu bimbingan.',
            'template.mimes' => 'Template harus berformat PDF, DOC, DOCX, XLS, atau XLSX.',
            'template.max' => 'Ukuran template maksimal 10 MB.',
        ]);
        $file = $data['template'];
        $previous = TemplateBimbingan::aktif();
        $path = $file->store('template-bimbingan', 'local');
        abort_if($path === false, 500, 'Template gagal disimpan.');

        $template = TemplateBimbingan::create([
            'nama_asli' => basename($file->getClientOriginalName()),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'ukuran' => $file->getSize(),
            'diunggah_oleh' => $request->user()->id,
        ]);
        $service->audit($request->user(), 'Unggah template kartu bimbingan', [
            'template_id' => $template->id, 'nama' => $template->nama_asli, 'ukuran' => $template->ukuran,
        ]);
        if ($previous) {
            Storage::disk('local')->delete($previous->path);
            $previous->delete();
        }

        return back()->with('success', 'Template kartu bimbingan berhasil diperbarui.');
    }

    public function downloadTemplate(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'mahasiswa'], true), 403);
        $template = TemplateBimbingan::aktif();
        abort_unless($template && Storage::disk('local')->exists($template->path), 404, 'Template kartu bimbingan belum tersedia.');

        return Storage::disk('local')->download($template->path, $template->nama_asli, [
            'Content-Type' => $template->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function decide(Request $request, PengajuanSkripsi $pengajuan, SkripsiService $service)
    {
        Gate::authorize('decide', $pengajuan);
        $data = $this->validateInput($request, ['status' => 'required|in:Diterima,Ditolak', 'alasan' => 'required_if:status,Ditolak|nullable|string|max:2000']);
        $service->decide($request->user(), $pengajuan, $data);

        return back()->with('success', 'Keputusan berhasil disimpan.');
    }

    public function transfer(Request $request, PengajuanSkripsi $pengajuan, SkripsiService $service)
    {
        $data = $this->validateInput($request, ['dosen_id' => 'required|integer|exists:dosens,id', 'alasan' => 'required|string|max:2000']);
        $service->transfer($request->user(), $pengajuan, $data);

        return back()->with('success', 'Pengajuan dialihkan dan menunggu persetujuan dosen baru.');
    }

    public function period(Request $request, SkripsiService $service, ?PeriodeSkripsi $periode = null)
    {
        $data = $this->validateInput($request, ['nama' => 'required|string|max:255', 'mulai' => 'required|date_format:Y-m-d\TH:i', 'berakhir' => 'required|date_format:Y-m-d\TH:i|after:mulai']);
        $period = $service->savePeriod($request->user(), $data, $periode);

        return redirect()->route('admin.skripsi', ['periode_id' => $period->id])->with('success', 'Periode disimpan dan perubahan dicatat dalam riwayat.');
    }

    public function dosen(Request $request, Dosen $dosen, SkripsiService $service)
    {
        $this->validateInput($request, ['aktif' => 'required|boolean']);
        $service->setDosen($request->user(), $dosen, $request->boolean('aktif'));

        return back()->with('success', 'Kelayakan pembimbing diperbarui.');
    }
}
