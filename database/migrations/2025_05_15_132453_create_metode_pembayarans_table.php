<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_metode_pembayarans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('metode_pembayarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // e.g., QRIS, LinkAja, Transfer BSI Zakat (Rek: ...241)
            $table->string('kode')->unique()->nullable(); // e.g., QRIS, LNK, BSIZ241
            $table->enum('tipe', ['digital', 'transfer_bank', 'tunai'])->default('digital');
            $table->string('nomor_rekening')->nullable(); // Jika tipe transfer_bank
            $table->string('atas_nama_rekening')->nullable(); // Jika tipe transfer_bank
            $table->string('bank_name')->nullable(); // Nama bank, misal BSI, Muamalat
            $table->text('instruksi_pembayaran')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('metode_pembayarans');
    }
};