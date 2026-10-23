<?php

use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Services\KrsSksLimit;

test('KRS maximum defaults to 22 for non-Informatika and 24 for Informatika regardless of IPK', function () {
    $limit = new KrsSksLimit;

    $mhsTambang = new Mahasiswa;
    $mhsTambang->setRelation('prodi', new Prodi(['kode_prodi' => 'TP', 'nama_prodi' => 'Teknik Pertambangan']));

    $mhsInformatika = new Mahasiswa;
    $mhsInformatika->setRelation('prodi', new Prodi(['kode_prodi' => 'IF', 'nama_prodi' => 'Teknik Informatika']));

    expect($limit->forPeriod(null))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => null]), $mhsTambang))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 24]), $mhsTambang))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 22]), $mhsTambang))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 20]), $mhsTambang))->toBe(20)
        ->and($limit->forPeriod(null, $mhsInformatika))->toBe(24)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 22]), $mhsInformatika))->toBe(24)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 24]), $mhsInformatika))->toBe(24)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 20]), $mhsInformatika))->toBe(20);
});
