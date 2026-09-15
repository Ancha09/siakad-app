<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Khs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\LegacyAcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $nilai = $legacy->filterRecords($query, $request)->latest()->paginate(15)->withQueryString();

        return view('admin.nilai-manual.index', compact('nilai') + $legacy->filterOptions());
    }

    public function create()
    {
        return view('admin.nilai-manual.form', $this->formData());
    }

    public function store(Request $request, LegacyAcademicService $legacy)
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
        });

        return redirect()->route('admin.nilai-manual.index')->with('success', 'Nilai lama/manual berhasil disimpan.');
    }

    public function edit(Khs $khs)
    {
        abort_unless($khs->is_manual, 404);
        $khs->load(['krs.mahasiswa', 'krs.mataKuliahManual', 'krs.jadwal']);

        return view('admin.nilai-manual.form', $this->formData() + compact('khs'));
    }

    public function update(Request $request, Khs $khs, LegacyAcademicService $legacy)
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
            [$huruf, $bobot] = $this->grade($data);
            $khs->update([
                'dosen_id' => $data['dosen_id'] ?? null,
                'dosen_override' => true,
                'krs_id' => $krs->id, 'nilai_angka' => $data['nilai_angka'],
                'nilai_huruf' => $huruf, 'bobot' => $bobot,
                'sks' => $data['sks'] ?? MataKuliah::find($data['mata_kuliah_id'])?->sks,
                'tahun_akademik' => $data['tahun_akademik'],
                'semester_akademik' => $data['semester_akademik'],
            ]);
        });

        return redirect()->route('admin.nilai-manual.index')->with('success', 'Nilai lama/manual berhasil diperbarui.');
    }

    public function destroy(Khs $khs)
    {
        abort_unless($khs->is_manual, 404);
        DB::transaction(function () use ($khs) {
            Mahasiswa::whereKey($khs->krs->mahasiswa_id)->lockForUpdate()->firstOrFail();
            $khs->delete();
        });

        return redirect()->route('admin.nilai-manual.index')->with('success', 'Entri nilai manual dihapus. KRS dan absensi tidak dihapus.');
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

    private function grade(array $data): array
    {
        if (! empty($data['nilai_huruf'])) {
            return [$data['nilai_huruf'], $data['bobot'] ?? self::GRADE_WEIGHTS[$data['nilai_huruf']]];
        }

        $nilai = (float) $data['nilai_angka'];
        foreach ([[85, 'A'], [80, 'A-'], [75, 'B+'], [70, 'B'], [65, 'B-'], [60, 'C+'], [55, 'C'], [40, 'D'], [0, 'E']] as [$minimum, $huruf]) {
            if ($nilai >= $minimum) {
                return [$huruf, $data['bobot'] ?? self::GRADE_WEIGHTS[$huruf]];
            }
        }

        return ['E', 0.00];
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
