<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up(): void
{
    Schema::table('users', function (Blueprint $table) {

        $table->string('login')->unique()->after('name');

        $table->enum('role', [
            'admin',
            'dosen',
            'mahasiswa'
        ])->default('mahasiswa')->after('login');

    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {

        $table->dropColumn('login');
        $table->dropColumn('role');

    });
}
};
