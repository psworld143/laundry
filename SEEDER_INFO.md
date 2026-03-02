# 👥 Customer Data Seeder

## Created 10 Sample Customers

### Quick Seed
**Visit:** `http://localhost/laundry/seed_customers.php`

OR run during setup: `http://localhost/laundry/setup.php` (now includes customers)

---

## Sample Customer Accounts

All customers use the same password: **`password123`**

| # | Username | Name | Email | Phone |
|---|----------|------|-------|-------|
| 1 | john.doe | John Doe | john.doe@email.com | +63 912 345 6789 |
| 2 | jane.smith | Jane Smith | jane.smith@email.com | +63 923 456 7890 |
| 3 | mike.johnson | Mike Johnson | mike.johnson@email.com | +63 934 567 8901 |
| 4 | sarah.williams | Sarah Williams | sarah.williams@email.com | +63 945 678 9012 |
| 5 | david.brown | David Brown | david.brown@email.com | +63 956 789 0123 |
| 6 | emily.davis | Emily Davis | emily.davis@email.com | +63 967 890 1234 |
| 7 | chris.wilson | Chris Wilson | chris.wilson@email.com | +63 978 901 2345 |
| 8 | lisa.garcia | Lisa Garcia | lisa.garcia@email.com | +63 989 012 3456 |
| 9 | robert.martinez | Robert Martinez | robert.martinez@email.com | +63 990 123 4567 |
| 10 | amanda.lee | Amanda Lee | amanda.lee@email.com | +63 901 234 5678 |

---

## Test Customer Login

### Method 1: Use Seeder Page
1. Visit: `http://localhost/laundry/seed_customers.php`
2. Click "Go to Login"
3. Login with any customer username and `password123`

### Method 2: Direct Login
1. Go to: `http://localhost/laundry/login.php`
2. Username: `john.doe` (or any from the list above)
3. Password: `password123`
4. Click Sign In
5. You'll see the **Customer Dashboard**

---

## What You Can Test

### As Customer (john.doe)
- ✅ View customer dashboard
- ✅ See customer stats
- ✅ Navigate to orders page
- ✅ Access profile
- ✅ View services

### As Admin
- ✅ View all 10 customers in customer management
- ✅ Edit customer details
- ✅ Delete customers
- ✅ See customer count in dashboard (11+ with admin)

---

## Features

### Auto-Skip Existing
- If customer already exists, it will be skipped
- No duplicate errors
- Safe to run multiple times

### Secure Passwords
- All passwords are hashed with bcrypt
- Secure password_hash() function
- Cannot see plaintext passwords in database

### Complete Data
- All fields populated
- Valid email addresses
- Philippine phone numbers (+63)
- Realistic names

---

## Database Structure

```sql
INSERT INTO users (
    username,
    name, 
    email,
    phone_number,
    password_hash,
    position,
    is_active
) VALUES (
    'john.doe',
    'John Doe',
    'john.doe@email.com',
    '+63 912 345 6789',
    '$2y$10$...',  -- bcrypt hash
    'user',
    1
);
```

---

## Quick Commands

### View Customers in Database
```sql
SELECT username, name, email, position 
FROM users 
WHERE position = 'user';
```

### Count Customers
```sql
SELECT COUNT(*) FROM users WHERE position = 'user';
```

### Delete All Sample Customers (if needed)
```sql
DELETE FROM users WHERE username IN (
    'john.doe', 'jane.smith', 'mike.johnson', 
    'sarah.williams', 'david.brown', 'emily.davis',
    'chris.wilson', 'lisa.garcia', 'robert.martinez', 
    'amanda.lee'
);
```

---

## Use Cases

### Testing Customer Features
1. Login as different customers
2. Test customer dashboard
3. Create sample orders
4. Test order tracking

### Testing Admin Features
1. Login as admin
2. View customer list
3. Edit customer details
4. Manage customer accounts

### Testing CRUD Operations
1. View customers (Read)
2. Add new customer (Create)
3. Edit customer (Update)
4. Delete customer (Delete)

---

## Summary

✅ **10 sample customers** created  
✅ **All have password:** `password123`  
✅ **Position:** user (customer role)  
✅ **Status:** active (can login)  
✅ **Complete data** (name, email, phone)  
✅ **Realistic information**  
✅ **Ready to use immediately**  

---

**Created:** October 19, 2025  
**Status:** ✅ Ready to Test  
**Password:** password123 (all customers)

