<?php

require_once 'vendor/autoload.php';

use App\Filament\Imports\DonaturImporter;
use App\Models\Donatur;
use App\Models\Pekerjaan;

// Simple test to validate the DonaturImporter structure
echo "Testing DonaturImporter structure...\n";

// Check if the importer class exists
if (class_exists(DonaturImporter::class)) {
    echo "✅ DonaturImporter class exists\n";
    
    // Test if we can get columns
    try {
        $columns = DonaturImporter::getColumns();
        echo "✅ getColumns() method works\n";
        echo "📊 Number of columns: " . count($columns) . "\n";
        
        foreach ($columns as $column) {
            echo "   - " . $column->getName() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Error getting columns: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ DonaturImporter class not found\n";
}

// Check if models exist
if (class_exists(Donatur::class)) {
    echo "✅ Donatur model exists\n";
} else {
    echo "❌ Donatur model not found\n";
}

if (class_exists(Pekerjaan::class)) {
    echo "✅ Pekerjaan model exists\n";
} else {
    echo "❌ Pekerjaan model not found\n";
}

echo "\nTest completed.\n";
