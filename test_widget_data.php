<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Donasi;
use App\Models\JenisDonasi;
use App\Models\SumberDanaPenyaluran;
use Illuminate\Support\Facades\DB;

echo "=== Checking Sample Data ===\n";

// Check donations
$totalDonations = Donasi::where('status_konfirmasi', 'verified')->count();
echo "Total verified donations: $totalDonations\n";

$totalAmount = Donasi::where('status_konfirmasi', 'verified')
    ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
echo "Total donation amount: Rp " . number_format($totalAmount, 0, ',', '.') . "\n";

// Check jenis donasi with sumber dana
$jenisWithSumber = JenisDonasi::with('sumberDanaPenyaluran')->get();
echo "\nJenis Donasi mapping:\n";
foreach ($jenisWithSumber as $jenis) {
    $sumber = $jenis->sumberDanaPenyaluran ? $jenis->sumberDanaPenyaluran->nama_sumber_dana : 'NO SUMBER';
    echo "- {$jenis->nama} -> $sumber\n";
}

// Check sumber dana breakdown
echo "\nSumber Dana breakdown:\n";
$sumberDanaTotals = Donasi::where('status_konfirmasi', 'verified')
    ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
    ->join('sumber_dana_penyalurans', 'jenis_donasis.sumber_dana_penyaluran_id', '=', 'sumber_dana_penyalurans.id')
    ->select('sumber_dana_penyalurans.nama_sumber_dana', 
            DB::raw('COUNT(*) as jumlah_transaksi'),
            DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_nilai'))
    ->groupBy('sumber_dana_penyalurans.id', 'sumber_dana_penyalurans.nama_sumber_dana')
    ->orderByDesc('total_nilai')
    ->get();

foreach ($sumberDanaTotals as $sumber) {
    echo "- {$sumber->nama_sumber_dana}: Rp " . number_format($sumber->total_nilai, 0, ',', '.') . " ({$sumber->jumlah_transaksi} transaksi)\n";
}

// Check donations with null sumber dana
$donationsWithoutSumber = Donasi::where('status_konfirmasi', 'verified')
    ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
    ->whereNull('jenis_donasis.sumber_dana_penyaluran_id')
    ->count();

echo "\nDonations without sumber dana: $donationsWithoutSumber\n";

echo "\nFirst 5 donations with details:\n";
$donationsWithDetails = Donasi::where('status_konfirmasi', 'verified')
    ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
    ->leftJoin('sumber_dana_penyalurans', 'jenis_donasis.sumber_dana_penyaluran_id', '=', 'sumber_dana_penyalurans.id')
    ->select('donasis.id', 'jenis_donasis.nama as jenis_nama', 'sumber_dana_penyalurans.nama_sumber_dana', 'donasis.jumlah', 'donasis.perkiraan_nilai_barang')
    ->take(5)
    ->get();

foreach ($donationsWithDetails as $donation) {
    $total = ($donation->jumlah ?? 0) + ($donation->perkiraan_nilai_barang ?? 0);
    $sumber = $donation->nama_sumber_dana ?? 'NULL';
    echo "- ID: {$donation->id}, Jenis: {$donation->jenis_nama}, Sumber: $sumber, Total: Rp " . number_format($total, 0, ',', '.') . "\n";
}
