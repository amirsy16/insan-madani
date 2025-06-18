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
        Schema::create('penggunaan_hak_amils', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            // Kolom untuk menghubungkan ke jenis penggunaan hak amil
            $table->foreignId('jenis_penggunaan_hak_amil_id')->constrained('jenis_penggunaan_hak_amils')->onDelete('cascade');
            $table->string('keterangan')->nullable(); // Untuk detail tambahan jika diperlukan
            $table->decimal('jumlah', 15, 2);
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User yang mencatat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggunaan_hak_amils');
    }
};