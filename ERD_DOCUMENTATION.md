# Laundry Management System - ERD Documentation
## For Lucidchart Import

This document contains all entities, attributes, and relationships for creating an Entity Relationship Diagram (ERD) in Lucidchart.

---

## ENTITIES AND ATTRIBUTES

### 1. users
**Primary Key:** user_id
**Attributes:**
- user_id (PK, int, auto_increment)
- username (varchar(50), unique)
- name (varchar(100))
- password_hash (varchar(255))
- email (varchar(100), unique)
- phone_number (varchar(20))
- position (enum: 'admin', 'user', 'cashier', 'driver', 'operator', 'manager')
- is_active (tinyint(1))
- created_at (datetime)
- updated_at (datetime)

### 2. customer_addresses
**Primary Key:** address_id
**Foreign Keys:** user_id → users.user_id
**Attributes:**
- address_id (PK, int, auto_increment)
- user_id (FK, int)
- address_type (enum: 'home', 'work', 'other')
- address (text)
- city (varchar(100))
- postal_code (varchar(20))
- contact_number (varchar(20))
- is_default (tinyint(1))
- created_at (datetime)

### 3. staff
**Primary Key:** staff_id
**Attributes:**
- staff_id (PK, int, auto_increment)
- name (varchar(100))
- position (enum: 'manager', 'operator', 'driver', 'cashier')
- contact_number (varchar(20))
- email (varchar(100), unique)
- hire_date (date)
- salary (decimal(10,2))
- is_active (tinyint(1))
- created_at (datetime)
- updated_at (datetime)

### 4. services
**Primary Key:** service_id
**Attributes:**
- service_id (PK, int, auto_increment)
- service_name (varchar(100))
- description (text)
- base_price (decimal(10,2))
- service_type (enum: 'wash_fold', 'dry_clean', 'ironing', 'express', 'pickup_delivery')
- estimated_duration (int) - hours
- is_active (tinyint(1))
- created_at (datetime)
- updated_at (datetime)

### 5. service_items
**Primary Key:** item_id
**Foreign Keys:** service_id → services.service_id
**Attributes:**
- item_id (PK, int, auto_increment)
- item_name (varchar(100))
- category (varchar(50))
- service_id (FK, int)
- price_multiplier (decimal(3,2))
- special_instructions (text)
- is_active (tinyint(1))

### 6. machines
**Primary Key:** machine_id
**Attributes:**
- machine_id (PK, int, auto_increment)
- machine_name (varchar(100))
- machine_type (enum: 'washer', 'dryer', 'iron', 'dry_cleaner', 'steam_cleaner')
- capacity (decimal(5,2)) - kg
- status (enum: 'active', 'maintenance', 'broken', 'idle')
- location (varchar(100))
- last_maintenance (date)
- next_maintenance (date)
- created_at (datetime)

### 7. payment_methods
**Primary Key:** method_id
**Attributes:**
- method_id (PK, int, auto_increment)
- method_name (varchar(50))
- is_online (tinyint(1))
- processing_fee (decimal(5,2))
- is_active (tinyint(1))
- created_at (datetime)

### 8. promotions
**Primary Key:** promotion_id
**Attributes:**
- promotion_id (PK, int, auto_increment)
- promotion_name (varchar(100))
- description (text)
- discount_type (enum: 'percentage', 'fixed_amount', 'free_service')
- discount_value (decimal(10,2))
- start_date (date)
- end_date (date)
- min_order_amount (decimal(10,2))
- max_discount (decimal(10,2))
- usage_limit (int)
- is_active (tinyint(1))
- created_at (datetime)

### 9. inventory
**Primary Key:** inventory_id
**Attributes:**
- inventory_id (PK, int, auto_increment)
- item_name (varchar(100))
- item_type (enum: 'detergent', 'fabric_softener', 'bleach', 'stain_remover', 'other')
- brand (varchar(100))
- price (decimal(10,2))
- quantity (int)
- min_stock_level (int)
- unit (varchar(20))
- created_at (datetime)
- updated_at (datetime)

