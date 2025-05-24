<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_sumber_dana_to_jenis_donasis_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_donasis', function (Blueprint $table) {
            // Kolom ini akan menghubungkan setiap jenis donasi ke kategori sumber dana utamanya
            $table->foreignId('sumber_dana_penyaluran_id')->nullable()->after('apakah_barang')->constrained('sumber_dana_penyalurans')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_donasis', function (Blueprint $table) {
            $table->dropForeign(['sumber_dana_penyaluran_id']);
            $table->dropColumn('sumber_dana_penyaluran_id');
        });
    }
};