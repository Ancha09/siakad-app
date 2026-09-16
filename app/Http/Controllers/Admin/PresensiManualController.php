<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Presensi;
use App\Models\Prodi;
use App\Services\LegacyAcademicService;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PresensiManualController extends Controller
{
    public function index(Request $request, LegacyAcademicService $legacy)
    {
        $query = Presensi::with(['dosenManual', 'krs.mahasiswa', 'krs.mataKuliahManual', 'krs.dosenManual', 'krs.jadwal.mataKuliah', 'krs.jadwal.dosen'])
            ->where('is_manual', true);
        $presensis = $legacy->filterRecords($query, $request, true)->orderByDesc('tanggal')->orderByDesc('id')->paginate(10)->appends($request->query());
        if ($presensis->currentPage() > $presensis->lastPage()) {
            return redirect()->route('admin.presensi-manual.index', array_replace($request->query(), ['page' => $presensis->lastPage()]));
        }

        return view('admin.presensi-manual.index', compact('presensis') + $legacy->filterOptions());
    }

    public function create(Request $request, LegacyListNavigation $navigation)
    {
        $returnUrl = $navigation->returnUrl($request, 'admin.presensi-manual.index');

        return view('admin.presensi-manual.form', $this->formData() + compact('returnUrl'));
    }

    public function store(Request $request, LegacyAcademicService $legacy, LegacyListNavigation $navigation)
    {
        $data = $this->validated($request);
        $legacy->validateSchedule($data);

        DB::transaction(function () use ($data, $legacy) {
            Mahasiswa::whereKey($data['mahasiswa_id'])->lockForUpdate()->firstOrFail();
            $this->ensureNotDuplicate($data, $legacy);
            $krs = $legacy->resolveKrs($data);
            Presensi::create([
                'krs_id' => $krs->id, 'tanggal' => $data['tanggal'],
                'pertemuan' => $data['pertemuan'] ?? null, 'status' => $data['status'],
                'keterangan' => $data['keterangan'] ?? null, 'is_manual' => true,
                'manual_identity' => $legacy->attendanceIdentity($data),
                'dosen_id' => $data['dosen_id'] ?? null,
                'dosen_override' => true,
            ]);
        });

        return redirect()->to($navigation->returnUrl($request, 'admin.presensi-manual.index'))->with('success', 'Absensi lama/manual berhasil disimpan.');
    }

    public function edit(Request $request, Presensi $presensi, LegacyListNavigation $navigation)
    {
        abort_unless($presensi->is_manual, 404);
        $presensi->load(['krs.mahasiswa', 'krs.mataKuliahManual', 'krs.jadwal']);

        $returnUrl = $navigation->returnUrl($request, 'admin.presensi-manual.index');

        return view('admin.presensi-manual.form', $this->formData() + compact('presensi', 'returnUrl'));
    }

    public function update(Request $request, Presensi $presensi, LegacyAcademicService $legacy, LegacyListNavigation $navigation)
    {
        abort_unless($presensi->is_manual, 404);
        $data = $this->validated($request);
        $legacy->validateSchedule($data);

        DB::transaction(function () use ($data, $legacy, $presensi) {
            Mahasiswa::whereKey($data['mahasiswa_id'])->lockForUpdate()->firstOrFail();
            $this->ensureNotDuplicate($data, $legacy, $presensi->id);
            $krs = $legacy->resolveKrs($data, overwriteMetadata: true, currentKrsId: $presensi->krs_id);
            $presensi->update([
                'dosen_id' => $data['dosen_id'] ?? null,
                'dosen_override' => true,
                'krs_id' => $krs->id, 'tanggal' => $data['tanggal'],
                'pertemuan' => $data['pertemuan'] ?? null, 'status' => $data['status'],
                'keterangan' => $data['keterangan'] ?? null,
                'manual_identity' => $legacy->attendanceIdentity($data),
            ]);
        });

        return redirect()->to($navigation->returnUrl($request, 'admin.presensi-manual.index'))->with('success', 'Absensi lama/manual berhasil diperbarui.');
    }

    public function destroy(Request $request, Presensi $presensi, LegacyListNavigation $navigation)
    {
        abort_unless($presensi->is_manual, 404);
        DB::transaction(function () use ($presensi) {
            Mahasiswa::whereKey($presensi->krs->mahasiswa_id)->lockForUpdate()->firstOrFail();
            $presensi->delete();
        });

        return redirect()->to($navigation->returnUrl($request, 'admin.presensi-manual.index'))->with('success', 'Entri absensi manual dihapus. KRS dan nilai tidak dihapus.');
    }

    private function ensureNotDuplicate(array $data, LegacyAcademicService $legacy, ?int $except = null): void
    {
        $query = Presensi::whereIn('krs_id', $legacy->matchingKrs($data)->select('id'))
            ->when($except, fn ($query) => $query->where('id', '!=', $except))
            ->where(function ($query) use ($data) {
                $query->whereDate('tanggal', $data['tanggal']);
                if (! empty($data['pertemuan'])) {
                    $query->orWhere('pertemuan', $data['pertemuan']);
                }
            });

        if ($query->exists()) {
            throw ValidationException::withMessages(['tanggal' => 'Absensi mahasiswa untuk mata kuliah pada tanggal/pertemuan tersebut sudah ada.']);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'mahasiswa_id' => ['required', 'integer', 'exists:mahasiswas,id'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'tahun_akademik' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'mata_kuliah_id' => ['required', 'integer', 'exists:mata_kuliahs,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'jadwal_id' => ['nullable', 'integer', 'exists:jadwals,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'pertemuan' => ['nullable', 'integer', 'min:1', 'max:255'],
            'status' => ['required', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alpha'])],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function formData(): array
    {
        return [
            'mahasiswas' => Mahasiswa::orderBy('nim')->get(), 'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'mataKuliahs' => MataKuliah::orderBy('kode_mk')->get(), 'dosens' => Dosen::orderBy('nama')->get(),
            'jadwals' => Jadwal::with(['mataKuliah', 'dosen', 'kelas'])->orderByDesc('tahun_akademik')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(),
        ];
    }
}
