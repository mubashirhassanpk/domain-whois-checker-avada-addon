<?php
/**
 * Simple syntax checker for Domain WHOIS Checker plugin
 */

echo "Checking PHP syntax for Domain WHOIS Checker plugin...\n\n";

$files = [
    'domain-whois-checker.php',
    'includes/class-whois-config.php',
    'includes/class-whois-checker.php',
    'includes/class-admin.php',
    'includes/class-shortcode.php',
    'includes/class-avada-integration.php'
];

$base_path = __DIR__ . '/';
$errors = false;

foreach ($files as $file) {
    $full_path = $base_path . $file;
    
    if (!file_exists($full_path)) {
        echo "❌ File not found: $file\n";
        $errors = true;
        continue;
    }
    
    // Check syntax
    $output = [];
    $return_code = 0;
    exec("php -l \"$full_path\"", $output, $return_code);
    
    if ($return_code === 0) {
        echo "✅ $file - Syntax OK\n";
    } else {
        echo "❌ $file - Syntax Error:\n";
        echo implode("\n", $output) . "\n";
        $errors = true;
    }
}

if (!$errors) {
    echo "\n🎉 All files have valid PHP syntax!\n";
    echo "\nNote: IDE warnings about 'Undefined function' for WordPress functions\n";
    echo "(like get_option, __, add_action, etc.) are expected and not actual errors.\n";
    echo "These functions are provided by WordPress core when the plugin runs.\n";
} else {
    echo "\n❌ Some files have syntax errors that need to be fixed.\n";
}
?>