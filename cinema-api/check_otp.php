<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailVerification;

echo "Checking OTP records for minhtri484tran@gmail.com:\n";

$otps = EmailVerification::where('email', 'minhtri484tran@gmail.com')->get();

if ($otps->count() > 0) {
    foreach ($otps as $otp) {
        echo "Email: " . $otp->email . "\n";
        echo "OTP: " . $otp->otp . "\n";
        echo "Expires: " . $otp->expires_at . "\n";
        echo "Used: " . ($otp->is_used ? 'Yes' : 'No') . "\n";
        echo "---\n";
    }
} else {
    echo "No OTP records found for this email.\n";
}

echo "Total OTP records: " . EmailVerification::count() . "\n";
