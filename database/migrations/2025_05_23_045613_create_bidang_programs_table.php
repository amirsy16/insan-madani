<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_bidang_programs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_programs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bidang')->unique(); // e.g., Pendidikan, Kesehatan
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidang_programs');
    }
};
