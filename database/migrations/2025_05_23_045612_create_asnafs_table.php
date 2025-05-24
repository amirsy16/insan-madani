<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_asnafs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asnafs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_asnaf')->unique(); // e.g., Fakir, Miskin
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asnafs');
    }
};