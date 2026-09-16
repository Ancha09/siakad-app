<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\PembayaranKrs;
use App\Models\PeriodeKrs;
use App\Models\PeriodeKrsMahasiswa;
use App\Models\Prodi;
use App\Services\LegacyListNavigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PeriodeKrsController extends Controller
{
    // ===================== INDEX =====================

    public function index(Request $request)
    {
        $filters = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::in(['Ganjil', 'Genap'])],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $periodeKrs = PeriodeKrs::query()
            ->when($filters['tahun_akademik'] ?? null, fn (Builder $query, string $tahun) => $query
                ->where('tahun_akademik', $tahun))
            ->when($filters['semester'] ?? null, fn (Builder $query, string $semester) => $query
                ->where('semester', $semester))
            ->latest()
            ->paginate(10)->withQueryString();

        return view(
            'admin.periode_krs.index',
            [
                'periodeKrs' => $periodeKrs,
                'tahunAkademiks' => PeriodeKrs::whereNotNull('tahun_akademik')
                    ->distinct()
                    ->orderByDesc('tahun_akademik')
                    ->pluck('tahun_akademik'),
            ]
        );
    }

    public function students(Request $request, PeriodeKrs $periodeKrs)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'between:1,14'],
            'status_bayar' => ['nullable', Rule::in(['belum_bayar', 'lunas'])],
            'status_akses' => ['nullable', Rule::in(['belum_dibuka', 'dibuka', 'ditutup'])],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $students = Mahasiswa::query()
            ->with([
                'prodi',
                'kelas',
                'aksesPeriodeKrs' => fn ($query) => $query->where('periode_krs_id', $periodeKrs->id),
                'pembayaranKrs' => fn ($query) => $query
                    ->where('tahun_akademik', $periodeKrs->tahun_akademik)
                    ->where('semester_akademik', $periodeKrs->semester),
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $student) use ($search) {
                    $student->where('nim', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->when($filters['prodi_id'] ?? null, fn (Builder $query, $prodiId) => $query
                ->where('prodi_id', $prodiId))
            ->when($filters['kelas_id'] ?? null, fn (Builder $query, $kelasId) => $query
                ->where('kelas_id', $kelasId))
            ->when($filters['angkatan'] ?? null, function (Builder $query, $angkatan) {
                $query->where(function (Builder $student) use ($angkatan) {
                    $student->where('angkatan', $angkatan)
                        ->orWhereHas('kelas', fn (Builder $kelas) => $kelas->where('angkatan', $angkatan));
                });
            })
            ->when($filters['semester'] ?? null, function (Builder $query, $semester) {
                $query->where(function (Builder $student) use ($semester) {
                    $student->where('semester', $semester)
                        ->orWhereHas('kelas', fn (Builder $kelas) => $kelas->where('semester', $semester));
                });
            });

        if (($filters['status_akses'] ?? null) === 'dibuka') {
            $students->whereHas('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id)
                ->where('status_akses', 'dibuka'));
        } elseif (($filters['status_akses'] ?? null) === 'ditutup') {
            $students->whereHas('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id)
                ->where('status_akses', 'ditutup'));
        } elseif (($filters['status_akses'] ?? null) === 'belum_dibuka') {
            $students->whereDoesntHave('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id));
        }

        if (($filters['status_bayar'] ?? null) === 'lunas') {
            $students->whereHas('pembayaranKrs', fn (Builder $query) => $query
                ->where('tahun_akademik', $periodeKrs->tahun_akademik)
                ->where('semester_akademik', $periodeKrs->semester)
                ->where('status_bayar', 'lunas'));
        } elseif (($filters['status_bayar'] ?? null) === 'belum_bayar') {
            $students->whereDoesntHave('pembayaranKrs', fn (Builder $query) => $query
                ->where('tahun_akademik', $periodeKrs->tahun_akademik)
                ->where('semester_akademik', $periodeKrs->semester)
                ->where('status_bayar', 'lunas'));
        }

        return view('admin.periode_krs.students', [
            'periodeKrs' => $periodeKrs,
            'mahasiswas' => $students->orderBy('nama')->paginate(10)->withQueryString(),
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(),
            'angkatans' => Mahasiswa::whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan'),
        ]);
    }

    public function updateStudentAccess(Request $request, PeriodeKrs $periodeKrs, Mahasiswa $mahasiswa)
    {
        $data = $request->validate([
            'status_akses' => ['required', Rule::in(['dibuka', 'ditutup'])],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ]);

        DB::transaction(function () use ($request, $periodeKrs, $mahasiswa, $data) {
            $current = PeriodeKrsMahasiswa::firstOrNew([
                'periode_krs_id' => $periodeKrs->id,
                'mahasiswa_id' => $mahasiswa->id,
            ]);

            $current->fill([
                'status_akses' => $data['status_akses'],
                'tanggal_dibuka' => $data['status_akses'] === 'dibuka' ? now() : $current->tanggal_dibuka,
                'tanggal_ditutup' => $data['status_akses'] === 'ditutup' ? now() : null,
                'admin_id' => $request->user()->id,
            ])->save();
        });

        $message = $data['status_akses'] === 'dibuka'
            ? "Akses KRS {$mahasiswa->nama} berhasil dibuka."
            : "Akses KRS {$mahasiswa->nama} berhasil ditutup.";

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(
                $request,
                'admin.periode-krs.students',
                ['periodeKrs' => $periodeKrs]
            ))
            ->with('success', $message);
    }

    public function updateStudentPayment(Request $request, PeriodeKrs $periodeKrs, Mahasiswa $mahasiswa)
    {
        $data = $request->validate([
            'status_bayar' => ['required', Rule::in(['belum_bayar', 'lunas'])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ]);

        DB::transaction(function () use ($request, $periodeKrs, $mahasiswa, $data) {
            PembayaranKrs::updateOrCreate([
                'mahasiswa_id' => $mahasiswa->id,
                'tahun_akademik' => $periodeKrs->tahun_akademik,
                'semester_akademik' => $periodeKrs->semester,
            ], [
                'semester' => $mahasiswa->semester ?? $mahasiswa->kelas?->semester,
                'status_bayar' => $data['status_bayar'],
                'tanggal_bayar' => $data['status_bayar'] === 'lunas' ? now()->toDateString() : null,
                'catatan' => $data['catatan'] ?? null,
                'diverifikasi_oleh' => $request->user()->id,
            ]);
        });

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(
                $request,
                'admin.periode-krs.students',
                ['periodeKrs' => $periodeKrs]
            ))
            ->with('success', "Status pembayaran {$mahasiswa->nama} berhasil diperbarui.");
    }

    // ===================== CREATE =====================

    public function create()
    {
        return view('admin.periode_krs.create');
    }

    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'tahun_akademik' => 'required|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'minimal_sks' => 'required|integer|min:0|max:30',
            'maksimal_sks' => 'required|integer|min:1|max:30',
            'status' => 'required|in:Dibuka,Ditutup',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->minimal_sks > $request->maksimal_sks) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' => 'Minimal SKS tidak boleh lebih besar dari maksimal SKS.',
                ]);
        }

        PeriodeKrs::create([
            'tahun_akademik' => $request->tahun_akademik,
            'semester' => $request->semester,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'minimal_sks' => $request->minimal_sks,
            'maksimal_sks' => $request->maksimal_sks,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil ditambahkan.'
            );
    }

    // ===================== EDIT =====================

    public function edit(PeriodeKrs $periodeKrs)
    {
        return view(
            'admin.periode_krs.edit',
            compact('periodeKrs')
        );
    }

    // ===================== UPDATE =====================

    public function update(
        Request $request,
        PeriodeKrs $periodeKrs
    ) {
        $request->validate([
            'tahun_akademik' => 'required|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'minimal_sks' => 'required|integer|min:0|max:30',
            'maksimal_sks' => 'required|integer|min:1|max:30',
            'status' => 'required|in:Dibuka,Ditutup',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->minimal_sks > $request->maksimal_sks) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' => 'Minimal SKS tidak boleh lebih besar dari maksimal SKS.',
                ]);
        }

        $periodeKrs->update([
            'tahun_akademik' => $request->tahun_akademik,
            'semester' => $request->semester,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'minimal_sks' => $request->minimal_sks,
            'maksimal_sks' => $request->maksimal_sks,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil diperbarui.'
            );
    }

    // ===================== TOGGLE STATUS =====================

    public function toggleStatus(PeriodeKrs $periodeKrs)
    {
        $periodeKrs->update([
            'status' => $periodeKrs->status === 'Dibuka'
                ? 'Ditutup'
                : 'Dibuka',
        ]);

        return back()->with(
            'success',
            'Status periode KRS berhasil diubah.'
        );
    }

    // ===================== DELETE =====================

    public function destroy(PeriodeKrs $periodeKrs)
    {
        $periodeKrs->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil dihapus.'
            );
    }
}
