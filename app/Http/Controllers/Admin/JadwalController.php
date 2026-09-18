<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\Dosen;
use App\Models\Ruangan;
use App\Models\Fakultas;
use App\Models\Prodi;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    // ===================== INDEX =====================

    public function index(Request $request)
    {
        // ===================== DATA FAKULTAS =====================

        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();


        // ===================== DATA PRODI =====================

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();


        // ===================== DATA DOSEN =====================

        $dosens = Dosen::orderBy('nama')
            ->get();


        // ===================== QUERY JADWAL =====================

        $query = Jadwal::with([
            'mataKuliah.prodi.fakultas',
            'dosen',
            'ruangan',
        ]);


        // ===================== SEARCH =====================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // Cari Mata Kuliah
                $q->whereHas('mataKuliah', function ($mk) use ($search) {

                    $mk->whereLike('nama_mk', '%' . $search . '%')
                       ->orWhereLike('kode_mk', '%' . $search . '%');

                })

                // Cari Dosen
                ->orWhereHas('dosen', function ($dosen) use ($search) {

                    $dosen->whereLike('nama', '%' . $search . '%'
                    );

                });

            });
        }


        // ===================== FILTER FAKULTAS =====================

        if ($request->filled('fakultas_id')) {

            $query->whereHas('mataKuliah', function ($mataKuliah) use ($request) {
                $mataKuliah
                    ->whereHas('prodi', fn ($prodi) => $prodi
                        ->where('fakultas_id', $request->fakultas_id))
                    ->orWhereHas('kurikulums.prodi', fn ($prodi) => $prodi
                        ->where('fakultas_id', $request->fakultas_id));
            });
        }


        // ===================== FILTER PRODI =====================

        if ($request->filled('prodi_id')) {

            $query->whereHas('mataKuliah', function ($mataKuliah) use ($request) {
                $mataKuliah
                    ->where('prodi_id', $request->prodi_id)
                    ->orWhereHas('kurikulums', fn ($kurikulum) => $kurikulum
                        ->where('prodi_id', $request->prodi_id));
            });
        }


        // ===================== FILTER DOSEN =====================

        if ($request->filled('dosen_id')) {

            $query->where(
                'dosen_id',
                $request->dosen_id
            );
        }


        // ===================== FILTER HARI =====================

        if ($request->filled('hari')) {

            $query->where(
                'hari',
                $request->hari
            );
        }


        // ===================== FILTER SEMESTER =====================

        if ($request->filled('semester_akademik')) {
            $semester = $this->normalizeAcademicSemester($request->semester_akademik);

            if ($semester) {
                $query->whereIn('semester_akademik', $this->semesterAliases($semester));
            }
        }


        // ===================== HASIL =====================

        $jadwals = $query
            ->orderByRaw("
                CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 0 END
            ")
            ->orderBy('jam_mulai')
            ->paginate(10)
            ->withQueryString();


        return view(
            'admin.jadwal.index',
            compact(
                'jadwals',
                'fakultas',
                'prodis',
                'dosens'
            )
        );
    }


    // ===================== CREATE =====================

    public function create()
    {
        $mataKuliahs = MataKuliah::with('prodi')
            ->orderBy('nama_mk')
            ->get();

        $dosens = Dosen::orderBy('nama')
            ->get();

        $ruangans = Ruangan::orderBy('nama_ruangan')
            ->get();

        return view(
            'admin.jadwal.create',
            compact(
                'mataKuliahs',
                'dosens',
                'ruangans'
            )
        );
    }


    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->merge($this->normalizedPeriodInput($request));

        $validated = $request->validate([
            'mata_kuliah_id'    => ['required', 'exists:mata_kuliahs,id'],
            'dosen_id'          => ['required', 'exists:dosens,id'],
            'ruangan_id'        => ['required', 'exists:ruangans,id'],
            'hari'              => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'jam_mulai'         => ['required'],
            'jam_selesai'       => ['required', 'after:jam_mulai'],
            'tahun_akademik'    => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ]);

        Jadwal::create($validated);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.jadwal'))
            ->with(
                'success',
                'Data jadwal berhasil ditambahkan.'
            );
    }


    // ===================== EDIT =====================

    public function edit(Jadwal $jadwal)
    {
        $mataKuliahs = MataKuliah::with('prodi')
            ->orderBy('nama_mk')
            ->get();

        $dosens = Dosen::orderBy('nama')
            ->get();

        $ruangans = Ruangan::orderBy('nama_ruangan')
            ->get();

        return view(
            'admin.jadwal.edit',
            compact(
                'jadwal',
                'mataKuliahs',
                'dosens',
                'ruangans'
            )
        );
    }


    // ===================== UPDATE =====================

    public function update(
        Request $request,
        Jadwal $jadwal
    ) {
        $request->merge($this->normalizedPeriodInput($request));

        $validated = $request->validate([
            'mata_kuliah_id'    => ['required', 'exists:mata_kuliahs,id'],
            'dosen_id'          => ['required', 'exists:dosens,id'],
            'ruangan_id'        => ['required', 'exists:ruangans,id'],
            'hari'              => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'jam_mulai'         => ['required'],
            'jam_selesai'       => ['required', 'after:jam_mulai'],
            'tahun_akademik'    => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ]);

        $jadwal->update($validated);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.jadwal'))
            ->with(
                'success',
                'Data jadwal berhasil diperbarui.'
            );
    }


    // ===================== DELETE =====================

    public function destroy(Jadwal $jadwal)
    {
        $jadwal->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.jadwal'))
            ->with(
                'success',
                'Data jadwal berhasil dihapus.'
            );
    }

    private function normalizedPeriodInput(Request $request): array
    {
        $year = preg_replace('/\s+/', '', trim((string) $request->input('tahun_akademik')));

        return [
            'tahun_akademik' => str_replace('-', '/', $year ?? ''),
            'semester_akademik' => $this->normalizeAcademicSemester(
                $request->input('semester_akademik')
            ),
        ];
    }

    private function normalizeAcademicSemester(string|int|null $semester): ?string
    {
        $value = strtolower(preg_replace('/\s+/', '', trim((string) $semester)) ?? '');

        return match ($value) {
            'ganjil', 'semesterganjil', '1', 'semester1' => 'Ganjil',
            'genap', 'semestergenap', '2', 'semester2' => 'Genap',
            default => null,
        };
    }

    private function semesterAliases(string $semester): array
    {
        return $semester === 'Ganjil'
            ? ['Ganjil', 'ganjil', 'GANJIL', '1', 'Semester 1', 'semester 1', 'Semester Ganjil', 'semester ganjil']
            : ['Genap', 'genap', 'GENAP', '2', 'Semester 2', 'semester 2', 'Semester Genap', 'semester genap'];
    }
}