### 10. pricing
**Primary Key:** pricing_id
**Foreign Keys:** service_id → services.service_id
**Attributes:**
- pricing_id (PK, int, auto_increment)
- service_id (FK, int)
- item_name (varchar(100))
- price_per_unit (decimal(10,2))
- basket_price (decimal(10,2))
- package_a (decimal(10,2))
- package_b (decimal(10,2))
- bulk_discount (decimal(5,2))
- created_at (datetime)
- updated_at (datetime)

### 11. transactions
**Primary Key:** payment_id
**Foreign Keys:** 
- user_id → users.user_id
- staff_id → staff.staff_id
- payment_method_id → payment_methods.method_id
- promotion_id → promotions.promotion_id
**Attributes:**
- payment_id (PK, int, auto_increment)
- user_id (FK, int)
- staff_id (FK, int, nullable)
- basket_count (int)
- package (enum: 'none', 'package a', 'package b')
- detergent_qty (int)
- softener_qty (int)
- subtotal (decimal(10,2))
- discount_amount (decimal(10,2))
- total_price (decimal(10,2))
- payment_method_id (FK, int)
- payment_status (enum: 'pending', 'unpaid', 'paid', 'refunded')
- laundry_status (enum: 'pending', 'in_progress', 'washing', 'drying', 'ironing', 'ready', 'delivered', 'cancelled')
- customer_number (varchar(50))
- account_name (varchar(100))
- remarks (text)
- promotion_id (FK, int, nullable)
- estimated_completion (datetime)
- actual_completion (datetime)
- clothing_type (varchar(50))
- created_at (datetime)
- updated_at (datetime)

### 12. transaction_items
**Primary Key:** item_id
**Foreign Keys:**
- payment_id → transactions.payment_id
- service_id → services.service_id
**Attributes:**
- item_id (PK, int, auto_increment)
- payment_id (FK, int)
- service_id (FK, int)
- item_name (varchar(100))
- quantity (int)
- unit_price (decimal(10,2))
- total_price (decimal(10,2))
- special_instructions (text)
- status (enum: 'pending', 'in_progress', 'completed')

### 13. pickup_delivery
**Primary Key:** schedule_id
**Foreign Keys:**
- payment_id → transactions.payment_id
- user_id → users.user_id
- address_id → customer_addresses.address_id
- driver_id → staff.staff_id
**Attributes:**
- schedule_id (PK, int, auto_increment)
- payment_id (FK, int)
- user_id (FK, int)
- address_id (FK, int)
- type (enum: 'pickup', 'delivery')
- scheduled_date (datetime)
- actual_date (datetime)
- status (enum: 'scheduled', 'in_progress', 'completed', 'cancelled', 'failed')
- driver_id (FK, int, nullable)
- notes (text)
- fee (decimal(10,2))
- created_at (datetime)
- updated_at (datetime)

### 14. machine_usage
**Primary Key:** usage_id
**Foreign Keys:**
- machine_id → machines.machine_id
- payment_id → transactions.payment_id
- staff_id → staff.staff_id
**Attributes:**
- usage_id (PK, int, auto_increment)
- machine_id (FK, int)
- payment_id (FK, int)
- staff_id (FK, int)
- start_time (datetime)
- end_time (datetime)
- cycle_type (varchar(50))
- load_weight (decimal(5,2))
- notes (text)

### 15. ratings
**Primary Key:** rating_id
**Foreign Keys:**
- user_id → users.user_id
- payment_id → transactions.payment_id
**Attributes:**
- rating_id (PK, int, auto_increment)
- user_id (FK, int)
- payment_id (FK, int, nullable)
- rating (tinyint) - 1 to 5
- comment (text)
- created_at (datetime)

