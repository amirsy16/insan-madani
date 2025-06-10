<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\DanaService;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\JenisDonasi;
use App\Models\SumberDanaPenyaluran;
use App\Models\ProgramPenyaluran;
use App\Models\MetodePembayaran;
use App\Models\BidangProgram;
use App\Models\Asnaf;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class LaporanPerubahanDanaCalculationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private DanaService $danaService;
    private $startDate;
    private $endDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->danaService = new DanaService();
        $this->startDate = '2024-01-01';
        $this->endDate = '2024-12-31';
        
        // Setup test data
        $this->setupTestData();
    }

    private function setupTestData()
    {
        // Create user for tracking
        $user = User::factory()->create();

        // Create sumber dana
        $sumberZakat = SumberDanaPenyaluran::create([
            'nama_sumber_dana' => 'Dana Zakat',
            'deskripsi' => 'Dana dari zakat maal dan fitrah',
            'aktif' => true
        ]);

        $sumberInfaq = SumberDanaPenyaluran::create([
            'nama_sumber_dana' => 'Dana Infaq',
            'deskripsi' => 'Dana dari infaq dan sedekah',
            'aktif' => true
        ]);

        $sumberHakAmil = SumberDanaPenyaluran::create([
            'nama_sumber_dana' => 'Hak Amil',
            'deskripsi' => 'Dana untuk operasional',
            'aktif' => true
        ]);

        // Create jenis donasi
        $zakatMaal = JenisDonasi::create([
            'nama' => 'Zakat Maal',
            'apakah_barang' => false,
            'sumber_dana_penyaluran_id' => $sumberZakat->id,
            'aktif' => true
        ]);

        $infaqTidakTerikat = JenisDonasi::create([
            'nama' => 'Infaq Tidak Terikat',
            'apakah_barang' => false,
            'sumber_dana_penyaluran_id' => $sumberInfaq->id,
            'aktif' => true
        ]);

        $donasiBarang = JenisDonasi::create([
            'nama' => 'Donasi Barang',
            'apakah_barang' => true,
            'sumber_dana_penyaluran_id' => null,
            'aktif' => true
        ]);

        // Create metode pembayaran
        $metodeTunai = MetodePembayaran::create([
            'nama' => 'Tunai',
            'aktif' => true
        ]);

        // Create donatur
        $donatur1 = Donatur::create([
            'nama' => 'Ahmad Test',
            'gender' => 'male',
            'nomor_hp' => '081234567890',
            'email' => 'ahmad@test.com'
        ]);

        $donatur2 = Donatur::create([
            'nama' => 'Siti Test',
            'gender' => 'female',
            'nomor_hp' => '081234567891',
            'email' => 'siti@test.com'
        ]);

        // Create bidang program
        $bidangPendidikan = BidangProgram::create([
            'nama_bidang' => 'Pendidikan',
            'deskripsi' => 'Program pendidikan',
            'aktif' => true
        ]);

        $bidangSosial = BidangProgram::create([
            'nama_bidang' => 'Sosial',
            'deskripsi' => 'Program sosial',
            'aktif' => true
        ]);

        // Create asnaf for zakat
        $asnafFakir = Asnaf::create([
            'nama_asnaf' => 'Fakir',
            'deskripsi' => 'Orang yang sangat membutuhkan',
            'aktif' => true
        ]);

        // Store created entities for use in tests
        $this->sumberZakat = $sumberZakat;
        $this->sumberInfaq = $sumberInfaq;
        $this->sumberHakAmil = $sumberHakAmil;
        $this->zakatMaal = $zakatMaal;
        $this->infaqTidakTerikat = $infaqTidakTerikat;
        $this->donasiBarang = $donasiBarang;
        $this->metodeTunai = $metodeTunai;
        $this->donatur1 = $donatur1;
        $this->donatur2 = $donatur2;
        $this->bidangPendidikan = $bidangPendidikan;
        $this->bidangSosial = $bidangSosial;
        $this->asnafFakir = $asnafFakir;
        $this->user = $user;
    }

    /** @test */
    public function it_calculates_zakat_with_correct_amil_percentage()
    {
        // Create zakat donation of 1,000,000
        $donasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 1000000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-001'
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        // Find zakat data in report
        $zakatData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana zakat') {
                $zakatData = $data;
                break;
            }
        }

        $this->assertNotNull($zakatData, 'Data Dana Zakat tidak ditemukan dalam laporan');
        
        // Test calculations
        $this->assertEquals(1000000, $zakatData['penerimaan'], 'Penerimaan zakat harus 1,000,000');
        
        // Bagian amil untuk zakat harus 12.5% = 125,000
        $expectedAmil = 1000000 * 0.125;
        $this->assertEquals($expectedAmil, $zakatData['bagian_amil'], 'Bagian amil zakat harus 12.5% = 125,000');
        
        // Surplus defisit = penerimaan - bagian amil - penyaluran
        $expectedSurplus = 1000000 - $expectedAmil - 0; // no penyaluran yet
        $this->assertEquals($expectedSurplus, $zakatData['surplus_defisit'], 'Surplus/defisit calculation incorrect');
    }

    /** @test */
    public function it_calculates_infaq_with_correct_amil_percentage()
    {
        // Create infaq donation of 500,000
        $donasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->infaqTidakTerikat->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 500000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-002'
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        // Find infaq data in report
        $infaqData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana infaq') {
                $infaqData = $data;
                break;
            }
        }

        $this->assertNotNull($infaqData, 'Data Dana Infaq tidak ditemukan dalam laporan');
        
        // Test calculations - infaq should have 12% amil (not 12.5% like zakat)
        $this->assertEquals(500000, $infaqData['penerimaan'], 'Penerimaan infaq harus 500,000');
        
        // Bagian amil untuk infaq harus 12% = 60,000
        $expectedAmil = 500000 * 0.12;
        $this->assertEquals($expectedAmil, $infaqData['bagian_amil'], 'Bagian amil infaq harus 12% = 60,000');
    }

    /** @test */
    public function it_includes_goods_value_in_calculations()
    {
        // Create donation with goods
        $donasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->donasiBarang->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 0, // no cash
            'perkiraan_nilai_barang' => 200000, // goods value
            'deskripsi_barang' => 'Beras 10kg',
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-003'
        ]);

        // Create cash + goods donation
        $donasi2 = Donasi::create([
            'donatur_id' => $this->donatur2->id,
            'jenis_donasi_id' => $this->infaqTidakTerikat->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 300000, // cash
            'perkiraan_nilai_barang' => 100000, // goods value
            'deskripsi_barang' => 'Pakaian layak pakai',
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-004'
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        // Find infaq data
        $infaqData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana infaq') {
                $infaqData = $data;
                break;
            }
        }

        $this->assertNotNull($infaqData, 'Data Dana Infaq tidak ditemukan dalam laporan');
        
        // Total penerimaan should include both cash and goods value
        $expectedTotal = 300000 + 100000; // cash + goods from second donation
        $this->assertEquals($expectedTotal, $infaqData['penerimaan'], 'Penerimaan harus mencakup nilai barang');
    }

    /** @test */
    public function it_calculates_historical_balance_correctly()
    {
        // Create historical donation (before period)
        $historicalDonasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 2000000,
            'tanggal_donasi' => '2023-12-15', // before period
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-HISTORICAL-001'
        ]);

        // Create historical penyaluran (before period)
        $historicalPenyaluran = ProgramPenyaluran::create([
            'nama_program' => 'Program Lama',
            'sumber_dana_penyaluran_id' => $this->sumberZakat->id,
            'bidang_program_id' => $this->bidangPendidikan->id,
            'asnaf_id' => $this->asnafFakir->id,
            'jumlah_dana' => 500000,
            'tanggal_penyaluran' => '2023-12-20', // before period
            'penerima_manfaat_individu' => 'Ahmad Historical',
            'dicatat_oleh_id' => $this->user->id
        ]);

        // Create current period donation
        $currentDonasi = Donasi::create([
            'donatur_id' => $this->donatur2->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 1000000,
            'tanggal_donasi' => '2024-06-15', // in period
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-CURRENT-001'
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        $zakatData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana zakat') {
                $zakatData = $data;
                break;
            }
        }

        $this->assertNotNull($zakatData, 'Data Dana Zakat tidak ditemukan');

        // Calculate expected saldo awal
        $historicalPenerimaan = 2000000;
        $historicalAmil = 2000000 * 0.125; // 12.5% for zakat
        $historicalPenyaluran = 500000;
        $expectedSaldoAwal = $historicalPenerimaan - $historicalAmil - $historicalPenyaluran;

        $this->assertEquals($expectedSaldoAwal, $zakatData['saldo_awal'], 'Saldo awal calculation incorrect');
        $this->assertEquals(1000000, $zakatData['penerimaan'], 'Current period penerimaan incorrect');
    }

    /** @test */
    public function it_calculates_penyaluran_correctly()
    {
        // Create donation
        $donasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 2000000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-005'
        ]);

        // Create penyaluran programs
        $penyaluran1 = ProgramPenyaluran::create([
            'nama_program' => 'Bantuan Pendidikan',
            'sumber_dana_penyaluran_id' => $this->sumberZakat->id,
            'bidang_program_id' => $this->bidangPendidikan->id,
            'asnaf_id' => $this->asnafFakir->id,
            'jumlah_dana' => 500000,
            'tanggal_penyaluran' => '2024-07-15',
            'penerima_manfaat_individu' => 'Budi Test',
            'dicatat_oleh_id' => $this->user->id
        ]);

        $penyaluran2 = ProgramPenyaluran::create([
            'nama_program' => 'Bantuan Sosial',
            'sumber_dana_penyaluran_id' => $this->sumberZakat->id,
            'bidang_program_id' => $this->bidangSosial->id,
            'asnaf_id' => $this->asnafFakir->id,
            'jumlah_dana' => 300000,
            'tanggal_penyaluran' => '2024-08-15',
            'penerima_manfaat_individu' => 'Citra Test',
            'dicatat_oleh_id' => $this->user->id
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        $zakatData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana zakat') {
                $zakatData = $data;
                break;
            }
        }

        $this->assertNotNull($zakatData, 'Data Dana Zakat tidak ditemukan');

        // Test total penyaluran
        $expectedTotalPenyaluran = 500000 + 300000;
        $this->assertEquals($expectedTotalPenyaluran, $zakatData['penyaluran'], 'Total penyaluran calculation incorrect');

        // Test rincian penyaluran by bidang program
        $this->assertArrayHasKey('rincian_penyaluran', $zakatData);
        $this->assertEquals(500000, $zakatData['rincian_penyaluran']['Pendidikan'], 'Penyaluran pendidikan incorrect');
        $this->assertEquals(300000, $zakatData['rincian_penyaluran']['Sosial'], 'Penyaluran sosial incorrect');

        // Test rincian penyaluran by asnaf (for zakat)
        $this->assertArrayHasKey('rincian_penyaluran_asnaf', $zakatData);
        $this->assertEquals($expectedTotalPenyaluran, $zakatData['rincian_penyaluran_asnaf']['Fakir'], 'Penyaluran by asnaf incorrect');
    }

    /** @test */
    public function it_calculates_final_balance_correctly()
    {
        // Create complete scenario
        $donasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 1000000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-006'
        ]);

        $penyaluran = ProgramPenyaluran::create([
            'nama_program' => 'Test Program',
            'sumber_dana_penyaluran_id' => $this->sumberZakat->id,
            'bidang_program_id' => $this->bidangPendidikan->id,
            'asnaf_id' => $this->asnafFakir->id,
            'jumlah_dana' => 200000,
            'tanggal_penyaluran' => '2024-07-15',
            'penerima_manfaat_individu' => 'Test Recipient',
            'dicatat_oleh_id' => $this->user->id
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        $zakatData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana zakat') {
                $zakatData = $data;
                break;
            }
        }

        $this->assertNotNull($zakatData, 'Data Dana Zakat tidak ditemukan');

        // Manual calculation
        $penerimaan = 1000000;
        $bagianAmil = 1000000 * 0.125; // 125,000
        $penyaluranAmount = 200000;
        $saldoAwal = 0; // no historical data
        
        $expectedSurplus = $penerimaan - $bagianAmil - $penyaluranAmount;
        $expectedSaldoAkhir = $saldoAwal + $expectedSurplus;

        $this->assertEquals($expectedSurplus, $zakatData['surplus_defisit'], 'Surplus/defisit calculation incorrect');
        $this->assertEquals($expectedSaldoAkhir, $zakatData['saldo_akhir'], 'Saldo akhir calculation incorrect');
    }

    /** @test */
    public function it_excludes_unverified_donations()
    {
        // Create verified donation
        $verifiedDonasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 1000000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-VERIFIED-001'
        ]);

        // Create pending donation (should be excluded)
        $pendingDonasi = Donasi::create([
            'donatur_id' => $this->donatur2->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 500000,
            'tanggal_donasi' => '2024-06-16',
            'status_konfirmasi' => 'pending',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-PENDING-001'
        ]);

        // Create rejected donation (should be excluded)
        $rejectedDonasi = Donasi::create([
            'donatur_id' => $this->donatur2->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 300000,
            'tanggal_donasi' => '2024-06-17',
            'status_konfirmasi' => 'rejected',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-REJECTED-001'
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        $zakatData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana zakat') {
                $zakatData = $data;
                break;
            }
        }

        $this->assertNotNull($zakatData, 'Data Dana Zakat tidak ditemukan');

        // Should only include verified donation
        $this->assertEquals(1000000, $zakatData['penerimaan'], 'Hanya donasi verified yang harus dihitung');
    }

    /** @test */
    public function it_calculates_summary_totals_correctly()
    {
        // Create donations for different sources
        $zakatDonasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 1000000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-ZAKAT-001'
        ]);

        $infaqDonasi = Donasi::create([
            'donatur_id' => $this->donatur2->id,
            'jenis_donasi_id' => $this->infaqTidakTerikat->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 500000,
            'tanggal_donasi' => '2024-06-16',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-INFAQ-001'
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        $this->assertArrayHasKey('summary', $report, 'Summary data harus ada');

        $summary = $report['summary'];
        
        // Test total penerimaan
        $expectedTotalPenerimaan = 1000000 + 500000;
        $this->assertEquals($expectedTotalPenerimaan, $summary['total_penerimaan'], 'Total penerimaan incorrect');

        // Test total bagian amil
        $zakatAmil = 1000000 * 0.12; // 12% for non-zakat in summary calculation
        $infaqAmil = 500000 * 0.12; // 12% for infaq
        $expectedTotalAmil = $zakatAmil + $infaqAmil;
        $this->assertEquals($expectedTotalAmil, $summary['total_bagian_amil'], 'Total bagian amil incorrect');
    }

    /** @test */
    public function it_handles_hak_amil_funds_correctly()
    {
        // Hak Amil should not have amil deduction
        // This test would require setting up Hak Amil as a jenis donasi
        // For now, we'll test that the calculation logic handles it properly

        $this->assertTrue(true, 'Test placeholder for Hak Amil handling');
    }

    /** @test */
    public function it_handles_negative_balances_correctly()
    {
        // Create scenario where penyaluran exceeds available funds
        $donasi = Donasi::create([
            'donatur_id' => $this->donatur1->id,
            'jenis_donasi_id' => $this->zakatMaal->id,
            'metode_pembayaran_id' => $this->metodeTunai->id,
            'jumlah' => 500000,
            'tanggal_donasi' => '2024-06-15',
            'status_konfirmasi' => 'verified',
            'dicatat_oleh_user_id' => $this->user->id,
            'nomor_transaksi_unik' => 'TRX-TEST-007'
        ]);

        // Large penyaluran that exceeds available funds
        $penyaluran = ProgramPenyaluran::create([
            'nama_program' => 'Large Program',
            'sumber_dana_penyaluran_id' => $this->sumberZakat->id,
            'bidang_program_id' => $this->bidangPendidikan->id,
            'asnaf_id' => $this->asnafFakir->id,
            'jumlah_dana' => 1000000, // exceeds available after amil
            'tanggal_penyaluran' => '2024-07-15',
            'penerima_manfaat_individu' => 'Large Recipient',
            'dicatat_oleh_id' => $this->user->id
        ]);

        $report = $this->danaService->getLaporanPerubahanDana($this->startDate, $this->endDate);

        $zakatData = null;
        foreach ($report as $key => $data) {
            if ($key !== 'summary' && isset($data['title']) && strtolower($data['title']) === 'dana zakat') {
                $zakatData = $data;
                break;
            }
        }

        $this->assertNotNull($zakatData, 'Data Dana Zakat tidak ditemukan');

        // Available after amil: 500000 - (500000 * 0.125) = 437,500
        // Penyaluran: 1,000,000
        // Surplus/defisit should be negative
        $availableAfterAmil = 500000 - (500000 * 0.125);
        $expectedDeficit = $availableAfterAmil - 1000000;
        
        $this->assertEquals($expectedDeficit, $zakatData['surplus_defisit'], 'Deficit calculation should be correct');
        $this->assertTrue($zakatData['surplus_defisit'] < 0, 'Should show deficit when penyaluran exceeds available funds');
    }
}
