<?php

namespace Tests\Integration;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeploymentAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_07_05_041846_add_role_to_users_table.php'))->up();
    }

    public function test_interactive_admin_creation_hashes_password(): void
    {
        $this->artisan('deploy:admin')
            ->expectsQuestion('Nama admin', 'Admin Uji')
            ->expectsQuestion('Login admin', 'admin-uji')
            ->expectsQuestion('Email admin', 'admin@example.test')
            ->expectsQuestion('Password (minimal 12 karakter)', 'password-uji-12345')
            ->expectsQuestion('Ulangi password', 'password-uji-12345')
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password-uji-12345', $admin->password));
    }

    public function test_noninteractive_creation_is_rejected(): void
    {
        $this->artisan('deploy:admin', ['--no-interaction' => true])->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_demo_seeder_is_rejected_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('deploy:admin');
        (new AdminSeeder)->run();
    }
}
