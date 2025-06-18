<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_program_penyalurans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_penyalurans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_program_penyaluran')->unique()->nullable(); // Kode unik untuk transaksi penyaluran
            $table->string('nama_program'); // Nama kegiatan/program penyaluran
            $table->date('tanggal_penyaluran');
            $table->decimal('jumlah_dana', 15, 2); // Jumlah dana yang disalurkan

            // Relasi ke Sumber Dana Utama dari mana dana ini diambil
            $table->foreignId('sumber_dana_penyaluran_id')->constrained('sumber_dana_penyalurans')->onDelete('restrict');

            // Relasi ke Jenis Donasi spesifik (jika dana diambil dari jenis donasi tertentu, misal 'Zakat Maal')
            // Ini opsional, tergantung seberapa detail Anda ingin melacak sumbernya.
            // Jika sumber_dana_penyaluran_id sudah cukup (misal 'Dana Zakat'), ini bisa nullable.
            $table->foreignId('jenis_donasi_id')->nullable()->constrained('jenis_donasis')->onDelete('set null');

            $table->foreignId('asnaf_id')->nullable()->constrained('asnafs')->onDelete('set null'); // Untuk penyaluran zakat ke asnaf
            $table->foreignId('bidang_program_id')->nullable()->constrained('bidang_programs')->onDelete('set null'); // Untuk penyaluran berdasarkan bidang program

            $table->string('penerima_manfaat_individu')->nullable(); // Jika penerima adalah individu
            $table->string('penerima_manfaat_lembaga')->nullable(); // Jika penerima adalah lembaga/kelompok
            $table->integer('jumlah_penerima_manfaat')->nullable()->default(1); // Jumlah orang/KK/lembaga

            $table->text('lokasi_penyaluran')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('bukti_penyaluran')->nullable(); // Path ke file dokumentasi/bukti

            $table->foreignId('dicatat_oleh_id')->constrained('users')->onDelete('restrict'); // User yang mencatat

            $table->timestamps();
            $table->softDeletes(); // Jika ingin menggunakan soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_penyalurans');
    }
};