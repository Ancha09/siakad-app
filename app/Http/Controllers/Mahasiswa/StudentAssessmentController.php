<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Services\StudentCplAssessmentService;
use Illuminate\Support\Facades\Auth;

class StudentAssessmentController extends Controller
{
    public function index(StudentCplAssessmentService $assessments)
    {
        $student = Mahasiswa::with('prodi')->where('user_id', Auth::id())->firstOrFail();

        return view('mahasiswa.student-assessment.index', [
            'profile' => $assessments->profile($student, [], true),
        ]);
    }
}
