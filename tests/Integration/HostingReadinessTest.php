<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HostingReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'auth.password_reset_enabled' => false,
        ]);
        DB::purge('sqlite');

        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable()->unique();
            $table->string('role')->default('mahasiswa');
        });

        Schema::create('dosens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dosen_id');
            $table->string('tahun_akademik');
            $table->string('semester_akademik');
            $table->timestamps();
        });
        Schema::create('krs', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_manual')->default(false);
            $table->unsignedBigInteger('mahasiswa_id');
            $table->unsignedBigInteger('jadwal_id');
            $table->string('status');
            $table->string('tahun_akademik');
            $table->string('semester_akademik');
            $table->timestamps();
        });
        Schema::create('khs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('krs_id')->unique();
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_huruf')->nullable();
            $table->decimal('bobot', 3, 2)->nullable();
            $table->string('tahun_akademik');
            $table->string('semester_akademik');
            $table->timestamps();
        });
    }

    private function account(string $role): User
    {
        return User::create([
            'name' => ucfirst($role),
            'login' => $role.'_'.fake()->unique()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'role' => $role,
            'password' => Hash::make('password'),
        ]);
    }

    public function test_each_dashboard_area_rejects_the_other_roles(): void
    {
        $admin = $this->account('admin');
        $dosen = $this->account('dosen');
        $mahasiswa = $this->account('mahasiswa');

        $this->actingAs($mahasiswa)->get(route('admin.dosen'))->assertForbidden();
        $this->actingAs($admin)->get(route('dosen.nilai'))->assertForbidden();
        $this->actingAs($dosen)->get(route('mahasiswa.krs'))->assertForbidden();
    }

    public function test_public_registration_and_unconfigured_password_reset_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password')->assertNotFound();
    }

    public function test_lecturer_can_only_save_valid_grades_for_their_own_schedule(): void
    {
        $lecturer = $this->account('dosen');
        $otherLecturer = $this->account('dosen');
        DB::table('dosens')->insert([
            ['id' => 1, 'user_id' => $lecturer->id],
            ['id' => 2, 'user_id' => $otherLecturer->id],
        ]);
        DB::table('jadwals')->insert([
            ['id' => 1, 'dosen_id' => 1, 'tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap'],
            ['id' => 2, 'dosen_id' => 2, 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil'],
        ]);
        DB::table('krs')->insert([
            ['id' => 1, 'mahasiswa_id' => 1, 'jadwal_id' => 1, 'status' => 'Disetujui', 'tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap'],
            ['id' => 2, 'mahasiswa_id' => 2, 'jadwal_id' => 2, 'status' => 'Disetujui', 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil'],
        ]);

        $this->actingAs($lecturer)
            ->post(route('dosen.nilai.store'), ['krs_id' => [1], 'nilai_angka' => [92]])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dosen.nilai'));

        $this->assertDatabaseHas('khs', [
            'krs_id' => 1,
            'nilai_angka' => 92,
            'nilai_huruf' => 'A',
            'tahun_akademik' => '2025/2026',
            'semester_akademik' => 'Genap',
        ]);

        $this->post(route('dosen.nilai.store'), ['krs_id' => [2], 'nilai_angka' => [80]])
            ->assertForbidden();
        $this->assertDatabaseMissing('khs', ['krs_id' => 2]);

        $this->post(route('dosen.nilai.store'), ['krs_id' => [1], 'nilai_angka' => [101]])
            ->assertSessionHasErrors('nilai_angka.0');
    }
}
