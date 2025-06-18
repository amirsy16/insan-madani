<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('target_donasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_target');
            $table->text('deskripsi')->nullable();
            $table->decimal('target_nominal', 15, 2);
            $table->decimal('nominal_terkumpul', 15, 2)->default(0);
            $table->decimal('persentase_hak_amil', 5, 2)->default(0); // Persentase hak amil (0-100)
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->enum('status', ['aktif', 'nonaktif', 'selesai'])->default('aktif');
            $table->foreignId('fundraiser_id')->nullable()->constrained('fundraisers')->onDelete('set null');
            $table->foreignId('dibuat_oleh_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Index untuk optimasi query
            $table->index(['status', 'tanggal_mulai', 'tanggal_berakhir']);
            $table->index(['fundraiser_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('target_donasi');
    }
};
