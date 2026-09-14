<?php

use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin can create a lecturer account without an email', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $prodi = Prodi::create([
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Industri',
        'jenjang' => 'S1',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.dosen.store'), [
        'nidn' => '0123456789',
        'nama' => 'Dosen Baru',
        'email' => '',
        'telepon' => '08123456789',
        'jabatan' => 'Lektor',
        'golongan' => 'III/c',
        'prodi_id' => $prodi->id,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dosen'));

    $dosen = Dosen::where('nidn', '0123456789')->sole();
    $user = User::where('login', '0123456789')->sole();

    expect($dosen->user_id)->toBe($user->id)
        ->and($dosen->email)->toBeNull()
        ->and($user->email)->toBeNull()
        ->and($user->role)->toBe('dosen')
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('lecturer nidn must not duplicate an existing user login', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $existingUser = User::factory()->create(['login' => '0123456789']);
    $prodi = Prodi::create([
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Industri',
        'jenjang' => 'S1',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.dosen.create'))
        ->post(route('admin.dosen.store'), [
            'nidn' => $existingUser->login,
            'nama' => 'Dosen Duplikat',
            'prodi_id' => $prodi->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response
        ->assertSessionHasErrors('nidn')
        ->assertRedirect(route('admin.dosen.create'));

    $this->assertDatabaseMissing('dosens', ['nidn' => $existingUser->login]);
});
