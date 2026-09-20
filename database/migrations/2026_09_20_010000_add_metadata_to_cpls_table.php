<?php

use App\Support\MiningCplCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cpls')) {
            return;
        }

        $needsVisionMission = ! Schema::hasColumn('cpls', 'turunan_visi_misi');
        $needsKkni = ! Schema::hasColumn('cpls', 'cpl_kkni');

        if ($needsVisionMission || $needsKkni) {
            Schema::table('cpls', function (Blueprint $table) use ($needsVisionMission, $needsKkni) {
                if ($needsVisionMission) {
                    $table->text('turunan_visi_misi')->nullable()->after('deskripsi');
                }
                if ($needsKkni) {
                    $table->text('cpl_kkni')->nullable()->after('turunan_visi_misi');
                }
            });
        }

        $miningProgramIds = DB::table('prodis')
            ->whereRaw('LOWER(nama_prodi) LIKE ?', ['%pertambangan%'])
            ->pluck('id');

        foreach (MiningCplCatalog::all() as $code => $metadata) {
            DB::table('cpls')
                ->whereIn('program_studi_id', $miningProgramIds)
                ->where('kode_cpl', $code)
                ->update($metadata + ['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cpls')) {
            return;
        }

        $columns = collect(['turunan_visi_misi', 'cpl_kkni'])
            ->filter(fn (string $column) => Schema::hasColumn('cpls', $column))
            ->all();

        if ($columns !== []) {
            Schema::table('cpls', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
