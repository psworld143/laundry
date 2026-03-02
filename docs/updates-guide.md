# Managed Updates Guide

## Overview
Automated patching system for PHP, Composer packages, and database migrations with configurable auto-update policies.

## Features
- Daily update checks
- Security update auto-application
- Email notifications
- Pre-update backups
- Update logging and statistics

## Usage

### Command Line
```bash
# Check for updates
php update_manager.php check

# Apply all updates
php update_manager.php apply

# Apply specific updates
php update_manager.php apply php,composer

# View statistics
php update_manager.php stats
```

### Web Interface (Admin Only)
```bash
# Check updates
curl "https://yourdomain.com/updates.php?action=check"

# Apply updates
curl -X POST "https://yourdomain.com/updates.php" \
     -d "action=apply&categories=php,composer"
```

## Configuration

### Auto-Update Policies
```php
'auto_update' => [
    'security' => true,    // Auto-apply security updates
    'minor' => false,      // Don't auto-apply minor updates
    'major' => false       // Don't auto-apply major updates
]
```

### Notifications
```php
'notification_email' => 'admin@laundrypro.com',
'backup_before_update' => true
```

## Scheduling

### Cron Job
```bash
# Daily check at 3 AM
0 3 * * * /usr/bin/php /path/to/laundry/update_manager.php check

# Weekly auto-apply security updates
0 4 * * 0 /usr/bin/php /path/to/laundry/update_manager.php apply security
```

## Security Considerations
- Always backup before updates
- Test updates in staging first
- Review security advisories
- Monitor update logs
- Use version control for rollback
