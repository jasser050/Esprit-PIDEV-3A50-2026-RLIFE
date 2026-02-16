<?php
/**
 * RLIFE Email Configuration Helper
 * 
 * This script helps you configure Gmail SMTP for sending real emails
 * Run: php configure-email.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          RLIFE Email Configuration Helper                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check if .env file exists
if (!file_exists('.env')) {
    echo "❌ Error: .env file not found!\n";
    echo "Make sure you're running this from the studyflow1 directory.\n";
    exit(1);
}

echo "📧 Let's configure Gmail SMTP for RLIFE\n";
echo "\n";

// Ask for Gmail address
echo "Step 1: Enter your Gmail address (e.g., rlife.noreply@gmail.com)\n";
echo "Gmail address: ";
$gmail = trim(fgets(STDIN));

if (empty($gmail) || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address!\n";
    exit(1);
}

echo "\n";
echo "Step 2: Enter your Gmail App Password (16 characters, remove spaces)\n";
echo "⚠️  This is NOT your regular Gmail password!\n";
echo "⚠️  You must create an App Password at: https://myaccount.google.com/apppasswords\n";
echo "App Password: ";
$appPassword = trim(fgets(STDIN));

// Remove spaces from app password
$appPassword = str_replace(' ', '', $appPassword);

if (empty($appPassword) || strlen($appPassword) < 16) {
    echo "❌ App Password seems incorrect! It should be 16 characters.\n";
    exit(1);
}

echo "\n";
echo "📝 Configuration Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Gmail Address: " . $gmail . "\n";
echo "App Password:  " . str_repeat('*', strlen($appPassword)) . " (hidden)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

echo "Do you want to proceed? (yes/no): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'yes' && strtolower($confirm) !== 'y') {
    echo "❌ Configuration cancelled.\n";
    exit(0);
}

// Update .env file
echo "\n";
echo "📝 Updating .env file...\n";

$envContent = file_get_contents('.env');
$mailerDsn = "gmail+smtp://{$gmail}:{$appPassword}@default";

// Replace MAILER_DSN line
$envContent = preg_replace(
    '/MAILER_DSN=.*/',
    "MAILER_DSN={$mailerDsn}",
    $envContent
);

file_put_contents('.env', $envContent);

echo "✅ .env file updated successfully!\n";
echo "\n";

// Update AdminMailerService.php
echo "📝 Updating AdminMailerService.php...\n";

$servicePath = 'src/Service/AdminMailerService.php';
if (file_exists($servicePath)) {
    $serviceContent = file_get_contents($servicePath);
    
    // Replace the from email
    $serviceContent = str_replace(
        "->from('noreply@rlife.com')",
        "->from('{$gmail}')",
        $serviceContent
    );
    
    file_put_contents($servicePath, $serviceContent);
    echo "✅ AdminMailerService.php updated successfully!\n";
} else {
    echo "⚠️  AdminMailerService.php not found at: {$servicePath}\n";
    echo "Please manually update the ->from() email address.\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                  Configuration Complete! ✅                     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Next steps:\n";
echo "1. Clear Symfony cache: php bin/console cache:clear\n";
echo "2. Login to admin panel\n";
echo "3. Go to: /admin/emails/compose\n";
echo "4. Click 'Send Test Email' to test\n";
echo "5. Check your email inbox!\n";
echo "\n";
echo "📚 For more details, read: EMAIL_SETUP_GUIDE.md\n";
echo "\n";
