<?php

// Test mail configuration
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure mail
$config = [
    'mail' => [
        'default' => 'smtp',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'username' => $_ENV['MAIL_USERNAME'] ?? 'tritranminh484@gmail.com',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'timeout' => null,
            ],
        ],
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'tritranminh484@gmail.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Cinema API',
        ],
    ],
];

// Test sending email
try {
    $mailer = new \Illuminate\Mail\Mailer(
        new \Illuminate\Mail\Transport\SmtpTransport(
            $config['mail']['mailers']['smtp']['host'],
            $config['mail']['mailers']['smtp']['port'],
            $config['mail']['mailers']['smtp']['encryption'],
            $config['mail']['mailers']['smtp']['username'],
            $config['mail']['mailers']['smtp']['password']
        )
    );
    
    $mailer->send('Test email from Cinema API', [], function ($message) {
        $message->to('tritranminh484@gmail.com')
                ->subject('Test Email - Cinema API')
                ->from('tritranminh484@gmail.com', 'Cinema API');
    });
    
    echo "Email sent successfully!";
    
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage();
}
?>
