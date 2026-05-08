-- Fix for missing columns in orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS transfer_image VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS transfer_type ENUM('full', 'partial') DEFAULT 'full',
ADD COLUMN IF NOT EXISTS transfer_amount DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS shipping_cost DECIMAL(10,2) DEFAULT 0.00;

-- Fix for missing stock column in product_variants
ALTER TABLE product_variants 
ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0;

-- Fix for missing admin_notes column in orders
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS admin_notes TEXT DEFAULT NULL;

-- Fix for missing address_landmark column if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS address_landmark VARCHAR(255) DEFAULT NULL;

-- Fix for missing city column if not exists
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL;
