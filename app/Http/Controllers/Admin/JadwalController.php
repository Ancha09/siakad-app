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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

                })
                ->orWhereLike('group_key', '%' . $search . '%');

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
        $validated = $this->validatedSchedule($request);

        DB::transaction(function () use ($validated) {
            $this->ensureSharedGroupIsConsistent($validated);
            $this->ensureScheduleDoesNotConflict($validated);

            Jadwal::create($validated);
        });


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
        $validated = $this->validatedSchedule($request);

        DB::transaction(function () use ($validated, $jadwal) {
            $this->ensureSharedGroupIsConsistent($validated, $jadwal);
            $this->ensureScheduleDoesNotConflict($validated, $jadwal);

            $jadwal->update($validated);
        });


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

    private function validatedSchedule(Request $request): array
    {
        $request->merge($this->normalizedPeriodInput($request) + [
            'is_lintas_prodi' => $request->boolean('is_lintas_prodi'),
        ]);

        $validated = $request->validate([
            'mata_kuliah_id'    => ['required', 'exists:mata_kuliahs,id'],
            'dosen_id'          => ['required', 'exists:dosens,id'],
            'ruangan_id'        => ['required', 'exists:ruangans,id'],
            'kelas_id'          => ['nullable', 'exists:kelas,id'],
            'hari'              => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'jam_mulai'         => ['required', 'regex:/^\d{2}:\d{2}(?::\d{2})?$/'],
            'jam_selesai'       => ['required', 'regex:/^\d{2}:\d{2}(?::\d{2})?$/', 'after:jam_mulai'],
            'tahun_akademik'    => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'is_lintas_prodi'   => ['required', 'boolean'],
            'group_key'         => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
        ], [
            'group_key.regex' => 'Kode grup hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
        ]);

        $validated['jam_mulai'] = $this->normalizeTime($validated['jam_mulai']);
        $validated['jam_selesai'] = $this->normalizeTime($validated['jam_selesai']);

        if (! $validated['is_lintas_prodi']) {
            $validated['group_key'] = null;

            return $validated;
        }

        $validated['group_key'] = strtoupper(trim((string) ($validated['group_key'] ?? '')));

        if ($validated['group_key'] === '') {
            $validated['group_key'] = $this->generatedGroupKey($validated);
        }

        return $validated;
    }

    private function ensureSharedGroupIsConsistent(array $data, ?Jadwal $ignored = null): void
    {
        if (! $data['is_lintas_prodi'] || empty($data['group_key'])) {
            return;
        }

        $existing = Jadwal::query()
            ->where('is_lintas_prodi', true)
            ->where('group_key', $data['group_key'])
            ->when($ignored, fn ($query) => $query->whereKeyNot($ignored->getKey()))
            ->lockForUpdate()
            ->first();

        if ($existing && $this->scheduleSignature($existing->toArray()) !== $this->scheduleSignature($data)) {
            throw ValidationException::withMessages([
                'group_key' => 'Kode grup tersebut sudah dipakai oleh sesi dengan dosen, hari, jam, ruangan, atau periode yang berbeda.',
            ]);
        }
    }

    private function ensureScheduleDoesNotConflict(array $data, ?Jadwal $ignored = null): void
    {
        $incomingCourse = MataKuliah::with('kurikulums')->findOrFail($data['mata_kuliah_id']);
        $incomingProgramIds = $this->courseProgramIds($incomingCourse);

        $conflicts = Jadwal::query()
            ->with('mataKuliah.kurikulums')
            ->where('hari', $data['hari'])
            ->whereRaw(
                "REPLACE(REPLACE(TRIM(tahun_akademik), ' ', ''), '-', '/') = ?",
                [$data['tahun_akademik']]
            )
            ->whereIn('semester_akademik', $this->semesterAliases($data['semester_akademik']))
            ->where('jam_mulai', '<', $data['jam_selesai'])
            ->where('jam_selesai', '>', $data['jam_mulai'])
            ->where(function ($query) use ($data) {
                $query->where('dosen_id', $data['dosen_id'])
                    ->orWhere('ruangan_id', $data['ruangan_id']);
            })
            ->when($ignored, fn ($query) => $query->whereKeyNot($ignored->getKey()))
            ->lockForUpdate()
            ->get();

        foreach ($conflicts as $conflict) {
            $sameSessionDetails = $this->scheduleSignature($conflict->toArray())
                === $this->scheduleSignature($data);
            $sameGroup = filled($data['group_key'])
                && filled($conflict->group_key)
                && hash_equals((string) $conflict->group_key, (string) $data['group_key']);
            $bothMarkedAsShared = $data['is_lintas_prodi'] && $conflict->is_lintas_prodi;
            $sharedSessionAllowed = $sameSessionDetails && ($sameGroup || $bothMarkedAsShared);
            $sameProgramScope = $this->programScopesOverlap(
                $incomingProgramIds,
                $this->courseProgramIds($conflict->mataKuliah)
            );

            // Mata kuliah reguler milik prodi yang benar-benar berbeda boleh
            // memakai slot yang sama. Ruang lingkup kosong berarti MKU/umum
            // dan sengaja dianggap beririsan dengan seluruh prodi.
            if ($sameProgramScope && ! $sharedSessionAllowed) {
                throw ValidationException::withMessages([
                    'jam_mulai' => 'Jadwal bentrok dengan jadwal lain dalam program studi yang sama pada hari, jam, dan periode tersebut.',
                ]);
            }
        }
    }

    private function courseProgramIds(?MataKuliah $course): array
    {
        if (! $course) {
            return [];
        }

        if (! $course->relationLoaded('kurikulums')) {
            $course->load('kurikulums');
        }

        return collect([$course->prodi_id])
            ->merge($course->kurikulums->pluck('prodi_id'))
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function programScopesOverlap(array $left, array $right): bool
    {
        // Tanpa prodi berarti mata kuliah umum/MKU yang berlaku lintas prodi.
        if ($left === [] || $right === []) {
            return true;
        }

        return array_intersect($left, $right) !== [];
    }

    private function scheduleSignature(array $data): array
    {
        return [
            (int) ($data['dosen_id'] ?? 0),
            strtolower(trim((string) ($data['hari'] ?? ''))),
            $this->normalizeTime((string) ($data['jam_mulai'] ?? '')),
            $this->normalizeTime((string) ($data['jam_selesai'] ?? '')),
            (int) ($data['ruangan_id'] ?? 0),
            str_replace('-', '/', preg_replace('/\s+/', '', trim((string) ($data['tahun_akademik'] ?? ''))) ?? ''),
            $this->normalizeAcademicSemester($data['semester_akademik'] ?? null),
        ];
    }

    private function generatedGroupKey(array $data): string
    {
        return 'GAB-' . strtoupper(substr(hash('sha256', implode('|', $this->scheduleSignature($data))), 0, 16));
    }

    private function normalizeTime(string $time): string
    {
        [$hour, $minute, $second] = array_pad(explode(':', trim($time)), 3, '00');

        return sprintf('%02d:%02d:%02d', (int) $hour, (int) $minute, (int) $second);
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
