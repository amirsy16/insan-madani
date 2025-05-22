<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndonesiaSeeder extends Seeder
{
    public function run(): void
    {
        // Import data from indonesia.sql
        $sqlPath = database_path('seeders/indonesia.sql');
        
        if (file_exists($sqlPath)) {
            try {
                // Log file size for debugging
                $fileSize = filesize($sqlPath);
                $this->command->info("SQL file size: {$fileSize} bytes");
                
                // Read file content
                $sql = file_get_contents($sqlPath);
                
                // Split SQL into individual statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                // Execute each statement separately
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            DB::unprepared($statement . ';');
                        } catch (\Exception $e) {
                            $this->command->error("Error executing statement: " . $e->getMessage());
                            Log::error("SQL Error: " . $e->getMessage());
                            // Continue with next statement
                        }
                    }
                }
                
                $this->command->info('Indonesia regions data imported successfully!');
            } catch (\Exception $e) {
                $this->command->error('Error importing SQL: ' . $e->getMessage());
                Log::error('Indonesia SQL import error: ' . $e->getMessage());
            }
        } else {
            $this->command->error('indonesia.sql file not found in database/seeders directory!');
            $this->command->info('Expected path: ' . $sqlPath);
        }
    }
}