### 16. reports
**Primary Key:** report_id
**Foreign Keys:**
- user_id → users.user_id
- payment_id → transactions.payment_id
**Attributes:**
- report_id (PK, int, auto_increment)
- user_id (FK, int)
- payment_id (FK, int, nullable)
- issue (enum: 'Lost Item', 'Delayed Service', 'Quality Issue', 'Billing Problem', 'Other')
- message (text)
- admin_reply (text)
- status (enum: 'open', 'in_progress', 'resolved', 'closed')
- priority (enum: 'low', 'medium', 'high', 'urgent')
- seen (tinyint(1))
- created_at (datetime)
- updated_at (datetime)

### 17. report_messages
**Primary Key:** message_id
**Foreign Keys:** report_id → reports.report_id
**Attributes:**
- message_id (PK, int, auto_increment)
- report_id (FK, int)
- sender_id (int)
- sender_type (enum: 'user', 'admin', 'staff')
- message (text)
- timestamp (datetime)
- created_at (datetime)

### 18. messages (Legacy)
**Primary Key:** message_id
**Foreign Keys:**
- report_id → reports.report_id
- user_id → users.user_id
**Attributes:**
- message_id (PK, int, auto_increment)
- report_id (FK, int)
- user_id (FK, int)
- message (text)
- created_at (datetime)

### 19. driver_payments
**Primary Key:** payment_id
**Foreign Keys:**
- order_id → transactions.payment_id
- processed_by → users.user_id
- payment_method_id → payment_methods.method_id
**Attributes:**
- payment_id (PK, int, auto_increment)
- order_id (FK, int)
- processed_by (FK, int)
- payment_method_id (FK, int)
- amount_received (decimal(10,2))
- transaction_ref (varchar(100))
- notes (text)
- status (enum: 'completed', 'cancelled', 'refunded')
- processed_at (datetime)
- updated_at (datetime)

### 20. driver_receipts
**Primary Key:** receipt_id
**Foreign Keys:**
- order_id → transactions.payment_id
- generated_by → users.user_id
**Attributes:**
- receipt_id (PK, int, auto_increment)
- order_id (FK, int)
- generated_by (FK, int)
- status (enum: 'generated', 'printed', 'delivered', 'cancelled')
- notes (text)
- delivered_at (datetime)
- printed_at (datetime)
- created_at (datetime)
- updated_at (datetime)

### 21. customer_inventory_fabric
**Primary Key:** fabric_id
**Foreign Keys:** user_id → users.user_id
**Attributes:**
- fabric_id (PK, int, auto_increment)
- user_id (FK, int)
- fabric_name (varchar(100))
- fabric_type (enum: 'cotton', 'polyester', 'wool', 'silk', 'linen', 'denim', 'other')
- color (varchar(50))
- quantity (int)
- unit (varchar(20))
- condition_status (enum: 'new', 'good', 'fair', 'poor', 'damaged')
- special_instructions (text)
- last_wash_date (date)
- next_wash_reminder (date)
- is_active (tinyint(1))
- created_at (datetime)
- updated_at (datetime)

### 22. custom_orders
**Primary Key:** order_id
**Foreign Keys:**
- user_id → users.user_id
- fabric_id → customer_inventory_fabric.fabric_id
- payment_method_id → payment_methods.method_id
**Attributes:**
- order_id (PK, int, auto_increment)
- user_id (FK, int)
- fabric_id (FK, int)
- service_type (enum: 'wash', 'dry_clean')
- soap_type (enum: 'tide', 'downy', 'clorox', 'oxiclean')
- ironing (tinyint(1))
- express (tinyint(1))
- special_instructions (text)
- subtotal (decimal(10,2))
- payment_method_id (FK, int)
- payment_status (enum: 'pending', 'unpaid', 'paid', 'refunded')
- laundry_status (enum: 'pending', 'in_progress', 'washing', 'drying', 'ironing', 'ready', 'delivered', 'cancelled')
- estimated_completion (datetime)
- actual_completion (datetime)
- created_at (datetime)
- updated_at (datetime)

