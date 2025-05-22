<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_fundraisers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fundraisers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_fundraiser');
            $table->string('nomor_identitas')->unique()->nullable(); // KTP/SIM
            $table->string('nomor_hp')->unique()->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Jika fundraiser punya akun user
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('fundraisers');
    }
};