# 🛒 Provision Store POS System

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.0-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Platform](https://img.shields.io/badge/Platform-Windows-0078D6?style=for-the-badge&logo=windows&logoColor=white)](https://microsoft.com/windows)

A modern, lightweight, and highly efficient **Point of Sale (POS), Inventory, and Khata (Credit / Customer Wallet) Management System** tailored specifically for provision stores, retail shops, and small businesses. Developed in **PHP** with support for both **MySQL (MariaDB)** and **PostgreSQL** databases.

---

## 🌟 Key Features

*   **⚡ Real-Time POS Billing:** Fast billing interface supporting barcode scanning, instant search, and real-time total calculation.
*   **📦 Inventory & Stock Control:** Add, update, and track products, categories, cost price, sale price, weight, stock quantity, and expiry dates.
*   **📓 Customer Khata (Credit Ledger):** Digital Khata system to keep record of customer dues, credit limits, and historical payments.
*   **💳 Customer Digital Wallet:** Allow deposits, purchase credits, and refunds with transaction logs.
*   **👥 Staff & Role Management:** Secure hierarchical ownership model (Admin vs. Staff roles) with distinct permissions.
*   **📊 Rich Analytical Reports:** Insightful dashboard for daily sales, revenue tracking, profit margins, and top-selling products.
*   **💾 Data Import & Export:** Bulk import/export shop data in CSV/SQL formats.
*   **🔒 Security & Audits:** Password reset/force change, secure bcrypt hashing, session management, and login audit trails.

---

## 📁 Directory Structure

```text
pos/
├── ajax/                   # Ajax requests handling scripts
├── assets/                 # CSS/JS and client-side resources
├── config/
│   └── db.php              # Database connection configuration
├── includes/               # Reusable headers, footers, & components
├── about.php               # About system page
├── customers.php           # Customer management interface
├── inventory.php           # Product & stock management
├── khata.php               # Khata ledger & transaction tracker
├── login.php               # Secure login portal
├── pos.php                 # Core billing terminal
├── register.php            # Admin register portal
├── reports.php             # Sales and analytic reports
├── run_pos.bat             # Batch script to boot app and database
├── start_app.bat           # Enhanced local server launcher
├── database.sql            # PostgreSQL database schema
└── mysql_database.sql      # MySQL database schema
```

---

## ⚙️ Installation & Setup

### 1. Prerequisites
Ensure you have the following installed on your machine:
*   **PHP (v8.0 or higher)**
*   **XAMPP / WampServer** (for MySQL) OR **PostgreSQL (v12 or higher)**
*   A modern web browser

### 2. Database Setup

#### Option A: MySQL / MariaDB (Recommended for XAMPP)
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a database named `pos_db`.
3. Import `mysql_database.sql` into `pos_db` using the **Import** tab.
4. Set up credentials in `config/db.php`:
   ```php
   $host = 'localhost';
   $dbname = 'pos_db';
   $user = 'root';      // Or your database username
   $password = '';      // Or your database password
   ```

#### Option B: PostgreSQL Setup
1. Create a PostgreSQL database named `pos_db`.
2. Execute the `database.sql` script to load the schema:
   ```bash
   psql -U postgres -d pos_db -f database.sql
   ```
3. Update `config/db.php` to use the `pgsql` DSN:
   ```php
   $dsn = "pgsql:host=$host;dbname=$dbname";
   ```

### 3. Enable Database Extensions in PHP
If database connection errors occur, ensure the respective drivers are enabled:
1. Open your `php.ini` file.
2. Uncomment the following lines (remove the leading `;`):
   *   For MySQL: `extension=pdo_mysql`
   *   For PostgreSQL: `extension=pdo_pgsql` and `extension=pgsql`
3. Restart your web server.

---

## 🚀 How to Run the App

We have provided convenient automation scripts to get you running in one click on Windows:

1.  Simply double-click **`start_app.bat`** (or **`run_pos.bat`**).
    *   *This will auto-start MySQL (if using XAMPP), launch the local PHP development server in the background, and open your default browser to the POS page.*
2.  Alternatively, start the server manually via terminal:
    ```bash
    php -S localhost:8000
    ```
    Then open **[http://localhost:8000](http://localhost:8000)** in your browser.

---

## 🔑 Default Credentials

On setup, you can register a new admin account or login using default credentials (if seeded):
*   **Default Username:** `admin`
*   **Default Password:** `admin123`
*(Note: You will be prompted to change default credentials upon first login for safety).*

---

## 🛡️ License & Contributing
Feel free to open issues or submit pull requests to make this POS system even better.
