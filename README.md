# Nook32 Coffee POS System

A modern, artisan Point of Sale (POS) and inventory management web application built for boutique coffee shops and cafes. Features a warm Purr'Coffee design language, split-screen authentication, atomic inventory deduction, and real-time sales reporting.

---

## Features

### ☕ Artisan POS Register
- **3-Column Register Layout**: Category filtering (Coffee, Non-Coffee, Food, Pastries), visual product catalog with live stock badges, dynamic cart sidebar, and instant receipt drawer.
- **Dynamic Order Types**: Supports Dine-In, Takeaway, and Delivery orders.
- **Multiple Payment Gateways**: Cash, Card, and digital QR payments (GCash / Maya) with auto-verification.
- **Real-Time Calculation**: Subtotal, service charges, discounts, and tax computation.

### 📦 Ingredient & Stock Control
- **Atomic Stock Deductions**: Automated deduction of raw ingredients (coffee beans, syrups, fresh milk, matcha powder) upon checkout.
- **Low-Stock Alerts**: Proactive visual indicators and checkout blocking when stock falls below safety thresholds.
- **Inventory Adjustment Auditing**: Dedicated interface for managers to restock and audit unit measurements.

### 📊 Executive Analytics & Reporting
- **Business Dashboard**: Real-time gross sales, net revenue, total transaction counts, and average order values.
- **Top Sellers**: High-performing menu items and customer preference analytics.
- **Order History**: Searchable, filterable audit trail of all historical orders with reprint receipt capabilities.

### 🛡️ Enterprise Security & Access Control
- **Role-Based Access Control (RBAC)**: Distinct permission tiers for `admin` and `cashier`.
- **Split-Screen Authentication**: Elegant, distraction-free portal with quick switching between cashier and management consoles.
- **Security Hardening**: Parameterized SQL queries (zero SQL injection), session hijacking defenses, and CSRF token protection on sensitive operations.

---

## Tech Stack
- **Backend**: PHP 8.1+ (Object-Oriented, PDO / MySQLi Prepared Statements)
- **Database**: MySQL 8.0+ / MariaDB 10.4+ (InnoDB Engine with Foreign Keys and Transactions)
- **Frontend**: Vanilla JavaScript (ES6+), Bootstrap 5.3, Custom Purr'Coffee CSS Design System
- **Icons**: Lucide & Bootstrap Icons

---

## Directory Structure

```text
nook32_pos/
├── assets/
│   └── css/
│       └── purr_theme.css          # Purr'Coffee unified aesthetic design system
├── classes/
│   ├── Database.php                # Database connection singleton
│   ├── InventoryManager.php        # Stock management and ingredient tracking
│   ├── Order.php                   # Order domain entity
│   ├── OrderManager.php            # Order creation & atomic transaction handling
│   ├── Product.php                 # Product entity model
│   ├── ProductManager.php          # Menu catalog & category retrieval
│   ├── TestDatabase.php            # Test environment database helper
│   └── User.php                    # User account & credential validation
├── config/
│   ├── database.php.example        # Database config template
│   └── test_database.php.example   # Test database config template
├── includes/
│   └── sidebar.php                 # Global navigation bar & staff user profile
├── pages/
│   └── index.php                   # Fallback index directory guard
├── add_user.php                    # User account creation interface
├── auth.php                        # Session guard & authorization helper
├── check.php                       # Server environment diagnostics
├── checkout.php                    # Transactional checkout API endpoint
├── checkout_page.php               # Standalone receipt & checkout interface
├── composer.json                   # Dependency definition
├── dashboard.php                   # Executive analytics & charts
├── delete_user.php                 # User removal handler
├── edit_user.php                   # Account configuration view
├── index.php                       # Main POS Register interface
├── inventory.php                   # Stock & ingredient management portal
├── login.php                       # Split-screen responsive authentication
├── logout.php                      # Session destruction & sign-out
├── manage_users.php                # Admin user directory
├── order_history.php               # Searchable order transaction logs
├── reset_password.php              # Administrative password reset tool
├── seed_data.sql                   # Initial menu items and ingredient seeds
├── setup_database.sql              # Database schema definitions
└── verify_qr_payment.php           # QR transaction verification endpoint
```

---

## Getting Started

### 1. Prerequisites
- PHP 8.1 or higher (with `mysqli`, `pdo_mysql`, `mbstring`, `json` extensions enabled)
- MySQL / MariaDB server (e.g., XAMPP, WampServer, or Docker)
- Web server: Apache / Nginx / PHP built-in server

### 2. Installation
1. Clone this repository:
   ```bash
   git clone https://github.com/Theshy691/Nook32.git
   cd Nook32
   ```

2. Copy the database configuration template:
   ```bash
   cp config/database.php.example config/database.php
   ```

3. Update `config/database.php` with your local database credentials:
   ```php
   $host = "localhost";
   $username = "root";
   $password = "your_mysql_password";
   $database = "nook32_pos";
   ```

### 3. Database Setup
Import the schema and initial seed data into MySQL:
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS nook32_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p nook32_pos < setup_database.sql
mysql -u root -p nook32_pos < seed_data.sql
```

### 4. Provision Initial Accounts
Create an initial administrator and cashier by executing a quick PHP CLI script or inserting a hashed password:
```php
$hash = password_hash('YourSecurePassword', PASSWORD_DEFAULT);
// INSERT INTO users (full_name, username, password, role) VALUES ('Admin', 'admin', '$hash', 'admin');
```

### 5. Running the Application
If using PHP's built-in server:
```bash
php -S localhost:8000
```
Open [http://localhost:8000/login.php](http://localhost:8000/login.php) in your browser.

---

## Testing

The system includes automated test suites to verify inventory deductions and race conditions:
```bash
php test_connection.php
php test_inventory.php
php test_inventory_deduction.php
php test_duplicate_deduction.php
php test_insufficient_inventory.php
php test_qr_payment.php
php test_verify_qr_payment.php
```

---

## License
MIT License. Crafted for Nook32 Coffee.
