# How to View Receipts in LaundryPro

## 📋 Overview
The LaundryPro system provides multiple ways to view receipts depending on your role and needs. This guide covers all available methods.

## 🔍 Methods to View Receipts

### 1. **Universal Receipt Viewer** (All Roles)
**Location**: `pages/receipt-viewer.php`

**Features**:
- View both regular orders and custom orders
- Search by Order ID, Customer Name, or Phone Number
- Print receipts
- Export to PDF (coming soon)
- Share receipts
- Mobile-responsive design

**How to Access**:
1. Navigate to `pages/receipt-viewer.php`
2. Use the search bar to find receipts
3. Click on search results to view receipt details

### 2. **Operator Receipt Management** (Operators & Admins)
**Location**: `pages/operator/receipt.php`

**Features**:
- Search orders by ID or customer name
- Print receipts
- Export to PDF
- Order history and management

**How to Access**:
1. Login as Operator or Admin
2. Go to Operator Dashboard
3. Click "Receipt Management"
4. Search for orders and view receipts

### 3. **Customer Order History** (Customers)
**Location**: `pages/customer/orders.php`

**Features**:
- View personal order history
- Access receipt details
- Track order status

**How to Access**:
1. Login as Customer
2. Go to Customer Dashboard
3. Click "My Orders" or "All Orders"
4. Click on any order to view receipt

### 4. **Cashier Dashboard** (Cashiers & Admins)
**Location**: `pages/cashier/dashboard.php`

**Features**:
- View recent transactions
- Quick access to order details
- Process payments

**How to Access**:
1. Login as Cashier or Admin
2. Go to Cashier Dashboard
3. View recent transactions table
4. Click "View Details" or "Process Payment"

### 5. **Admin Order Management** (Admins Only)
**Location**: `pages/admin/orders.php`

**Features**:
- Complete order management
- View all orders and receipts
- Advanced filtering and search

**How to Access**:
1. Login as Admin
2. Go to Admin Dashboard
3. Click "Orders"
4. Search and view any order receipt

## 🔎 Search Methods

### By Order ID
- Enter the 6-digit order ID (e.g., `000123`)
- Works for both regular and custom orders

### By Customer Name
- Enter customer's full name or partial name
- Case-insensitive search

### By Phone Number
- Enter customer's phone number
- Supports various formats

### By Date Range
- Use date filters in admin/operator interfaces
- View receipts from specific time periods

## 📱 Receipt Features

### **Receipt Information Displayed**:
- **Header**: Company branding and contact info
- **Receipt Details**: Order ID, date, time, type
- **Customer Info**: Name, phone, email
- **Order Items**: Service details, quantities, prices
- **Payment Info**: Method, status, amount
- **Service Status**: Laundry status, completion estimates
- **Special Instructions**: Customer notes and requirements

### **Receipt Types**:
1. **Regular Orders**: Standard laundry services
2. **Custom Orders**: Customer fabric-based orders

### **Actions Available**:
- **Print**: Generate physical receipt
- **PDF Export**: Download as PDF file
- **Share**: Share receipt link
- **Search**: Find specific receipts

## 🖨️ Printing Receipts

### **Print Options**:
1. **Direct Print**: Click "Print Receipt" button
2. **Print Preview**: Browser print preview
3. **Print Settings**: Configure paper size, orientation

### **Print Features**:
- Professional receipt layout
- Company branding
- Complete order details
- Print-optimized styling

## 📊 Receipt Management by Role

### **Admin**
- ✅ View all receipts
- ✅ Search any order
- ✅ Print receipts
- ✅ Export data
- ✅ Manage order status

### **Cashier**
- ✅ View recent transactions
- ✅ Process payments
- ✅ Print receipts
- ✅ Customer service

### **Operator**
- ✅ Receipt management
- ✅ Order processing
- ✅ Print receipts
- ✅ Customer support

### **Driver**
- ✅ Payment scanning
- ✅ Receipt generation
- ✅ Mobile access

### **Customer**
- ✅ Personal order history
- ✅ Receipt viewing
- ✅ Order tracking

## 🔗 Direct Links

### **Quick Access URLs**:
- Universal Viewer: `/pages/receipt-viewer.php`
- Operator Receipts: `/pages/operator/receipt.php`
- Customer Orders: `/pages/customer/orders.php`
- Cashier Dashboard: `/pages/cashier/dashboard.php`
- Admin Orders: `/pages/admin/orders.php`

### **Direct Receipt Access**:
- Regular Order: `/pages/receipt-viewer.php?id=123`
- Custom Order: `/pages/receipt-viewer.php?id=456`

## 📱 Mobile Access

### **Mobile Features**:
- Responsive design
- Touch-friendly interface
- Mobile printing
- Share functionality
- Camera scanning (drivers)

## 🛠️ Troubleshooting

### **Common Issues**:
1. **Receipt Not Found**: Verify order ID exists
2. **Access Denied**: Check user permissions
3. **Print Issues**: Check browser print settings
4. **Search Not Working**: Try different search terms

### **Support**:
- Contact system administrator
- Check user role permissions
- Verify order exists in database

## 📈 Best Practices

### **For Staff**:
- Always verify customer identity before showing receipts
- Use search functionality for quick access
- Print receipts when requested
- Keep receipts secure and private

### **For Customers**:
- Save receipt numbers for reference
- Keep printed receipts for records
- Contact support for receipt issues

---

**Note**: Receipt access is role-based. Ensure you have appropriate permissions for your user role.
