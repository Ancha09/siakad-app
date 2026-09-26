<?php

use App\Models\PeriodeKrs;
use App\Services\KrsSksLimit;

test('temporary KRS maximum defaults to 22 and respects a lower period limit', function () {
    $limit = new KrsSksLimit;

    expect($limit->forPeriod(null))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => null])))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 24])))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 22])))->toBe(22)
        ->and($limit->forPeriod(new PeriodeKrs(['maksimal_sks' => 20])))->toBe(20);
});
