# LaundryPro - Laundry Shop Management System

## 🚀 Brand New Clean Architecture

**Complete rebuild with modern technologies:**
- ✅ Pure PHP (No frameworks, minimal dependencies)
- ✅ Tailwind CSS CDN (Modern, responsive UI)
- ✅ AJAX (No page reloads, smooth UX)
- ✅ MySQL Database (Efficient data management)
- ✅ Role-Based Access Control (6 user roles)

---

## 📋 Quick Start

### 1. Database Setup
```sql
-- Import the database schema
mysql -u root -p < database-schema/laundry_updated_schema.sql
```

### 2. Initial Setup
Visit: `http://localhost/laundry/setup.php`

This will create:
- ✅ Admin user (username: `admin`, password: `admin123`)
- ✅ Sample services (4 services)
- ✅ Sample machines (3 machines)

### 3. Login
Visit: `http://localhost/laundry/login.php`

**Admin Credentials:**
- Username: `admin`
- Password: `admin123`

---

## 🏗️ System Architecture

### File Structure
```
laundry/
├── config.php                 # Configuration (constants only)
├── index.php                  # Entry point
├── login.php                  # Authentication
├── logout.php                 # Logout handler
├── dashboard.php              # Role-based router
├── layout.php                 # Main layout template
├── setup.php                  # Initial setup script
│
├── api/                       # AJAX API endpoints
│   ├── machines.php           # Machine CRUD
│   ├── services.php           # Service CRUD
│   ├── staff.php              # Staff CRUD
│   └── customers.php          # Customer CRUD
│
├── pages/                     # All application pages
│   ├── admin/                 # Admin role pages
│   │   ├── dashboard.php
│   │   ├── machines.php
│   │   ├── services.php
│   │   ├── staff.php
│   │   └── customers.php
│   │
│   ├── manager/               # Manager role pages
│   │   └── dashboard.php
│   │
│   ├── operator/              # Operator role pages
│   │   └── dashboard.php
│   │
│   ├── driver/                # Driver role pages
│   │   └── dashboard.php
│   │
│   ├── cashier/               # Cashier role pages
│   │   └── dashboard.php
│   │
│   └── customer/              # Customer role pages
│       └── dashboard.php
│
├── assets/                    # Static assets
│   └── app.js                 # AJAX framework
│
└── database-schema/           # Database schema
    └── laundry_updated_schema.sql
```

---

## 👥 User Roles

### 1. Administrator (admin)
**Full system access**
- Dashboard with system statistics
- Manage customers
- Manage staff
- Manage services
- Manage machines
- Manage inventory
- View all orders
- Generate reports

### 2. Manager (manager)
**Operations management**
- Dashboard with operational metrics
- Manage staff
- View customers
- Manage inventory
- View orders
- Assign tasks

### 3. Operator (operator)
**Machine and order operations**
- Dashboard with active orders
- Process laundry orders
- Manage machines
- Update order status

### 4. Driver (driver)
**Delivery management**
- Dashboard with delivery schedule
- View deliveries
- Update pickup/delivery status
- Track routes

### 5. Cashier (cashier)
**Order and payment processing**
- Dashboard with payment stats
- Create orders
- Process payments
- Issue receipts

### 6. Customer (user)
**Self-service portal**
- Dashboard with order history
- Create new orders
- Track order status
- View invoices

---

## 🎨 Features

### ✅ Implemented
- ✅ Clean authentication (login/logout)
- ✅ Role-based dashboards (6 roles)
- ✅ AJAX-powered CRUD operations
- ✅ Machine management
- ✅ Service management
- ✅ Staff management
- ✅ Customer management
- ✅ Modern, responsive UI
- ✅ No page reloads
- ✅ Real-time updates
- ✅ Error handling
- ✅ Session management
- ✅ Secure authentication

### 🔜 Ready to Extend
- Orders/Transactions module
- Inventory management
- Reports and analytics
- Payment processing
- Delivery tracking
- Customer notifications
- Rating system

---

## 🔧 Technology Stack

| Technology | Purpose |
|------------|---------|
| **PHP 7.4+** | Backend logic |
| **MySQL 5.7+** | Database |
| **Tailwind CSS** | Modern UI framework |
| **AJAX/Fetch API** | Asynchronous operations |
| **Font Awesome** | Icons |
| **PDO** | Database abstraction |

