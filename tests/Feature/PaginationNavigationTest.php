<?php

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Penelitian;
use App\Models\Prodi;
use App\Models\User;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('master edit and save preserve the filtered second page', function (string $master, string $variable, string $parameter) {
    $admin = User::factory()->create(['role' => 'admin']);
    $prodi = Prodi::create(['kode_prodi' => 'NAV', 'nama_prodi' => 'Prodi Navigasi', 'jenjang' => 'S1']);
    foreach (range(1, 11) as $number) {
        if ($master === 'mahasiswa') {
            $user = User::factory()->create(['role' => 'mahasiswa']);
            Mahasiswa::create([
                'nim' => 'NAVM'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'nama' => 'Navigasi Mahasiswa '.$number, 'prodi_id' => $prodi->id,
                'angkatan' => 2020, 'semester' => 1, 'user_id' => $user->id, 'is_active' => true,
            ]);
        } elseif ($master === 'dosen') {
            $user = User::factory()->create(['role' => 'dosen']);
            Dosen::create([
                'nidn' => 'NAVD'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'nama' => 'Navigasi Dosen '.$number, 'prodi_id' => $prodi->id,
                'user_id' => $user->id, 'is_active' => true,
            ]);
        } else {
            MataKuliah::create([
                'kode_mk' => 'NAVK'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'nama_mk' => 'Navigasi Mata Kuliah '.$number,
                'sks' => 3, 'semester' => 1, 'prodi_id' => $prodi->id,
            ]);
        }
    }

    $filters = [$master === 'dosen' ? 'q' : 'search' => 'Navigasi', 'prodi_id' => $prodi->id, 'page' => 2];
    $listRoute = 'admin.'.$master;
    $returnUrl = Request::create(route($listRoute, $filters))->fullUrl();
    $page = $this->actingAs($admin)->get($returnUrl)->assertOk();
    $paginator = $page->viewData($variable);
    $record = $paginator->first();
    expect($paginator->firstItem())->toBe(11)->and($paginator->perPage())->toBe(10);
    $page->assertSee('class="app-pagination"', false)
        ->assertSee('href="'.e($paginator->previousPageUrl()).'"', false);
    $this->get(route($listRoute.'.edit', [$parameter => $record->id, 'return_url' => $returnUrl]))
        ->assertOk()->assertSee('name="return_url" value="'.e($returnUrl).'"', false);
    $this->get(route($listRoute.'.create', ['return_url' => $returnUrl]))
        ->assertOk()->assertSee('name="return_url" value="'.e($returnUrl).'"', false);

    $input = ['prodi_id' => $prodi->id, 'return_url' => $returnUrl];
    if ($master === 'mahasiswa') {
        $input += ['nim' => $record->nim, 'nama' => 'Navigasi Mahasiswa Diperbarui', 'angkatan' => 2020, 'semester' => 1];
    } elseif ($master === 'dosen') {
        $input += ['nidn' => $record->nidn, 'nama' => 'Navigasi Dosen Diperbarui'];
    } else {
        $input += ['kode_mk' => $record->kode_mk, 'nama_mk' => 'Navigasi Mata Kuliah Diperbarui', 'sks' => 3, 'semester' => 1];
    }
    $this->put(route($listRoute.'.update', $record->id), $input)
        ->assertSessionHasNoErrors()->assertRedirect($returnUrl);
    $this->get($returnUrl)->assertOk()->assertViewHas($variable, fn ($items) => $items->currentPage() === 2 && $items->total() === 11);

    if ($master === 'mahasiswa') {
        $this->delete(route($listRoute.'.destroy', $record->id), ['return_url' => $returnUrl])->assertRedirect($returnUrl);
        $this->assertDatabaseHas('mahasiswas', ['id' => $record->id, 'is_active' => false]);
    }
})->with([
    ['mahasiswa', 'mahasiswas', 'mahasiswa'],
    ['dosen', 'dosens', 'dosen'],
    ['matakuliah', 'matakuliahs', 'matakuliah'],
]);

