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
        Schema::create('donaturs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_donatur', 10)->unique(); // Kode unik donatur (format: DNyyxxxx)
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('nama');
            
            // Address fields
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignId('village_id')->nullable()->constrained('villages')->onDelete('set null');
            $table->text('alamat_detail')->nullable();
            $table->text('alamat_lengkap')->nullable(); // Combined address for display/search
            
            $table->string('nomor_hp')->unique()->nullable();
            $table->string('email')->unique()->nullable(); // Menambahkan kolom email
            $table->foreignId('pekerjaan_id')->nullable()->constrained('pekerjaans')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donaturs');
    }
};





