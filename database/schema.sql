-- Intentionally imperfect schema
-- Inconsistent naming, mixed casing, missing FKs

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    username VARCHAR(50) NOT NULL,
    Full_Name VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS products;
CREATE TABLE products (
    product_id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(255),
    slug VARCHAR(255),
    description TEXT,
    price DECIMAL(10,2),
    image_path VARCHAR(255),
    file_path VARCHAR(255),
    is_active TINYINT DEFAULT 1
    -- Missing foreign key constraint strictly enforcing user existence
);

DROP TABLE IF EXISTS Carts;  -- Mixed case table name
CREATE TABLE Carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(255),
    product_id INTEGER,
    quantity INTEGER DEFAULT 1,
    created_at DATETIME
);

DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    total_amount DECIMAL(10,2),
    payment_provider VARCHAR(50),
    payment_status VARCHAR(20),
    transaction_id VARCHAR(100),
    order_date DATETIME
);

DROP TABLE IF EXISTS payment_providers;
CREATE TABLE payment_providers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    provider_name VARCHAR(50),
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    is_enabled TINYINT
);
