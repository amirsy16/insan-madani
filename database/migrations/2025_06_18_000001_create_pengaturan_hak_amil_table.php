<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pengaturan_hak_amil', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengaturan');
            $table->text('deskripsi')->nullable();
            $table->decimal('persentase_hak_amil', 5, 2); // Persentase hak amil (0-100)
            $table->enum('tipe_pengaturan', ['global', 'per_jenis_donasi', 'per_fundraiser'])->default('global');
            
            // Untuk pengaturan per jenis donasi
            $table->foreignId('jenis_donasi_id')->nullable()->constrained('jenis_donasis')->onDelete('cascade');
            
            // Untuk pengaturan per fundraiser
            $table->foreignId('fundraiser_id')->nullable()->constrained('fundraisers')->onDelete('cascade');
            
            $table->boolean('aktif')->default(true);
            $table->date('tanggal_berlaku_mulai')->nullable();
            $table->date('tanggal_berlaku_berakhir')->nullable();
            
            $table->foreignId('dibuat_oleh_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Index untuk optimasi query
            $table->index(['tipe_pengaturan', 'aktif']);
            $table->index(['jenis_donasi_id', 'aktif']);
            $table->index(['fundraiser_id', 'aktif']);
            
            // Unique constraint untuk menghindari duplikasi
            $table->unique(['tipe_pengaturan', 'jenis_donasi_id', 'fundraiser_id'], 'unique_pengaturan_hak_amil');
        });
    }

    public function down(): void {
        Schema::dropIfExists('pengaturan_hak_amil');
    }
};
