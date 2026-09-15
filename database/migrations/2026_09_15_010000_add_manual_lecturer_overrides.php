<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['khs', 'presensis'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('dosen_id')->nullable()->constrained('dosens')->nullOnDelete();
                // False preserves the lecturer inherited by all existing records.
                $table->boolean('dosen_override')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (['khs', 'presensis'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('dosen_id');
                $table->dropColumn('dosen_override');
            });
        }
    }
};
