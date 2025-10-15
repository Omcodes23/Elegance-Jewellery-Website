-- Create and select the database
CREATE DATABASE IF NOT EXISTS elegance_jewelry;
USE elegance_jewelry;

-- Products table
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  old_price DECIMAL(10,2),
  category VARCHAR(50) NOT NULL,
  image VARCHAR(255) NOT NULL,
  stock INT NOT NULL DEFAULT 10,
  sku VARCHAR(50),
  featured TINYINT(1) DEFAULT 0,
  new TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  address TEXT,
  city VARCHAR(50),
  state VARCHAR(50),
  zip VARCHAR(20),
  country VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT,
  total_amount DECIMAL(10,2) NOT NULL,
  shipping_address TEXT NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Processing',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT,
  product_name VARCHAR(255) NOT NULL,
  product_price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  image VARCHAR(255)
);

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert dummy admin user
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$i3N9qgHJDtIhyb7m6UOQne0D4KpKTdOk93fv3YiPW0jveNNHtKJ4G', 'admin@example.com');
-- Password: admin123 (hashed)

-- Insert sample categories
INSERT INTO categories (name, description, image) VALUES
('rings', 'Beautiful rings for every occasion', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600'),
('necklaces', 'Elegant necklaces to complement any outfit', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600'),
('earrings', 'Stunning earrings from simple studs to elaborate designs', 'https://images.unsplash.com/photo-1635767798638-3665a167adc2?w=600'),
('bracelets', 'Delicate and statement bracelets for your wrist', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600');

-- Insert sample products
INSERT INTO products (name, description, price, old_price, category, image, stock, sku, featured, new) VALUES
('Diamond Eternity Ring', 'This stunning Diamond Eternity Ring features brilliant-cut diamonds set in 18K white gold. The diamonds are expertly set to maximize brilliance and fire, creating a ring that truly sparkles from every angle.', 1999.99, 2499.99, 'rings', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600', 10, 'RNG-001', 1, 0),
('Sapphire Pendant Necklace', 'An exquisite sapphire pendant necklace featuring a deep blue natural sapphire surrounded by a halo of diamonds. The pendant hangs from a delicate 18K white gold chain.', 1299.99, 1599.99, 'necklaces', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600', 15, 'NCK-001', 1, 1),
('Pearl Drop Earrings', 'Elegant pearl drop earrings featuring lustrous freshwater pearls suspended from 14K gold posts. These timeless earrings add a touch of sophistication to any outfit.', 899.99, 1199.99, 'earrings', 'https://images.unsplash.com/photo-1635767798638-3665a167adc2?w=600', 20, 'EAR-001', 1, 0),
('Gold Tennis Bracelet', 'A classic 18K gold tennis bracelet featuring a continuous line of round brilliant diamonds. This bracelet catches the light beautifully and is secured with a double safety clasp.', 2499.99, 2999.99, 'bracelets', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=600', 8, 'BRC-001', 1, 0),
('Emerald Halo Ring', 'A stunning emerald halo ring featuring a vibrant green natural emerald surrounded by a circle of sparkling diamonds. Set in 18K white gold with a split shank design.', 1799.99, 2199.99, 'rings', 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=600', 12, 'RNG-002', 0, 1),
('Ruby Stud Earrings', 'Beautiful ruby stud earrings featuring rich red natural rubies set in 14K yellow gold. These earrings are secured with comfortable push backs.', 1299.99, 1499.99, 'earrings', 'https://images.unsplash.com/photo-1635767798638-3665a167adc2?w=600', 15, 'EAR-002', 0, 1),
('Platinum Chain Necklace', 'A sleek and modern platinum chain necklace with a contemporary link design. This versatile piece works well on its own or paired with a pendant.', 1599.99, 1899.99, 'necklaces', 'https://images.unsplash.com/photo-1599643477877-530eb83abc8e?w=600', 10, 'NCK-002', 0, 1),
('Diamond Charm Bracelet', 'A delightful charm bracelet in 18K white gold featuring various diamond-set charms including a heart, star, and moon. Each charm moves freely along the bracelet.', 1899.99, 2299.99, 'bracelets', 'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?w=600', 8, 'BRC-002', 0, 1);

-- Sample customers for testing
INSERT INTO customers (first_name, last_name, email, password, phone, address, city, state, zip, country) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$Lrs0pRodCwPXzvuHSJV2.eZrgFQd4QDeM/5d4NsJ.YYvhOWrnpqBu', '555-123-4567', '123 Main St', 'New York', 'NY', '10001', 'United States'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$Lrs0pRodCwPXzvuHSJV2.eZrgFQd4QDeM/5d4NsJ.YYvhOWrnpqBu', '555-987-6543', '456 Oak Ave', 'Los Angeles', 'CA', '90001', 'United States');
-- Password: password123 (hashed)

-- Sample orders
INSERT INTO orders (order_number, customer_id, total_amount, shipping_address, payment_method, status) VALUES
('ORD-458721', 1, 2499.99, 'John Doe, 123 Main St, New York, NY, 10001, United States', 'Credit Card', 'Completed'),
('ORD-458720', 2, 1299.99, 'Jane Smith, 456 Oak Ave, Los Angeles, CA, 90001, United States', 'PayPal', 'Processing');

-- Sample order items
INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity) VALUES
(1, 4, 'Gold Tennis Bracelet', 2499.99, 1),
(2, 2, 'Sapphire Pendant Necklace', 1299.99, 1);