### 23. admin_fabrics
**Primary Key:** fabric_id
**Attributes:**
- fabric_id (PK, int, auto_increment)
- fabric_name (varchar(100))
- fabric_type (enum: 'cotton', 'polyester', 'wool', 'silk', 'linen', 'denim', 'leather', 'synthetic', 'other')
- price_multiplier (decimal(3,2))
- wash_temperature (enum: 'cold', 'warm', 'hot', 'hand_wash', 'dry_clean')
- description (text)
- care_instructions (text)
- processing_time (int) - hours
- is_popular (tinyint(1))
- is_active (tinyint(1))
- created_at (datetime)
- updated_at (datetime)

---

## RELATIONSHIPS

### One-to-Many Relationships:

1. **users** → **customer_addresses** (1:N)
   - One user can have many addresses
   - Foreign Key: customer_addresses.user_id → users.user_id (CASCADE)

2. **users** → **transactions** (1:N)
   - One user can have many transactions
   - Foreign Key: transactions.user_id → users.user_id (CASCADE)

3. **users** → **ratings** (1:N)
   - One user can give many ratings
   - Foreign Key: ratings.user_id → users.user_id (CASCADE)

4. **users** → **reports** (1:N)
   - One user can create many reports
   - Foreign Key: reports.user_id → users.user_id (CASCADE)

5. **users** → **customer_inventory_fabric** (1:N)
   - One user can have many fabric items
   - Foreign Key: customer_inventory_fabric.user_id → users.user_id (CASCADE)

6. **users** → **custom_orders** (1:N)
   - One user can create many custom orders
   - Foreign Key: custom_orders.user_id → users.user_id (CASCADE)

7. **users** → **driver_payments** (1:N)
   - One driver can process many payments
   - Foreign Key: driver_payments.processed_by → users.user_id (CASCADE)

8. **users** → **driver_receipts** (1:N)
   - One driver can generate many receipts
   - Foreign Key: driver_receipts.generated_by → users.user_id

9. **staff** → **transactions** (1:N)
   - One staff member can handle many transactions
   - Foreign Key: transactions.staff_id → staff.staff_id (SET NULL)

10. **staff** → **pickup_delivery** (1:N)
    - One driver can handle many pickups/deliveries
    - Foreign Key: pickup_delivery.driver_id → staff.staff_id (SET NULL)

11. **staff** → **machine_usage** (1:N)
    - One staff member can use machines for many orders
    - Foreign Key: machine_usage.staff_id → staff.staff_id

12. **services** → **service_items** (1:N)
    - One service can have many items
    - Foreign Key: service_items.service_id → services.service_id (CASCADE)

13. **services** → **pricing** (1:N)
    - One service can have many pricing options
    - Foreign Key: pricing.service_id → services.service_id (CASCADE)

14. **services** → **transaction_items** (1:N)
    - One service can be in many transaction items
    - Foreign Key: transaction_items.service_id → services.service_id

15. **transactions** → **transaction_items** (1:N)
    - One transaction can have many items
    - Foreign Key: transaction_items.payment_id → transactions.payment_id (CASCADE)

16. **transactions** → **pickup_delivery** (1:N)
    - One transaction can have many pickup/delivery schedules
    - Foreign Key: pickup_delivery.payment_id → transactions.payment_id (CASCADE)

17. **transactions** → **machine_usage** (1:N)
    - One transaction can use many machines
    - Foreign Key: machine_usage.payment_id → transactions.payment_id (CASCADE)

18. **transactions** → **ratings** (1:N)
    - One transaction can have many ratings (optional)
    - Foreign Key: ratings.payment_id → transactions.payment_id (SET NULL)

19. **transactions** → **reports** (1:N)
    - One transaction can have many reports (optional)
    - Foreign Key: reports.payment_id → transactions.payment_id (SET NULL)

20. **transactions** → **driver_payments** (1:N)
    - One transaction can have many driver payment records
    - Foreign Key: driver_payments.order_id → transactions.payment_id (CASCADE)

