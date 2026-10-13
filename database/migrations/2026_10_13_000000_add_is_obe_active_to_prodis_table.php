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
        if (Schema::hasTable('prodis') && ! Schema::hasColumn('prodis', 'is_obe_active')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->boolean('is_obe_active')->default(false)->after('jenjang');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('prodis') && Schema::hasColumn('prodis', 'is_obe_active')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->dropColumn('is_obe_active');
            });
        }
    }
};
