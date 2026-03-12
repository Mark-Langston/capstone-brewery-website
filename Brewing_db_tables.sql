-- Create Database
CREATE DATABASE IF NOT EXISTS main_channel_brewing;
USE main_channel_brewing;

-- 1. Beers Table: Supports seasonal rotation, descriptions, and tap status 
CREATE TABLE beers (
    beer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    style VARCHAR(50),
    abv DECIMAL(4,2), -- Alcohol by Volume
    ibu INT,          -- International Bitterness Units (Hoppiness) [cite: 230]
    image_url VARCHAR(255),
    is_on_tap BOOLEAN DEFAULT TRUE,
    is_seasonal BOOLEAN DEFAULT FALSE,
    availability_status ENUM('In Stock', 'Out of Stock') DEFAULT 'In Stock', 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Merchandise Table: Supports photos, price, and online viewing [cite: 24, 30]
CREATE TABLE merchandise (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    category VARCHAR(50), -- e.g., Apparel, Glassware
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Events Table: Supports weekly recurring and one-time events [cite: 25, 31]
CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE,
    start_time TIME,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(50), -- e.g., 'Weekly', 'Monthly'
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Specials and Promotions: For kitchen specials and happy hour [cite: 26, 32]
CREATE TABLE specials (
    special_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price_info VARCHAR(100),
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    type ENUM('Kitchen', 'Happy Hour', 'Limited Time') NOT NULL
);

-- 5. Retail Locations: For the "Where to Buy" page [cite: 27, 33]
CREATE TABLE retail_locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(150) NOT NULL,
    city VARCHAR(100), -- e.g., Huntsville, Birmingham [cite: 27]
    address TEXT,
    website_url VARCHAR(255),
    last_verified DATE
);

-- 6. Employees Table: For the "About Us" page updates [cite: 20, 227]
CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    position VARCHAR(100),
    bio TEXT,
    photo_url VARCHAR(255),
    hire_date DATE
);

-- 7. Audit Log: To track updates by staff [cite: 213]
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    action_performed TEXT NOT NULL,
    performed_by VARCHAR(100),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);