<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Services\StudentCplAssessmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentCplAssessmentController extends Controller
{
    public function index(Request $request, StudentCplAssessmentService $assessments)
    {
        $lecturer = Dosen::where('user_id', Auth::id())->firstOrFail();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'angkatan' => ['nullable', 'integer', 'between:1900,2200'],
            'tahun_akademik' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}[\/-]\d{4}$/'],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);
        $base = Mahasiswa::query()
            ->with('prodi')
            ->where('dosen_wali_id', $lecturer->id)
            ->whereHas('prodi', fn (Builder $query) => $query
                ->whereRaw('LOWER(nama_prodi) LIKE ?', ['%pertambangan%']));
        $students = (clone $base)
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $identity) => $identity
                    ->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")))
            ->when($filters['angkatan'] ?? null, fn (Builder $query, int $year) => $query->where('angkatan', $year))
            ->orderBy('nama')
            ->paginate(12)
            ->withQueryString();
        $profiles = $assessments->profiles($students->getCollection(), $filters);
        $students->setCollection($students->getCollection()->map(function (Mahasiswa $student) use ($profiles) {
            $student->setAttribute('cpl_profile', $profiles->get($student->id));

            return $student;
        }));

        return view('dosen.student-cpl-assessment.index', [
            'students' => $students,
            'filters' => $filters,
            'cohorts' => (clone $base)->whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan'),
            'academicYears' => $assessments->academicYearsForStudents((clone $base)->get(['mahasiswas.id'])),
        ]);
    }

    public function show(Request $request, Mahasiswa $mahasiswa, StudentCplAssessmentService $assessments)
    {
        $lecturer = Dosen::where('user_id', Auth::id())->firstOrFail();
        abort_unless((int) $mahasiswa->dosen_wali_id === (int) $lecturer->id, 403);
        $filters = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}[\/-]\d{4}$/'],
        ]);
        $mahasiswa->load('prodi');
        abort_unless($assessments->supports($mahasiswa->prodi), 404);

        return view('dosen.student-cpl-assessment.show', [
            'profile' => $assessments->profile($mahasiswa, $filters),
            'filters' => $filters,
            'academicYears' => $assessments->academicYearsForStudents(collect([$mahasiswa])),
        ]);
    }
}
