<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_jenis_donasis_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jenis_donasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // e.g., Zakat Maal, Infak Umum
            $table->string('kode')->unique()->nullable(); // e.g., ZML, IFU (opsional)
            $table->text('deskripsi')->nullable();
            // Untuk menandai apakah jenis ini perlu field keterangan tambahan saat input donasi
            $table->boolean('membutuhkan_keterangan_tambahan')->default(false);
            $table->boolean('apakah_barang')->default(false); // Untuk menandai jika ini donasi barang
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('jenis_donasis');
    }
};