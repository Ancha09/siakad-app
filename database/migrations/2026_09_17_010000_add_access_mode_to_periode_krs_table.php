<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('periode_krs', 'access_mode')) {
            Schema::table('periode_krs', function (Blueprint $table) {
                // Preserve the existing per-student behavior for periods already stored.
                $table->string('access_mode', 20)->default('selected')->after('status');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty so production access configuration is never removed.
    }
};
