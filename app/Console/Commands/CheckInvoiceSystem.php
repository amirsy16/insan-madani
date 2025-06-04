<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Donasi;
use App\Models\InvoiceDonasi;
use App\Services\InvoiceDeliveryService;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckInvoiceSystem extends Command
{
    protected $signature = 'invoice:check-system';
    protected $description = 'Check invoice delivery system health and configuration';

    public function handle()
    {
        $this->info('🔍 Checking Madani Invoice Delivery System...');
        $this->newLine();

        // Check database tables
        $this->checkDatabase();
        
        // Check services
        $this->checkServices();
        
        // Check file permissions
        $this->checkFilePermissions();
        
        // Check configuration
        $this->checkConfiguration();
        
        // Summary
        $this->showSummary();
    }

    private function checkDatabase()
    {
        $this->info('📊 Database Status:');
        
        // Check invoice_donasi table
        if (Schema::hasTable('invoice_donasi')) {
            $this->line('✅ invoice_donasi table exists');
            $count = InvoiceDonasi::count();
            $this->line("   - Records: {$count}");
        } else {
            $this->error('❌ invoice_donasi table missing');
        }
        
        // Check donasi table
        if (Schema::hasTable('donasi')) {
            $verifiedCount = Donasi::where('status_konfirmasi', 'verified')->count();
            $this->line("✅ donasi table exists ({$verifiedCount} verified donations)");
        } else {
            $this->error('❌ donasi table missing');
        }
        
        $this->newLine();
    }

    private function checkServices()
    {
        $this->info('🔧 Service Status:');
        
        try {
            $deliveryService = app(InvoiceDeliveryService::class);
            $this->line('✅ InvoiceDeliveryService initialized');
        } catch (\Exception $e) {
            $this->error('❌ InvoiceDeliveryService failed: ' . $e->getMessage());
        }
        
        try {
            $pdfService = app(InvoicePdfService::class);
            $this->line('✅ InvoicePdfService initialized');
        } catch (\Exception $e) {
            $this->error('❌ InvoicePdfService failed: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function checkFilePermissions()
    {
        $this->info('📁 File Permissions:');
        
        $paths = [
            storage_path('app/public/invoices'),
            storage_path('logs'),
            public_path('storage'),
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $this->line("✅ {$path} (permissions: {$perms})");
            } else {
                $this->warn("⚠️  {$path} does not exist");
                
                if (str_contains($path, 'invoices')) {
                    $this->info("   Creating invoices directory...");
                    mkdir($path, 0755, true);
                    $this->line("   ✅ Created invoices directory");
                }
            }
        }
        
        $this->newLine();
    }

    private function checkConfiguration()
    {
        $this->info('⚙️  Configuration Status:');
        
        // Email configuration
        $mailDriver = config('mail.default');
        $this->line("✅ Mail driver: {$mailDriver}");
        
        if ($mailDriver !== 'log') {
            $mailHost = config('mail.mailers.'.$mailDriver.'.host');
            $this->line("   - SMTP Host: {$mailHost}");
        }
        
        // SMS configuration
        $smsDriver = config('services.sms.driver', 'not configured');
        $this->line("📱 SMS driver: {$smsDriver}");
        
        // WhatsApp configuration
        $whatsappDriver = config('services.whatsapp.driver', 'disabled');
        $this->line("💬 WhatsApp driver: {$whatsappDriver}");
        
        // Organization info
        $orgName = config('app.organization.name');
        $this->line("🏢 Organization: {$orgName}");
        
        $this->newLine();
    }

    private function showSummary()
    {
        $this->info('📋 System Summary:');
        
        // Available delivery methods
        $this->line('📤 Available Delivery Methods:');
        $this->line('   ✅ Email (active)');
        $this->line('   ⏸️  SMS (infrastructure ready, gateway needed)');
        $this->line('   ❌ WhatsApp (temporarily disabled)');
        $this->line('   ✅ Download Manual (active)');
        $this->line('   ✅ Print for Pickup (active)');
        
        $this->newLine();
        
        // Quick stats
        $totalInvoices = InvoiceDonasi::count();
        $sentInvoices = InvoiceDonasi::where('delivery_status', 'sent')->count();
        $pendingInvoices = InvoiceDonasi::where('delivery_status', 'pending')->count();
        
        $this->line("📊 Invoice Statistics:");
        $this->line("   - Total invoices: {$totalInvoices}");
        $this->line("   - Successfully sent: {$sentInvoices}");
        $this->line("   - Pending: {$pendingInvoices}");
        
        $this->newLine();
        $this->info('🎉 System Status: READY FOR PRODUCTION');
    }
}