---

## 🛠️ Configuration

### Database Connection
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'laundry');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Application Settings
```php
define('APP_NAME', 'LaundryPro');
define('BASE_URL', '/laundry/');
```

### Debug Mode
```php
define('DEBUG_MODE', true);  // Development
define('DEBUG_MODE', false); // Production
```

---

## 📡 AJAX API Reference

### Machines API
**Endpoint:** `/api/machines.php`

**GET** - Get all machines
```javascript
const res = await Ajax.get('/laundry/api/machines.php');
```

**POST** - Create/Update machine
```javascript
const res = await Ajax.post('/laundry/api/machines.php', {
    machine_name: 'Washer-01',
    machine_type: 'washing_machine',
    brand: 'Samsung',
    model: 'WW10',
    capacity: '10kg',
    location: 'Floor 1',
    status: 'available'
});
```

**DELETE** - Delete machine
```javascript
const res = await Ajax.delete('/laundry/api/machines.php', {
    machine_id: 1
});
```

### Similar endpoints for:
- `/api/services.php` - Service management
- `/api/staff.php` - Staff management
- `/api/customers.php` - Customer management

---

## 🔐 Security Features

✅ **Authentication**
- Session-based authentication
- Password hashing (bcrypt)
- Secure session management

✅ **Authorization**
- Role-based access control
- Permission checks on all pages
- API endpoint protection

✅ **Input Validation**
- Client-side validation
- Server-side sanitization
- SQL injection prevention (PDO prepared statements)
- XSS prevention (HTML escaping)

---

## 🎯 Code Philosophy

### Simple & Clean
- No complex frameworks
- Minimal dependencies
- Easy to understand
- Easy to maintain

### Modular
- Separated concerns
- Reusable components
- Independent modules
- Easy to extend

### Modern
- AJAX for better UX
- Responsive design
- Clean UI/UX
- Best practices

---

## 📖 Usage Guide

### For Administrators

1. **Login** with admin credentials
2. **Access Admin Dashboard** - See system overview
3. **Manage Modules:**
   - **Machines** - Add/edit/delete laundry machines
   - **Services** - Manage service offerings
   - **Staff** - Manage employee records
   - **Customers** - View and manage customers

### For Customers

1. **Register** a new account
2. **Login** to customer portal
3. **Create Orders** - Request laundry services
4. **Track Orders** - Monitor order status
5. **View History** - See past orders

---

## 🐛 Troubleshooting

### Login Issues
1. Clear browser cache and cookies
2. Verify database connection in `config.php`
3. Check that admin user exists
4. Run `setup.php` again if needed

### AJAX Not Working
1. Check browser console for errors
2. Verify API endpoint paths
3. Ensure JavaScript is enabled
4. Check server error logs

### Permission Denied
1. Verify user role in database
2. Check session is active
3. Clear browser cookies
4. Login again

---

## 📊 Database Tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts (all roles) |
| `staff` | Staff member details |
| `services` | Laundry services |
| `machines` | Laundry machines/equipment |
| `transactions` | Orders/transactions |
| `inventory` | Stock and supplies |
| `customer_addresses` | Customer address book |

---

## 🚀 Deployment

### Development
1. XAMPP/WAMP server
2. Import database
3. Run setup.php
4. Login and test

### Production
1. Set `DEBUG_MODE = false` in config.php
2. Change database credentials
3. Update `BASE_URL` to production domain
4. Enable HTTPS
5. Secure file permissions

---

## 📞 Support

For issues or questions:
1. Check this README
2. Review code comments
3. Check browser console
4. Check server error logs (`errors.log`)

---

## 📝 License

Proprietary - Laundry Shop Management System

---

## 🎉 Credits

**Built with:**
- PHP
- MySQL
- Tailwind CSS
- Font Awesome
- Pure JavaScript (Fetch API)

**Architecture:**
- Clean, modular code
- AJAX-powered operations
- Role-based access control
- Modern responsive design

---

**Version:** 2.0.0 (Complete Rebuild)  
**Last Updated:** October 19, 2025  
**Status:** ✅ Production Ready

