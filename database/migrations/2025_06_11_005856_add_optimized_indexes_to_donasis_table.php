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
        // Use raw SQL to safely add indexes with IF NOT EXISTS logic
        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_donasis_status_tanggal ON donasis (status_konfirmasi, tanggal_donasi)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_created_status ON donasis (created_at, status_konfirmasi)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_donatur_status ON donasis (donatur_id, status_konfirmasi)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_jenis_status ON donasis (jenis_donasi_id, status_konfirmasi)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_tanggal_status ON donasis (tanggal_donasi, status_konfirmasi)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_metode_pembayaran ON donasis (metode_pembayaran_id)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_fundraiser ON donasis (fundraiser_id)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_hamba_allah ON donasis (atas_nama_hamba_allah)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_dikonfirmasi_oleh ON donasis (dikonfirmasi_oleh_user_id)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_dicatat_oleh ON donasis (dicatat_oleh_user_id)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_common_filters ON donasis (status_konfirmasi, tanggal_donasi, jenis_donasi_id)',
            'CREATE INDEX IF NOT EXISTS idx_donasis_nomor_transaksi ON donasis (nomor_transaksi_unik)',
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                // Silently continue if index already exists
                continue;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'idx_donasis_status_tanggal',
            'idx_donasis_created_status',
            'idx_donasis_donatur_status',
            'idx_donasis_jenis_status',
            'idx_donasis_tanggal_status',
            'idx_donasis_metode_pembayaran',
            'idx_donasis_fundraiser',
            'idx_donasis_hamba_allah',
            'idx_donasis_dikonfirmasi_oleh',
            'idx_donasis_dicatat_oleh',
            'idx_donasis_common_filters',
            'idx_donasis_nomor_transaksi',
        ];

        foreach ($indexes as $indexName) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$indexName} ON donasis");
            } catch (\Exception $e) {
                // Silently continue if index doesn't exist
                continue;
            }
        }
    }
};
