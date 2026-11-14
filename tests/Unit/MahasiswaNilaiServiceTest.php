<?php

namespace Tests\Unit;

use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\MahasiswaNilaiKomponen;
use App\Models\MataKuliah;
use App\Models\RpsPenilaianKomponen;
use App\Services\MahasiswaNilaiService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class MahasiswaNilaiServiceTest extends TestCase
{
    public function test_obe_component_scores_are_aggregated_by_normal_assessment_category(): void
    {
        $service = new MahasiswaNilaiService;
        $komponens = collect([
            $this->component(1, 'UTS (S1)', 10),
            $this->component(2, 'UTS (S2)', 30),
            $this->component(3, 'UAS (S1)', 20),
            $this->component(4, 'Tugas terstruktur (S1)', 15),
            $this->component(5, 'Tugas studi kasus (S2)', 15),
            $this->component(6, 'Kuis (S1)', 10),
        ]);
        $nilaiKomponens = collect([
            $this->componentScore(1, 70),
            $this->componentScore(2, 90),
            $this->componentScore(3, 80),
            $this->componentScore(4, 60),
            $this->componentScore(5, 100),
            $this->componentScore(6, 50),
        ]);

        $summary = $service->ringkasanKomponenObe($komponens, $nilaiKomponens);

        $this->assertSame([
            ['nama' => 'UTS', 'nilai' => 85.0, 'bobot' => 40.0],
            ['nama' => 'UAS', 'nilai' => 80.0, 'bobot' => 20.0],
            ['nama' => 'Tugas', 'nilai' => 80.0, 'bobot' => 30.0],
            ['nama' => 'Kuis', 'nilai' => 50.0, 'bobot' => 10.0],
        ], $summary);
    }

    public function test_missing_obe_component_score_uses_zero_like_the_final_grade_calculation(): void
    {
        $komponens = collect([
            $this->component(1, 'UTS (S1)', 25),
            $this->component(2, 'UTS (S2)', 25),
        ]);
        $nilaiKomponens = collect([$this->componentScore(1, 80)]);

        $summary = (new MahasiswaNilaiService)->ringkasanKomponenObe($komponens, $nilaiKomponens);

        $this->assertSame([
            ['nama' => 'UTS', 'nilai' => 40.0, 'bobot' => 50.0],
        ], $summary);
    }

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

    public function test_unfinished_grade_is_not_counted_in_the_index_denominator(): void
    {
        $final = $this->grade(4, 3, true);
        $unfinished = $this->grade(0, 3, true);
        $unfinished->fill(['nilai_angka' => null, 'nilai_huruf' => null, 'bobot' => null]);

        $this->assertSame(4.0, (new MahasiswaNilaiService)->hitungIndeks(collect([$final, $unfinished])));
    }

    private function grade(float $weight, int $credits, bool $questionnaireCompleted): Khs
    {
        $course = new MataKuliah(['sks' => $credits]);
        $schedule = new Jadwal;
        $schedule->setRelation('mataKuliah', $course);

        $krs = new Krs;
        $krs->setRelation('jadwal', $schedule);
        $krs->setRelation('kuesioner', $questionnaireCompleted ? new Kuesioner : null);

        $grade = new Khs(['nilai_angka' => 85, 'nilai_huruf' => 'A', 'bobot' => $weight]);
        $grade->setRelation('krs', $krs);

        return $grade;
    }

    public function test_manual_grades_are_locked_and_masked_until_their_questionnaire_is_completed(): void
    {
        $service = new MahasiswaNilaiService;
        $locked = $this->grade(4, 3, false);
        $locked->fill(['is_manual' => true, 'nilai_angka' => 93.37, 'nilai_huruf' => 'A']);
        $visible = $this->grade(3, 3, true);
        $visible->fill(['is_manual' => true, 'nilai_angka' => 70, 'nilai_huruf' => 'B']);
        $grades = collect([$locked, $visible]);
        $this->assertNull($service->ringkasan($grades)['ipk_terlihat']);
        $this->assertSame(1, $service->ringkasan($grades)['kuesioner_tertunda']);
        $service->sembunyikanNilaiTerkunci($grades);
        foreach (['nilai_angka', 'nilai_huruf', 'bobot'] as $field) {
            $this->assertNull($locked->toArray()[$field]);
        }
        $this->assertSame(70, $visible->nilai_angka);
        $this->assertSame('B', $visible->nilai_huruf);
    }

    private function component(int $id, string $name, float $weight): RpsPenilaianKomponen
    {
        $component = new RpsPenilaianKomponen([
            'nama_instrumen' => $name,
            'bobot' => $weight,
        ]);
        $component->setAttribute('id', $id);

        return $component;
    }

    private function componentScore(int $componentId, float $score): MahasiswaNilaiKomponen
    {
        return new MahasiswaNilaiKomponen([
            'komponen_id' => $componentId,
            'nilai_angka' => $score,
        ]);
    }
}
