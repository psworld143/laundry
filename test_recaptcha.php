<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>reCAPTCHA Test</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <h1>reCAPTCHA Test Page</h1>
    <p>Site Key: <?= RECAPTCHA_SITE_KEY ?></p>
    
    <form>
        <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
        <br>
        <button type="submit">Submit</button>
    </form>
    
    <script>
        console.log('reCAPTCHA site key:', '<?= RECAPTCHA_SITE_KEY ?>');
        console.log('reCAPTCHA script loaded');
        
        // Check if reCAPTCHA is loaded
        window.onload = function() {
            if (typeof grecaptcha !== 'undefined') {
                console.log('reCAPTCHA is loaded and ready');
            } else {
                console.error('reCAPTCHA failed to load');
            }
        };
    </script>
</body>
</html>
