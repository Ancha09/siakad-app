<?php

use App\Http\Controllers\Admin\PresensiManualController;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

test('legacy attendance routes are no longer registered', function () {
    foreach (['index', 'create', 'store', 'edit', 'update', 'destroy'] as $action) {
        expect(Route::has('admin.presensi-manual.'.$action))->toBeFalse();
    }
    foreach (Route::getRoutes() as $route) {
        expect($route->getActionName())->not->toContain(PresensiManualController::class);
    }
    foreach (['admin.presensi', 'admin.presensi.excel', 'admin.presensi.pdf', 'dosen.presensi', 'dosen.presensi.show', 'dosen.presensi.store', 'mahasiswa.presensi'] as $name) {
        expect(Route::has($name))->toBeTrue();
    }
});

test('archived controller cannot write attendance even through stale routes', function () {
    Route::get('/_test/stale-attendance', [PresensiManualController::class, 'index']);
    Route::post('/_test/stale-attendance', [PresensiManualController::class, 'store']);
    Route::put('/_test/stale-attendance/{presensi}', [PresensiManualController::class, 'update']);
    $queries = [];
    DB::listen(function ($event) use (&$queries) {
        $queries[] = $event->sql;
    });

    $this->get('/_test/stale-attendance')->assertNotFound();
    $this->post('/_test/stale-attendance', ['status' => 'Hadir'])->assertNotFound();
    $this->put('/_test/stale-attendance/1', ['status' => 'Alpha'])->assertNotFound();
    expect(array_filter($queries, fn ($query) => preg_match('/^\s*(insert|update|delete)\b/i', $query)))->toBeEmpty();
});

test('regular attendance pages remain accessible and legacy input is not linked', function (string $role, string $route) {
    $user = User::factory()->create(['role' => $role]);
    if ($role === 'dosen') {
        Dosen::create(['nidn' => 'REG001', 'nama' => 'Dosen Reguler', 'user_id' => $user->id, 'is_active' => true]);
    } elseif ($role === 'mahasiswa') {
        Mahasiswa::create(['nim' => 'REG002', 'nama' => 'Mahasiswa Reguler', 'user_id' => $user->id, 'is_active' => true]);
    }

    $this->actingAs($user)->get(route($route))->assertOk()
        ->assertDontSee('Input Absensi Lama')
        ->assertDontSee('/admin/presensi-manual', false);
    $this->get('/admin/presensi-manual')->assertNotFound();
    $this->get('/admin/presensi-manual/create')->assertNotFound();
    $this->get('/admin/presensi-manual/1/edit')->assertNotFound();
    $this->post('/admin/presensi-manual', ['status' => 'Hadir'])->assertNotFound();
    $this->put('/admin/presensi-manual/1', ['status' => 'Alpha'])->assertNotFound();
    $this->delete('/admin/presensi-manual/1')->assertNotFound();
})->with([
    ['admin', 'admin.presensi'],
    ['dosen', 'dosen.presensi'],
    ['mahasiswa', 'mahasiswa.presensi'],
]);
