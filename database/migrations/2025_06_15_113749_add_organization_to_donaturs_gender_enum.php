<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mengubah enum gender untuk menambahkan 'organization'
        DB::statement("ALTER TABLE donaturs MODIFY COLUMN gender ENUM('male', 'female', 'organization') DEFAULT 'male'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke enum semula (hanya male, female)
        // PERHATIAN: Data dengan gender='organization' akan hilang jika rollback
        DB::statement("ALTER TABLE donaturs MODIFY COLUMN gender ENUM('male', 'female') DEFAULT 'male'");
    }
};
