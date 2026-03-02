<?php
require_once 'config.php';

// Test the current keys
$site_key = RECAPTCHA_SITE_KEY;
$secret_key = RECAPTCHA_SECRET_KEY;

echo "<h2>reCAPTCHA Key Information</h2>";
echo "<p><strong>Site Key:</strong> $site_key</p>";
echo "<p><strong>Secret Key:</strong> $secret_key</p>";

// Test key format
if (strpos($site_key, '6Ldk') === 0) {
    echo "<p style='color: orange;'>⚠️ These appear to be reCAPTCHA v3 keys (starting with 6Ldk)</p>";
    echo "<p style='color: blue;'>ℹ️ reCAPTCHA v3 is invisible and doesn't show a checkbox</p>";
    echo "<p style='color: green;'>✅ Solution: Use reCAPTCHA v2 keys (starting with 6Le)</p>";
} elseif (strpos($site_key, '6Le') === 0) {
    echo "<p style='color: green;'>✅ These appear to be reCAPTCHA v2 keys (starting with 6Le)</p>";
} else {
    echo "<p style='color: red;'>❌ Unknown key format</p>";
}

echo "<h3>Recommended Actions:</h3>";
echo "<ol>";
echo "<li>Go to <a href='https://www.google.com/recaptcha/admin/create' target='_blank'>Google reCAPTCHA Admin Console</a></li>";
echo "<li>Choose 'reCAPTCHA v2' (not v3)</li>";
echo "<li>Add 'localhost' as a domain</li>";
echo "<li>Copy the new keys to config.php</li>";
echo "</ol>";

// Test with Google's official v2 test keys
echo "<h3>Test with Official v2 Keys:</h3>";
echo "<p>For testing, you can temporarily use these official Google v2 test keys:</p>";
echo "<code>Site Key: 6LeIxAcTAAAAAJcZVRqyHh71UMIEbQjVyyTy_0f6</code><br>";
echo "<code>Secret Key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe</code>";
?>
