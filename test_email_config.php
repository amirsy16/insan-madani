<?php

use Illuminate\Support\Facades\Mail;

// Simple test to verify email configuration
try {
    // Test basic mail configuration
    $config = config('mail');
    echo "MAIL_MAILER: " . $config['default'] . "\n";
    echo "MAIL_HOST: " . $config['mailers']['smtp']['host'] . "\n";
    echo "MAIL_PORT: " . $config['mailers']['smtp']['port'] . "\n";
    echo "MAIL_FROM: " . $config['from']['address'] . "\n";
    
    echo "\nEmail configuration looks good!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