test('shared list navigation rejects external and wrong-list return urls for every master', function () {
    $navigation = new LegacyListNavigation;
    foreach (['mahasiswa', 'dosen', 'matakuliah', 'jadwal', 'krs', 'khs', 'kelas', 'periode-krs', 'prodi', 'ruangan', 'fakultas'] as $master) {
        $listRoute = 'admin.'.$master;
        foreach (['https://evil.test/admin/'.$master.'?page=2', '//evil.test/admin/'.$master, '/admin/presensi-manual?page=2', '/admin/'.$master.'#fragment'] as $url) {
            expect($navigation->returnUrl(Request::create('/', 'POST', ['return_url' => $url]), $listRoute))->toBe(route($listRoute));
        }
        $safe = $navigation->returnUrl(Request::create('/', 'POST', [
            'return_url' => '/admin/'.$master.'?q=Navigasi&fakultas_id=1&page=2&return_url=https://evil.test',
        ]), $listRoute);
        parse_str(parse_url($safe, PHP_URL_QUERY), $query);
        expect($query)->toBe(['q' => 'Navigasi', 'fakultas_id' => '1', 'page' => '2']);
    }
});

test('dosen research save and edit preserve the filtered second page', function () {
    $user = User::factory()->create(['role' => 'dosen']);
    $dosen = Dosen::create(['nidn' => 'NAVP001', 'nama' => 'Dosen Navigasi', 'user_id' => $user->id, 'is_active' => true]);
    foreach (range(1, 11) as $number) {
        Penelitian::create([
            'dosen_id' => $dosen->id, 'judul' => 'Navigasi Penelitian '.$number,
            'jenis' => 'Penelitian', 'tahun' => 2025, 'status' => 'Draft',
        ]);
    }
    $returnUrl = Request::create(route('dosen.penelitian', [
        'search' => 'Navigasi', 'jenis' => 'Penelitian', 'tahun' => 2025, 'status' => 'Draft', 'page' => 2,
    ]))->fullUrl();
    $list = $this->actingAs($user)->get($returnUrl)->assertOk();
    $list->assertSee('name="return_url" value="'.e($returnUrl).'"', false);
    $record = $list->viewData('penelitians')->first();
    $input = ['judul' => 'Navigasi Penelitian Diperbarui', 'jenis' => 'Penelitian', 'tahun' => 2025, 'status' => 'Draft', 'return_url' => $returnUrl];
    $this->put(route('dosen.penelitian.update', $record), $input)
        ->assertSessionHasNoErrors()->assertRedirect($returnUrl);
    $this->post(route('dosen.penelitian.store'), $input)
        ->assertSessionHasNoErrors()->assertRedirect($returnUrl);
    $this->get($returnUrl)->assertOk()->assertViewHas('penelitians', fn ($items) => $items->currentPage() === 2 && $items->total() === 12);
});

test('shared pagination preserves named pages and shows summary even without page links', function () {
    $request = Request::create('/pagination?search=Navigasi&jawaban_page=2&status_page=3');
    app()->instance('request', $request);
    $paginator = new LengthAwarePaginator(range(11, 20), 25, 10, 2, [
        'path' => $request->url(), 'pageName' => 'jawaban_page',
    ]);
    $html = $paginator->appends($request->query())->onEachSide(1)->links('pagination.default')->toHtml();
    expect($html)->toContain('aria-current="page">2</span>', 'class="app-pagination__link"');
    $this->assertMatchesRegularExpression('/Menampilkan\s+11&ndash;20\s+dari\s+25\s+data/', $html);
    expect($html)->toContain('href="'.e($paginator->url(3)).'"');
    parse_str(parse_url($paginator->url(3), PHP_URL_QUERY), $query);
    expect($query)->toBe(['search' => 'Navigasi', 'status_page' => '3', 'jawaban_page' => '3']);

    foreach ([[[1], 1, '1&ndash;1', 1], [[], 0, '0&ndash;0', 0]] as [$items, $total, $range, $expectedTotal]) {
        $single = new LengthAwarePaginator($items, $total, 10, 1, ['path' => $request->url()]);
        $html = $single->links('pagination.default')->toHtml();
        $this->assertMatchesRegularExpression('/Menampilkan\s+'.$range.'\s+dari\s+'.$expectedTotal.'\s+data/', $html);
        expect($html)->not->toContain('class="app-pagination__links"');
    }
});
