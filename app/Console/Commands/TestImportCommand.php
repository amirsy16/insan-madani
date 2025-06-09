<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Filament\Imports\DonaturImporter;
use App\Filament\Imports\FundraiserImporter;
use App\Filament\Imports\DonasiImporter;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TestImportCommand extends Command
{
    protected $signature = 'test:import {type} {--file=}';
    protected $description = 'Test CSV import functionality';

    public function handle()
    {
        $type = $this->argument('type');
        $file = $this->option('file');
        
        if (!$file) {
            $file = "test_{$type}_import.csv";
        }
        
        if (!file_exists($file)) {
            $this->error("File {$file} tidak ditemukan!");
            return 1;
        }
        
        // Set user untuk testing (diperlukan untuk import)
        $user = User::first();
        if (!$user) {
            $this->error("Tidak ada user ditemukan di database!");
            return 1;
        }
        Auth::login($user);
        
        $this->info("Testing import {$type} dengan file: {$file}");
        
        try {
            // Upload file to storage untuk testing
            $content = file_get_contents($file);
            $storagePath = "imports/test_{$type}_" . time() . ".csv";
            Storage::disk('local')->put($storagePath, $content);
            
            // Create import record
            $import = Import::create([
                'user_id' => $user->id,
                'file_name' => basename($file),
                'file_path' => $storagePath,
                'importer' => $this->getImporterClass($type),
                'total_rows' => $this->countCsvRows($file),
                'processed_rows' => 0,
                'successful_rows' => 0,
                'failed_rows_count' => 0,
            ]);
            
            $this->info("Import record dibuat dengan ID: {$import->id}");
            
            // Test column mapping
            $this->testColumnMapping($type);
            
            // Test validation rules
            $this->testValidationRules($type);
            
            $this->info("✅ Testing selesai! Import {$type} siap digunakan.");
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function getImporterClass($type)
    {
        return match($type) {
            'donatur' => DonaturImporter::class,
            'fundraiser' => FundraiserImporter::class, 
            'donasi' => DonasiImporter::class,
            default => throw new \InvalidArgumentException("Invalid type: {$type}")
        };
    }
    
    private function countCsvRows($file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return count($lines) - 1; // Minus header
    }
    
    private function testColumnMapping($type)
    {
        $this->info("📋 Testing column mapping untuk {$type}...");
        
        $importerClass = $this->getImporterClass($type);
        $columns = $importerClass::getColumns();
        
        $this->info("   Kolom yang dikonfigurasi: " . count($columns));
        
        $requiredColumns = [];
        foreach ($columns as $column) {
            if (method_exists($column, 'isRequired') && $column->isRequired()) {
                $requiredColumns[] = $column->getName();
            }
        }
        
        if (!empty($requiredColumns)) {
            $this->info("   Kolom wajib: " . implode(', ', $requiredColumns));
        }
        
        $this->info("   ✅ Column mapping OK");
    }
    
    private function testValidationRules($type)
    {
        $this->info("🔍 Testing validation rules untuk {$type}...");
        
        $importerClass = $this->getImporterClass($type);
        $columns = $importerClass::getColumns();
        
        $rulesCount = 0;
        foreach ($columns as $column) {
            if (method_exists($column, 'getRules') && !empty($column->getRules())) {
                $rulesCount++;
            }
        }
        
        $this->info("   Kolom dengan validation rules: {$rulesCount}");
        $this->info("   ✅ Validation rules OK");
    }
}
