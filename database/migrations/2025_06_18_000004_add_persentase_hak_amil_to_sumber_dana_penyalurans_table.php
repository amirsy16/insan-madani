<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sumber_dana_penyalurans', function (Blueprint $table) {
            $table->decimal('persentase_hak_amil', 5, 2)->default(12.00)->after('aktif')->comment('Persentase hak amil untuk sumber dana ini (0-100)');
        });
    }

    public function down(): void
    {
        Schema::table('sumber_dana_penyalurans', function (Blueprint $table) {
            $table->dropColumn('persentase_hak_amil');
        });
    }
};
