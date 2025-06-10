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
        Schema::table('jenis_donasis', function (Blueprint $table) {
            $table->boolean('mengandung_dana_non_halal')->default(false)->after('aktif');
            $table->text('keterangan_dana_non_halal')->nullable()->after('mengandung_dana_non_halal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_donasis', function (Blueprint $table) {
            $table->dropColumn(['mengandung_dana_non_halal', 'keterangan_dana_non_halal']);
        });
    }
};
