USE nook32_pos;

-- Insert categories if not present
INSERT IGNORE INTO categories (category_id, category_name) VALUES
(1, 'Espresso & Hot Coffee'),
(2, 'Iced Coffee'),
(3, 'Frappe & Blended'),
(4, 'Non-Coffee & Tea'),
(5, 'Pastries & Snacks');

-- Insert products
INSERT IGNORE INTO products (product_id, category_id, product_name, price, availability) VALUES
(1, 1, 'Espresso Solo', 75.00, 'available'),
(2, 1, 'Americano Hot', 90.00, 'available'),
(3, 1, 'Café Latte Hot', 110.00, 'available'),
(4, 1, 'Cappuccino Hot', 110.00, 'available'),
(5, 2, 'Iced Americano', 100.00, 'available'),
(6, 2, 'Iced Spanish Latte', 130.00, 'available'),
(7, 2, 'Iced Caramel Macchiato', 140.00, 'available'),
(8, 2, 'Iced Vanilla Latte', 130.00, 'available'),
(9, 3, 'Mocha Frappe', 150.00, 'available'),
(10, 3, 'Caramel Frappe', 150.00, 'available'),
(11, 4, 'Matcha Green Tea Latte', 135.00, 'available'),
(12, 5, 'Butter Croissant', 85.00, 'available'),
(13, 5, 'Chocolate Chip Cookie', 65.00, 'available');

-- Insert inventory ingredients
INSERT IGNORE INTO inventory (inventory_id, item_name, quantity, unit, low_stock_limit) VALUES
(1, 'Coffee Beans', 5000.000, 'g', 1000.000),
(2, 'Fresh Milk', 25000.000, 'ml', 3000.000),
(3, 'Condensed Milk', 5000.000, 'ml', 1000.000),
(4, 'Caramel Sauce', 3000.000, 'ml', 500.000),
(5, 'Vanilla Syrup', 3000.000, 'ml', 500.000),
(6, 'Mocha Powder', 2500.000, 'g', 500.000),
(7, 'Matcha Powder', 2000.000, 'g', 300.000),
(8, 'Butter Croissant Dough', 50.000, 'pcs', 10.000),
(9, 'Choco Chip Dough', 60.000, 'pcs', 10.000),
(10, 'Paper Cups & Lids', 500.000, 'pcs', 50.000);

-- Insert product recipes
INSERT IGNORE INTO product_recipes (recipe_id, product_id, recipe_name) VALUES
(1, 1, 'Espresso Solo Recipe'),
(2, 2, 'Americano Hot Recipe'),
(3, 3, 'Café Latte Hot Recipe'),
(4, 4, 'Cappuccino Hot Recipe'),
(5, 5, 'Iced Americano Recipe'),
(6, 6, 'Iced Spanish Latte Recipe'),
(7, 7, 'Iced Caramel Macchiato Recipe'),
(8, 8, 'Iced Vanilla Latte Recipe'),
(9, 9, 'Mocha Frappe Recipe'),
(10, 10, 'Caramel Frappe Recipe'),
(11, 11, 'Matcha Green Tea Latte Recipe'),
(12, 12, 'Butter Croissant Recipe'),
(13, 13, 'Chocolate Chip Cookie Recipe');

-- Insert recipe ingredients
INSERT IGNORE INTO recipe_ingredients (recipe_ingredient_id, recipe_id, inventory_id, quantity_required) VALUES
-- Espresso: 18g beans, 1 cup
(1, 1, 1, 18.000),
(2, 1, 10, 1.000),
-- Americano: 18g beans, 1 cup
(3, 2, 1, 18.000),
(4, 2, 10, 1.000),
-- Café Latte Hot: 18g beans, 200ml fresh milk, 1 cup
(5, 3, 1, 18.000),
(6, 3, 2, 200.000),
(7, 3, 10, 1.000),
-- Cappuccino Hot: 18g beans, 150ml fresh milk, 1 cup
(8, 4, 1, 18.000),
(9, 4, 2, 150.000),
(10, 4, 10, 1.000),
-- Iced Americano: 18g beans, 1 cup
(11, 5, 1, 18.000),
(12, 5, 10, 1.000),
-- Iced Spanish Latte: 18g beans, 150ml fresh milk, 30ml condensed milk, 1 cup
(13, 6, 1, 18.000),
(14, 6, 2, 150.000),
(15, 6, 3, 30.000),
(16, 6, 10, 1.000),
-- Iced Caramel Macchiato: 18g beans, 150ml milk, 20ml caramel sauce, 1 cup
(17, 7, 1, 18.000),
(18, 7, 2, 150.000),
(19, 7, 4, 20.000),
(20, 7, 10, 1.000),
-- Iced Vanilla Latte: 18g beans, 150ml milk, 20ml vanilla syrup, 1 cup
(21, 8, 1, 18.000),
(22, 8, 2, 150.000),
(23, 8, 5, 20.000),
(24, 8, 10, 1.000),
-- Mocha Frappe: 18g beans, 150ml milk, 25g mocha powder, 1 cup
(25, 9, 1, 18.000),
(26, 9, 2, 150.000),
(27, 9, 6, 25.000),
(28, 9, 10, 1.000),
-- Caramel Frappe: 18g beans, 150ml milk, 30ml caramel, 1 cup
(29, 10, 1, 18.000),
(30, 10, 2, 150.000),
(31, 10, 4, 30.000),
(32, 10, 10, 1.000),
-- Matcha Latte: 15g matcha powder, 200ml milk, 1 cup
(33, 11, 7, 15.000),
(34, 11, 2, 200.000),
(35, 11, 10, 1.000),
-- Croissant: 1 croissant dough
(36, 12, 8, 1.000),
-- Cookie: 1 cookie dough
(37, 13, 9, 1.000);

