# 3-2-1-1-0 Backup System Guide

## Overview
The LaundryPro backup system implements the 3-2-1-1-0 backup strategy:
- **3 copies**: Original + 2 additional copies
- **2 media**: Local disk + offsite storage
- **1 offsite**: At least one copy in different location
- **1 offline**: At least one copy not connected to network
- **0 errors**: All backups verified and tested

## Components

### 1. Database Backup
- Full MySQL dump with routines, triggers, and events
- Single-transaction for consistency
- Compressed with gzip
- Encrypted with AES-256

### 2. Files Backup
- All application files and directories
- Excludes: backups, node_modules, .git, logs, cache
- Compressed tar archive
- Encrypted for security

### 3. Local Backup
- Combined database and files backup
- Stored locally for fast recovery
- Retention: 7 days

### 4. Offsite Backup
- Copy to separate storage location
- Can be cloud storage (S3, Google Drive) or remote server
- Retention: 30 days
- Configurable via backup_system.php

### 5. Immutable Backup
- Read-only backup copy
- Cannot be modified or deleted accidentally
- Retention: 90 days
- File permissions set to 0444

## Usage

### Command Line
```bash
# Execute backup
php backup.php execute

# Check backup status
php backup.php status

# Test backup restoration
php backup.php test
php backup.php test <backup_id>
```

### Web Interface (Admin Only)
```bash
# Execute backup
curl -X POST "https://yourdomain.com/backup.php" \
     -H "Content-Type: application/json" \
     -d '{"action":"execute"}'

# Check status
curl "https://yourdomain.com/backup.php?action=status"

# Test backup
curl "https://yourdomain.com/backup.php?action=test&backup_id=backup_xxx"
```

## Configuration

### Basic Settings (backup_system.php)
```php
$this->config = [
    'backup_dir' => __DIR__ . '/backups',
    'local_retention' => 7,      // days
    'offsite_retention' => 30,    // days
    'compression' => true,
    'encryption' => true
];
```

### Encryption Key
Replace the placeholder in `getEncryptionKey()`:
```php
private function getEncryptionKey() {
    // Use environment variable in production
    return hash('sha256', $_ENV['BACKUP_ENCRYPTION_KEY']);
}
```

### Offsite Storage Setup
Modify `createOffsiteBackup()` method:
- AWS S3: Use AWS SDK
- Google Drive: Use Google API
- FTP/SFTP: Use PHP FTP functions
- Remote server: Use SSH/SCP

## Automated Scheduling

### Cron Job (Linux/macOS)
```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/php /path/to/laundry/backup.php execute

# Weekly status check
0 6 * * 1 /usr/bin/php /path/to/laundry/backup.php status
```

### Windows Task Scheduler
```cmd
schtasks /create /tn "LaundryPro Backup" /tr "php C:\path\to\laundry\backup.php execute" /sc daily /st 02:00
```

## Monitoring and Alerts

### Backup Logs
- All backups logged to `backup_logs` table
- Includes success/failure status, file sizes, verification data
- Access via backup.php?action=status

### Email Notifications
Add to `logBackup()` method:
```php
if (!$data['success']) {
    mail('admin@yourdomain.com', 'Backup Failed', $data['error']);
}
```

### Health Checks
```bash
# Check if backup directory exists
test -d /path/to/laundry/backups || echo "Backup directory missing"

# Check disk space
df -h /path/to/laundry/backups

# Check recent backups
find /path/to/laundry/backups -name "*.tar" -mtime -1 | wc -l
```

## Recovery Procedures

### 1. Database Recovery
```bash
# Decrypt if needed
openssl aes-256-cbc -d -in backup.enc -out backup.tar -k your-key

# Extract database backup
tar -xf backup.tar db_backup_*.sql.gz

# Decompress
gunzip db_backup_*.sql.gz

# Restore
mysql -u username -p database_name < db_backup_*.sql
```

### 2. Files Recovery
```bash
# Extract files backup
tar -xf backup.tar files_backup_*.tar

# Restore to application directory
tar -xzf files_backup_*.tar -C /path/to/laundry/
```

### 3. Full System Recovery
1. Stop web server
2. Restore database
3. Restore application files
4. Update configuration if needed
5. Test application
6. Start web server

## Security Considerations

### Access Control
- Backup files should be owned by web server user
- Permissions: 0640 for files, 0750 for directories
- Immutable backups: 0444

### Encryption
- Always encrypt backups containing sensitive data
- Store encryption keys separately from backups
- Rotate encryption keys periodically

### Network Security
- Use SFTP/HTTPS for offsite transfers
- Verify SSL certificates
- Use VPN for remote server access

## Testing

### Monthly Tests
1. Test restore from local backup
2. Test restore from offsite backup
3. Verify backup integrity
4. Check recovery time objectives

### Documentation
- Document recovery procedures
- Update contact information
- Maintain runbook for emergencies

## Troubleshooting

### Common Issues
1. **Permission denied**: Check file/directory permissions
2. **Disk full**: Monitor disk space usage
3. **Network timeout**: Check offsite connectivity
4. **Encryption failed**: Verify key availability

### Log Locations
- PHP error log: Check for PHP errors
- Backup logs: `backup_logs` database table
- System logs: `/var/log/syslog` or Event Viewer

## Best Practices

1. **Regular testing**: Test backup restoration monthly
2. **Monitoring**: Set up alerts for backup failures
3. **Documentation**: Keep recovery procedures updated
4. **Security**: Encrypt and secure backup storage
5. **Retention**: Follow data retention policies
6. **Performance**: Schedule backups during low-traffic periods
