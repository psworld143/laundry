# HTTPS/HSTS Enforcement Guide for LaundryPro

## Overview
This guide implements sitewide HTTPS enforcement and HSTS (HTTP Strict Transport Security) to ensure all traffic is encrypted.

## Implementation Status
- ✅ HSTS headers set in security.php (when HTTPS detected)
- ✅ Session cookies marked Secure
- ✅ CSP and security headers configured
- ⚠️  Requires web server configuration for full enforcement

## Web Server Configuration

### Apache (.htaccess)
```apache
# Force HTTPS redirect
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# HSTS Preload (6 months, include subdomains)
Header always set Strict-Transport-Security "max-age=15768000; includeSubDomains; preload"

# Additional security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

### Nginx
```nginx
# Force HTTPS redirect
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS configuration
server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    # SSL certificates
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # HSTS Preload (6 months, include subdomains)
    add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload" always;
    
    # Additional security headers
    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options DENY always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
}
```

## HSTS Preload Submission
1. Test your site: https://hstspreload.org/
2. Ensure HTTPS works for all subdomains
3. Submit domain for preload list

## Certificate Requirements
- Use TLS 1.2+ only
- Strong cipher suites (ECDHE+AESGCM)
- Certificate from trusted CA (Let's Encrypt recommended)

## Testing Commands
```bash
# Test HTTPS redirect
curl -I http://yourdomain.com

# Test HSTS
curl -I https://yourdomain.com

# SSL Labs test
https://www.ssllabs.com/ssltest/
```

## Next Steps
- Configure web server with above rules
- Obtain SSL certificate
- Test HTTPS enforcement
- Submit to HSTS preload list
