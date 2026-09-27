<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mahasiswas', 'alamat')) {
            Schema::table('mahasiswas', fn (Blueprint $table) => $table->text('alamat')->nullable());
        }

        if (! Schema::hasColumn('mahasiswas', 'ktm_photo_path')) {
            Schema::table('mahasiswas', fn (Blueprint $table) => $table->string('ktm_photo_path')->nullable());
        }

        if (! Schema::hasColumn('mahasiswas', 'ktm_photo_uploaded_at')) {
            Schema::table('mahasiswas', fn (Blueprint $table) => $table->timestamp('ktm_photo_uploaded_at')->nullable());
        }

        if (! Schema::hasColumn('mahasiswas', 'ktm_photo_locked')) {
            Schema::table('mahasiswas', fn (Blueprint $table) => $table->boolean('ktm_photo_locked')->default(false));
        }

        if (! Schema::hasColumn('mahasiswas', 'ktm_photo_reset_at')) {
            Schema::table('mahasiswas', fn (Blueprint $table) => $table->timestamp('ktm_photo_reset_at')->nullable());
        }

        if (! Schema::hasColumn('mahasiswas', 'ktm_photo_reset_by')) {
            Schema::table('mahasiswas', fn (Blueprint $table) => $table->foreignId('ktm_photo_reset_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete());
        }
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ktm_photo_reset_by');
            $table->dropColumn([
                'alamat',
                'ktm_photo_path',
                'ktm_photo_uploaded_at',
                'ktm_photo_locked',
                'ktm_photo_reset_at',
            ]);
        });
    }
};