21. **transactions** → **driver_receipts** (1:N)
    - One transaction can have many receipts
    - Foreign Key: driver_receipts.order_id → transactions.payment_id

22. **customer_addresses** → **pickup_delivery** (1:N)
    - One address can be used for many pickups/deliveries
    - Foreign Key: pickup_delivery.address_id → customer_addresses.address_id

23. **machines** → **machine_usage** (1:N)
    - One machine can be used for many orders
    - Foreign Key: machine_usage.machine_id → machines.machine_id

24. **payment_methods** → **transactions** (1:N)
    - One payment method can be used in many transactions
    - Foreign Key: transactions.payment_method_id → payment_methods.method_id

25. **payment_methods** → **custom_orders** (1:N)
    - One payment method can be used in many custom orders
    - Foreign Key: custom_orders.payment_method_id → payment_methods.method_id

26. **payment_methods** → **driver_payments** (1:N)
    - One payment method can be used in many driver payments
    - Foreign Key: driver_payments.payment_method_id → payment_methods.method_id

27. **promotions** → **transactions** (1:N)
    - One promotion can be applied to many transactions (optional)
    - Foreign Key: transactions.promotion_id → promotions.promotion_id (SET NULL)

28. **reports** → **report_messages** (1:N)
    - One report can have many messages
    - Foreign Key: report_messages.report_id → reports.report_id (CASCADE)

29. **reports** → **messages** (1:N) [Legacy]
    - One report can have many legacy messages
    - Foreign Key: messages.report_id → reports.report_id

30. **customer_inventory_fabric** → **custom_orders** (1:N)
    - One fabric item can be used in many custom orders
    - Foreign Key: custom_orders.fabric_id → customer_inventory_fabric.fabric_id (CASCADE)

---

## LUCIDCHART IMPORT INSTRUCTIONS

### Option 1: Manual Creation
1. Open Lucidchart
2. Create a new ERD diagram
3. Add each entity as a table shape
4. Add all attributes listed above
5. Connect relationships using the foreign key relationships listed
6. Set cardinality: 1 (one) to N (many) based on relationships above

### Option 2: Using CSV Import
1. Create a CSV file with columns: Entity, Attribute, Type, Primary Key, Foreign Key, Relationship
2. Import into Lucidchart using the database import feature

### Option 3: SQL Import
1. Use the SQL schema file: `database-schema/laundry_updated_schema.sql`
2. Lucidchart supports direct SQL import in some plans
3. Go to: File → Import → Database → MySQL

---

## KEY RELATIONSHIP SUMMARY

**Core Transaction Flow:**
- users → transactions → transaction_items → services
- transactions → payment_methods
- transactions → promotions (optional)
- transactions → staff (optional)

**Delivery Flow:**
- transactions → pickup_delivery → customer_addresses
- pickup_delivery → staff (driver)

**Machine Operations:**
- transactions → machine_usage → machines
- machine_usage → staff

**Customer Features:**
- users → customer_inventory_fabric → custom_orders
- users → ratings → transactions
- users → reports → transactions

**Driver Operations:**
- users (driver) → driver_payments → transactions
- users (driver) → driver_receipts → transactions

---

## COLOR CODING SUGGESTION

- **Blue:** Core entities (users, transactions, services)
- **Green:** Supporting entities (staff, machines, inventory)
- **Orange:** Transaction-related (transaction_items, driver_payments, driver_receipts)
- **Purple:** Customer features (customer_addresses, customer_inventory_fabric, custom_orders)
- **Yellow:** Reference data (payment_methods, promotions, admin_fabrics)
- **Gray:** System entities (reports, ratings, messages)

---

## NOTES

- All foreign keys with CASCADE will delete related records when parent is deleted
- All foreign keys with SET NULL will set the FK to NULL when parent is deleted
- Some relationships are optional (nullable foreign keys)
- The system supports both regular orders (transactions) and custom orders (custom_orders)
- Driver operations are tracked separately in driver_payments and driver_receipts

