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
        Schema::table('jenis_penggunaan_hak_amils', function (Blueprint $table) {
            $table->decimal('persentase_hak_amil', 5, 2)->default(0)->after('nama')->comment('Persentase hak amil dari total donasi (0-100)');
            $table->foreignId('jenis_donasi_id')->nullable()->after('persentase_hak_amil')->constrained('jenis_donasis')->onDelete('cascade');
            $table->foreignId('sumber_dana_penyaluran_id')->nullable()->after('jenis_donasi_id')->constrained('sumber_dana_penyalurans')->onDelete('cascade');
            $table->boolean('aktif')->default(true)->after('sumber_dana_penyaluran_id');
            $table->date('tanggal_berlaku_mulai')->nullable()->after('aktif');
            $table->date('tanggal_berlaku_berakhir')->nullable()->after('tanggal_berlaku_mulai');
            
            // Index untuk optimasi query
            $table->index(['jenis_donasi_id', 'aktif']);
            $table->index(['sumber_dana_penyaluran_id', 'aktif']);
            
            // Constraint untuk memastikan hanya salah satu yang diisi
            $table->check('(jenis_donasi_id IS NOT NULL AND sumber_dana_penyaluran_id IS NULL) OR (jenis_donasi_id IS NULL AND sumber_dana_penyaluran_id IS NOT NULL) OR (jenis_donasi_id IS NULL AND sumber_dana_penyaluran_id IS NULL)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_penggunaan_hak_amils', function (Blueprint $table) {
            $table->dropIndex(['jenis_donasi_id', 'aktif']);
            $table->dropIndex(['sumber_dana_penyaluran_id', 'aktif']);
            
            $table->dropForeign(['jenis_donasi_id']);
            $table->dropForeign(['sumber_dana_penyaluran_id']);
            
            $table->dropColumn([
                'persentase_hak_amil',
                'jenis_donasi_id',
                'sumber_dana_penyaluran_id',
                'aktif',
                'tanggal_berlaku_mulai',
                'tanggal_berlaku_berakhir'
            ]);
        });
    }
};
