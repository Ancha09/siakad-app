<?php

use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Models\User;
use App\Services\KtmService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeKtmStudent(string $nim = 'KTM26001'): array
{
    $user = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::create([
        'kode_prodi' => 'TP-'.$nim,
        'nama_prodi' => 'Teknik Pertambangan',
        'jenjang' => 'S1',
    ]);
    $student = Mahasiswa::create([
        'nim' => $nim,
        'nama' => 'Mahasiswa KTM',
        'alamat' => 'Jalan Cihanjuang No. 161',
        'prodi_id' => $prodi->id,
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return compact('user', 'student');
}

test('student uploads one temporary photo then finalizes and cannot replace it', function () {
    Storage::fake('local');
    $data = makeKtmStudent();

    $this->actingAs($data['user'])
        ->post(route('mahasiswa.ktm.upload'), [
            'photo' => UploadedFile::fake()->image('portrait.png', 600, 800)->size(500),
        ])
        ->assertRedirect(route('mahasiswa.ktm'));

    $student = $data['student']->fresh();
    expect($student->ktm_photo_path)->toBeNull()
        ->and($student->ktm_photo_locked)->toBeFalse();

    $temporaryPath = app(KtmService::class)->temporaryPath($student);
    expect($temporaryPath)->not->toBeNull();
    Storage::disk('local')->assertExists($temporaryPath);

    $this->actingAs($data['user'])
        ->post(route('mahasiswa.ktm.finalize'), ['confirm_final' => '1'])
        ->assertRedirect(route('mahasiswa.ktm'));

    $student->refresh();
    expect($student->ktm_photo_locked)->toBeTrue()
        ->and($student->ktm_photo_uploaded_at)->not->toBeNull();
    Storage::disk('local')->assertExists($student->ktm_photo_path);
    Storage::disk('local')->assertMissing($temporaryPath);

    $this->actingAs($data['user'])
        ->get(route('mahasiswa.ktm.image'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
    $this->actingAs($data['user'])
        ->get(route('mahasiswa.ktm.png'))
        ->assertOk()
        ->assertDownload('KTM-'.$student->nim.'.png');
    $this->actingAs($data['user'])
        ->get(route('mahasiswa.ktm.pdf'))
        ->assertOk()
        ->assertDownload('KTM-'.$student->nim.'.pdf');

    $finalPath = $student->ktm_photo_path;
    $this->actingAs($data['user'])
        ->post(route('mahasiswa.ktm.upload'), [
            'photo' => UploadedFile::fake()->image('replacement.jpg', 600, 800),
        ])
        ->assertSessionHas('error');

    expect($student->fresh()->ktm_photo_path)->toBe($finalPath);
});

test('student can cancel temporary photo and disguised scripts are rejected', function () {
    Storage::fake('local');
    $data = makeKtmStudent('KTM26002');

    $this->actingAs($data['user'])
        ->post(route('mahasiswa.ktm.upload'), [
            'photo' => UploadedFile::fake()->createWithContent('malware.jpg', '<?php echo "bad";'),
        ])
        ->assertSessionHas('error');

    $this->actingAs($data['user'])
        ->post(route('mahasiswa.ktm.upload'), [
            'photo' => UploadedFile::fake()->image('valid.webp', 600, 800),
        ])
        ->assertRedirect(route('mahasiswa.ktm'));

    $path = app(KtmService::class)->temporaryPath($data['student']);
    Storage::disk('local')->assertExists($path);

    $this->actingAs($data['user'])
        ->delete(route('mahasiswa.ktm.cancel'))
        ->assertRedirect(route('mahasiswa.ktm'));

    Storage::disk('local')->assertMissing($path);
});

test('only admin can reset a locked KTM photo', function () {
    Storage::fake('local');
    $data = makeKtmStudent('KTM26003');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($data['user'])->post(route('mahasiswa.ktm.upload'), [
        'photo' => UploadedFile::fake()->image('portrait.jpg', 600, 800),
    ]);
    $this->actingAs($data['user'])->post(route('mahasiswa.ktm.finalize'), ['confirm_final' => '1']);

    $student = $data['student']->fresh();
    $path = $student->ktm_photo_path;

    $this->actingAs($admin)
        ->get(route('admin.ktm.index'))
        ->assertOk()
        ->assertSee('KTM Mahasiswa');
    $this->actingAs($admin)
        ->get(route('admin.ktm.show', $student))
        ->assertOk()
        ->assertSee($student->nim);
    $this->actingAs($admin)
        ->get(route('admin.ktm.image', $student))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs($data['user'])
        ->delete(route('admin.ktm.reset', $student))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('admin.ktm.reset', $student))
        ->assertRedirect(route('admin.ktm.index'));

    $student->refresh();
    expect($student->ktm_photo_locked)->toBeFalse()
        ->and($student->ktm_photo_path)->toBeNull()
        ->and($student->ktm_photo_reset_by)->toBe($admin->id);
    Storage::disk('local')->assertMissing($path);
});

test('temporary KTM cleanup removes photos older than 24 hours', function () {
    Storage::fake('local');
    Storage::disk('local')->put('ktm/temp/99/old.jpg', 'old');
    Storage::disk('local')->put('ktm/temp/99/new.jpg', 'new');
    touch(Storage::disk('local')->path('ktm/temp/99/old.jpg'), now()->subHours(25)->timestamp);

    $this->artisan('ktm:cleanup-temporary')->assertSuccessful();

    Storage::disk('local')->assertMissing('ktm/temp/99/old.jpg');
    Storage::disk('local')->assertExists('ktm/temp/99/new.jpg');
});
