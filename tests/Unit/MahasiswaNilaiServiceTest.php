<?php

namespace Tests\Unit;

use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\MataKuliah;
use App\Services\MahasiswaNilaiService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class MahasiswaNilaiServiceTest extends TestCase
{
    public function test_ipk_is_locked_while_any_questionnaire_is_pending(): void
    {
        $service = new MahasiswaNilaiService;
        $khs = collect([
            $this->grade(4.0, 3, true),
            $this->grade(2.0, 3, false),
        ]);

        $summary = $service->ringkasan($khs);

        $this->assertSame(3.0, $summary['ipk_aktual']);
        $this->assertNull($summary['ipk_terlihat']);
        $this->assertSame(1, $summary['kuesioner_tertunda']);
        $this->assertTrue($summary['terkunci']);
    }

    public function test_ipk_is_visible_after_all_questionnaires_are_completed(): void
    {
        $service = new MahasiswaNilaiService;
        $khs = collect([
            $this->grade(4.0, 3, true),
            $this->grade(2.0, 3, true),
        ]);

        $summary = $service->ringkasan($khs);

        $this->assertSame(3.0, $summary['ipk_terlihat']);
        $this->assertSame(0, $summary['kuesioner_tertunda']);
        $this->assertFalse($summary['terkunci']);
    }

    public function test_no_grade_is_shown_as_empty_instead_of_locked(): void
    {
        $summary = (new MahasiswaNilaiService)->ringkasan(new Collection);

        $this->assertSame(0.0, $summary['ipk_aktual']);
        $this->assertNull($summary['ipk_terlihat']);
        $this->assertFalse($summary['terkunci']);
    }

    private function grade(float $weight, int $credits, bool $questionnaireCompleted): Khs
    {
        $course = new MataKuliah(['sks' => $credits]);
        $schedule = new Jadwal;
        $schedule->setRelation('mataKuliah', $course);

        $krs = new Krs;
        $krs->setRelation('jadwal', $schedule);
        $krs->setRelation('kuesioner', $questionnaireCompleted ? new Kuesioner : null);

        $grade = new Khs(['bobot' => $weight]);
        $grade->setRelation('krs', $krs);

        return $grade;
    }
}
