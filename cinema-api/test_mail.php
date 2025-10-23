<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

echo "Testing mail configuration...\n";

try {
    // Test sending OTP email
    $otp = '123456';
    $email = 'minhtri484tran@gmail.com';
    $name = 'Test User';
    
    echo "Sending OTP email to: $email\n";
    echo "OTP: $otp\n";
    
    Mail::to($email)->send(new OtpMail($otp, $email, $name));
    
    echo "Email sent successfully!\n";
    
} catch (\Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
