<?php

use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\Prodi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can access dashboard and view attendance analytics elements', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSeeText('Perkembangan & Analisis Presensi');
    $response->assertSee('chartAttendanceTrend');
    $response->assertSee('chartAttendanceToday');
    $response->assertSee('chartAttendanceProdi');
    $response->assertSeeText('Total Mahasiswa Terdaftar');
    $response->assertSeeText('Persentase Kehadiran Hari Ini');
    $response->assertSeeText('Total Alpa Hari Ini');
});

test('guest is redirected to login when accessing admin dashboard', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin can fetch attendance analytics via json endpoint', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.dashboard.attendance-analytics', ['range' => '30_days']));

    $response->assertOk();
    $response->assertJsonStructure([
        'range',
        'range_label',
        'start_date',
        'end_date',
        'has_real_data',
        'stats' => [
            'total_students',
            'today_total',
            'today_hadir',
            'today_izin',
            'today_sakit',
            'today_alpa',
            'today_rate',
        ],
        'trend' => [
            'labels',
            'hadir',
            'izin',
            'sakit',
            'alpa',
            'total',
        ],
        'distribution' => [
            'title',
            'labels',
            'counts',
            'rates',
            'colors',
        ],
        'prodi_comparison' => [
            'labels',
            'hadir',
            'izin',
            'sakit',
            'alpa',
        ],
    ]);
});

test('attendance analytics endpoint supports various range filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (['today', '7_days', '30_days', 'this_month'] as $range) {
        $response = $this->actingAs($admin)
            ->getJson(route('admin.dashboard.attendance-analytics', ['range' => $range]));

        $response->assertOk();
        expect($response->json('range'))->toBe($range);
    }

    // Custom date range
    $customResponse = $this->actingAs($admin)->getJson(route('admin.dashboard.attendance-analytics', [
        'range' => 'custom',
        'start_date' => Carbon::today()->subDays(5)->toDateString(),
        'end_date' => Carbon::today()->toDateString(),
    ]));

    $customResponse->assertOk();
    expect($customResponse->json('range'))->toBe('custom')
        ->and(count($customResponse->json('trend.labels')))->toBe(6);
});

test('attendance analytics calculates real attendance counts correctly', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $prodi = Prodi::create([
        'kode_prodi' => 'TI-01',
        'nama_prodi' => 'Teknik Industri',
        'jenjang' => 'S1',
    ]);

    $mahasiswa = Mahasiswa::create([
        'nim' => '12345678',
        'nama' => 'Budi Attendance',
        'prodi_id' => $prodi->id,
        'semester' => 3,
        'is_active' => true,
    ]);

    $krs = Krs::create([
        'mahasiswa_id' => $mahasiswa->id,
        'prodi_id' => $prodi->id,
        'semester' => 3,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    // Create Presensi today: 1 Hadir
    Presensi::create([
        'krs_id' => $krs->id,
        'tanggal' => Carbon::today()->toDateString(),
        'pertemuan' => 1,
        'status' => 'Hadir',
    ]);

    $response = $this->actingAs($admin)->getJson(route('admin.dashboard.attendance-analytics', [
        'range' => 'today',
    ]));

    $response->assertOk();
    expect($response->json('has_real_data'))->toBeTrue()
        ->and($response->json('stats.today_hadir'))->toBe(1)
        ->and((float) $response->json('stats.today_rate'))->toEqual(100.0)
        ->and($response->json('stats.today_alpa'))->toBe(0);
});

test('attendance analytics rejects invalid range parameter with 422', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->getJson(route('admin.dashboard.attendance-analytics', [
        'range' => 'invalid_range_value',
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['range']);
});
