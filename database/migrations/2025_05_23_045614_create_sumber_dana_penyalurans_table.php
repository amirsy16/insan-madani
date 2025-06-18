<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_sumber_dana_penyalurans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumber_dana_penyalurans', function (Blueprint $table) {
            $table->id();
            // Kolom ini akan menyimpan nama sumber dana utama seperti Dana Zakat, Dana Infaq, dll.
            // Ini akan membantu dalam pengkategorian di laporan.
            $table->string('nama_sumber_dana')->unique(); // e.g., Dana Zakat, Dana Infaq, Dana CSR, Dana DSKL, Hak Amil
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            // Mungkin ada relasi ke jenis_donasi jika diperlukan,
            // tapi untuk laporan perubahan dana, pengelompokan umumnya berdasarkan kategori besar ini.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumber_dana_penyalurans');
    }
};