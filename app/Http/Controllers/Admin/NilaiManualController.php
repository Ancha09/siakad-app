<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\LegacyAcademicService;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NilaiManualController extends Controller
{
    private const GRADE_WEIGHTS = [
        'A' => 4.00, 'A-' => 3.75, 'B+' => 3.50, 'B' => 3.00,
        'B-' => 2.75, 'C+' => 2.50, 'C' => 2.00, 'D' => 1.00, 'E' => 0.00,
    ];

    public function index(Request $request, LegacyAcademicService $legacy)
    {
        $query = Khs::with(['dosenManual', 'krs.mahasiswa', 'krs.mataKuliahManual', 'krs.dosenManual', 'krs.jadwal.mataKuliah', 'krs.jadwal.dosen'])
            ->where('is_manual', true);
        $nilai = $legacy->filterRecords($query, $request)->latest('id')->paginate(10)->appends($request->query());
        if ($nilai->currentPage() > $nilai->lastPage()) {
            return redirect()->route('admin.nilai-manual.index', array_replace($request->query(), ['page' => $nilai->lastPage()]));
        }

        return view('admin.nilai-manual.index', compact('nilai') + $legacy->filterOptions());
    }

    public function create(Request $request, LegacyListNavigation $navigation)
    {
        $returnUrl = $navigation->returnUrl($request, 'admin.nilai-manual.index');

        return view('admin.nilai-manual.form', $this->formData() + compact('returnUrl'));
    }

    public function store(Request $request, LegacyAcademicService $legacy, LegacyListNavigation $navigation)
    {
        $data = $this->validated($request);
        $legacy->validateSchedule($data);

        DB::transaction(function () use ($data, $legacy) {
            Mahasiswa::whereKey($data['mahasiswa_id'])->lockForUpdate()->firstOrFail();
            $duplicate = Khs::whereIn('krs_id', $legacy->matchingKrs($data)->select('id'))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['mata_kuliah_id' => 'Nilai mahasiswa untuk mata kuliah dan periode tersebut sudah ada.']);
            }

            $krs = $legacy->resolveKrs($data);
            [$huruf, $bobot] = $this->grade($data);

            Khs::create([
                'krs_id' => $krs->id,
                'nilai_angka' => $data['nilai_angka'],
                'nilai_huruf' => $huruf,
                'bobot' => $bobot,
                'sks' => $data['sks'] ?? MataKuliah::find($data['mata_kuliah_id'])?->sks,
                'tahun_akademik' => $data['tahun_akademik'],
                'semester_akademik' => $data['semester_akademik'],
                'is_manual' => true,
                'dosen_id' => $data['dosen_id'] ?? null,
                'dosen_override' => true,
            ]);

            // Jika KHS lama pernah dihapus tetapi kuesionernya tetap ada,
            // sinkronkan hanya evaluasi milik KRS ini dengan dosen pilihan admin.
            $this->syncEvaluationContext($krs, $data);
        });

        return redirect()->to($navigation->returnUrl($request, 'admin.nilai-manual.index'))->with('success', 'Nilai lama/manual berhasil disimpan.');
    }

    public function edit(Request $request, Khs $khs, LegacyListNavigation $navigation)
    {
        abort_unless($khs->is_manual, 404);
        $khs->load(['krs.mahasiswa', 'krs.mataKuliahManual', 'krs.jadwal']);

        $returnUrl = $navigation->returnUrl($request, 'admin.nilai-manual.index');

        return view('admin.nilai-manual.form', $this->formData() + compact('khs', 'returnUrl'));
    }

    public function update(Request $request, Khs $khs, LegacyAcademicService $legacy, LegacyListNavigation $navigation)
    {
        abort_unless($khs->is_manual, 404);
        $data = $this->validated($request);
        $legacy->validateSchedule($data);

        DB::transaction(function () use ($data, $legacy, $khs) {
            Mahasiswa::whereKey($data['mahasiswa_id'])->lockForUpdate()->firstOrFail();
            $duplicate = Khs::where('id', '!=', $khs->id)
                ->whereIn('krs_id', $legacy->matchingKrs($data)->select('id'))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['mata_kuliah_id' => 'Nilai mahasiswa untuk mata kuliah dan periode tersebut sudah ada.']);
            }

            $krs = $legacy->resolveKrs($data, overwriteMetadata: true, currentKrsId: $khs->krs_id);
            $nilaiAngkaBerubah = (float) $khs->nilai_angka !== (float) $data['nilai_angka'];
            [$huruf, $bobot] = $this->grade($data, $nilaiAngkaBerubah);
            $khs->update([
                'dosen_id' => $data['dosen_id'] ?? null,
                'dosen_override' => true,
                'krs_id' => $krs->id, 'nilai_angka' => $data['nilai_angka'],
                'nilai_huruf' => $huruf, 'bobot' => $bobot,
                'sks' => $data['sks'] ?? MataKuliah::find($data['mata_kuliah_id'])?->sks,
                'tahun_akademik' => $data['tahun_akademik'],
                'semester_akademik' => $data['semester_akademik'],
            ]);

            // Koreksi dosen pada satu nilai manual harus langsung tercermin pada
            // evaluasi terkait, tanpa backfill atau update massal record lain.
            $this->syncEvaluationContext($krs, $data);
        });

        return redirect()->to($navigation->returnUrl($request, 'admin.nilai-manual.index'))->with('success', 'Nilai lama/manual berhasil diperbarui.');
    }

    public function destroy(Request $request, Khs $khs, LegacyListNavigation $navigation)
    {
        abort_unless($khs->is_manual, 404);
        DB::transaction(function () use ($khs) {
            Mahasiswa::whereKey($khs->krs->mahasiswa_id)->lockForUpdate()->firstOrFail();
            $khs->delete();
        });

        return redirect()->to($navigation->returnUrl($request, 'admin.nilai-manual.index'))->with('success', 'Entri nilai manual dihapus. KRS dan absensi tidak dihapus.');
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
            'nilai_angka' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_huruf' => ['nullable', Rule::in(array_keys(self::GRADE_WEIGHTS))],
            'sks' => ['nullable', 'integer', 'min:1', 'max:30'],
            'bobot' => ['nullable', 'numeric', 'min:0', 'max:4'],
        ]);
    }

    private function grade(array $data, bool $forceFromNumber = false): array
    {
        $nilai = (float) $data['nilai_angka'];
        $hurufOtomatis = 'E';
        foreach ([[85, 'A'], [80, 'A-'], [75, 'B+'], [70, 'B'], [65, 'B-'], [60, 'C+'], [55, 'C'], [40, 'D'], [0, 'E']] as [$minimum, $huruf]) {
            if ($nilai >= $minimum) {
                $hurufOtomatis = $huruf;

                break;
            }
        }

        $huruf = $forceFromNumber || empty($data['nilai_huruf'])
            ? $hurufOtomatis
            : $data['nilai_huruf'];

        return [$huruf, self::GRADE_WEIGHTS[$huruf]];
    }

    private function syncEvaluationContext(Krs $krs, array $data): void
    {
        // Beberapa instalasi/test lama belum memiliki modul kuesioner.
        if (! Schema::hasTable('kuesioners')) {
            return;
        }

        $krs->kuesioner()->update([
            'dosen_id' => $data['dosen_id'] ?? null,
            'mata_kuliah_id' => $data['mata_kuliah_id'],
            'kelas_id' => $data['kelas_id'] ?? $krs->mahasiswa?->kelas_id,
            'tahun_akademik' => $data['tahun_akademik'],
            'semester_akademik' => $data['semester_akademik'],
        ]);
    }

    private function formData(): array
    {
        return [
            'mahasiswas' => Mahasiswa::orderBy('nim')->get(), 'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'mataKuliahs' => MataKuliah::orderBy('kode_mk')->get(), 'dosens' => Dosen::orderBy('nama')->get(),
            'jadwals' => Jadwal::with(['mataKuliah', 'dosen', 'kelas'])->orderByDesc('tahun_akademik')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(), 'gradeLetters' => array_keys(self::GRADE_WEIGHTS),
        ];
    }
}
