<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prodis', function (Blueprint $table) {

            $table->foreignId('fakultas_id')
                ->nullable()
                ->after('jenjang')
                ->constrained('fakultas')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('prodis', function (Blueprint $table) {

            $table->dropForeign(['fakultas_id']);
            $table->dropColumn('fakultas_id');

        });
    }
};