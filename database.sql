-- Database Schema for Provision Store POS (Secure & Optimized)

-- 1. Users Table (Core Auth)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) CHECK (role IN ('admin', 'staff')) NOT NULL DEFAULT 'admin',
    status VARCHAR(20) CHECK (status IN ('active', 'inactive')) DEFAULT 'active',
    force_password_change BOOLEAN DEFAULT FALSE, -- Renamed from force_password_reset to match request
    owner_id INTEGER REFERENCES users(id) ON DELETE CASCADE, -- Hierarchical ownership (Staff -> Admin)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. User Profiles (Extended Info)
CREATE TABLE user_profiles (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(100),
    shop_name VARCHAR(150),
    phone VARCHAR(20),
    address TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Login History (Audit Trail)
CREATE TABLE login_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT
);

-- Index for faster lookup of login history
CREATE INDEX idx_login_history_user_id ON login_history(user_id);

-- 4. Customers (Shop Specific)
CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    total_due DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Products (Inventory)
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(50),
    price DECIMAL(10, 2) NOT NULL,
    weight VARCHAR(20),
    stock INTEGER DEFAULT 0,
    barcode VARCHAR(100),
    barcode_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, barcode)
);

-- 6. Sales (Transactions)
CREATE TABLE sales (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    customer_id INTEGER REFERENCES customers(id) ON DELETE SET NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_type VARCHAR(20) CHECK (payment_type IN ('pay_now', 'khata')),
    status VARCHAR(20) CHECK (status IN ('paid', 'pending')),
    sale_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Sale Items
CREATE TABLE sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
    quantity INTEGER NOT NULL,
    price_at_sale DECIMAL(10, 2) NOT NULL
);

-- 8. Khata Payments (Money Received)
CREATE TABLE khata_payments (
    id SERIAL PRIMARY KEY,
    customer_id INTEGER REFERENCES customers(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL,
    payment_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for Performance
CREATE INDEX idx_products_user_barcode ON products(user_id, barcode);
CREATE INDEX idx_customers_user ON customers(user_id);
CREATE INDEX idx_sales_user ON sales(user_id);
CREATE INDEX idx_payments_user_customer ON khata_payments(user_id, customer_id);
