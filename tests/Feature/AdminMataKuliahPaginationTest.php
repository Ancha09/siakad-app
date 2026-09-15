<?php

use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;

test('mata kuliah pagination has working previous and next links that retain filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $prodi = Prodi::create([
        'kode_prodi' => 'TI-PAG',
        'nama_prodi' => 'Teknik Industri',
        'jenjang' => 'S1',
    ]);

    foreach (range(1, 11) as $number) {
        MataKuliah::create([
            'kode_mk' => 'PAG'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'nama_mk' => 'Kalkulus Pagination '.$number,
            'sks' => 3,
            'semester' => 1,
            'prodi_id' => $prodi->id,
        ]);
    }

    $filters = [
        'search' => 'Kalkulus Pagination',
        'prodi_id' => $prodi->id,
        'semester' => 1,
    ];

    $pageOne = $this->actingAs($admin)->get(route('admin.matakuliah', $filters));

    $pageOne
        ->assertOk()
        ->assertSeeText('Menampilkan 1–10 dari 11 data')
        ->assertSeeText('Berikutnya →')
        ->assertSee(e(route('admin.matakuliah', [...$filters, 'page' => 2])), false);

    $pageTwo = $this->actingAs($admin)->get(route('admin.matakuliah', [...$filters, 'page' => 2]));

    $pageTwo
        ->assertOk()
        ->assertSeeText('Menampilkan 11–11 dari 11 data')
        ->assertSeeText('← Sebelumnya')
        ->assertSee(e(route('admin.matakuliah', $filters)), false);
});
