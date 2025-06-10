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
        Schema::table('donasis', function (Blueprint $table) {
            // Drop the existing text column
            $table->dropColumn('sub_kategori_dana_non_halal');
            
            // Add new foreign key column
            $table->unsignedBigInteger('kategori_dana_non_halal_id')->nullable()->after('keterangan_infak_khusus');
            
            // Add foreign key constraint
            $table->foreign('kategori_dana_non_halal_id')->references('id')->on('kategori_dana_non_halal')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donasis', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['kategori_dana_non_halal_id']);
            
            // Drop the foreign key column
            $table->dropColumn('kategori_dana_non_halal_id');
            
            // Restore original text column
            $table->text('sub_kategori_dana_non_halal')->nullable()->after('keterangan_infak_khusus');
        });
    }
};
