CREATE DATABASE IF NOT EXISTS visitplanner;
USE visitplanner;

CREATE USER IF NOT EXISTS 'webapp'@'localhost' IDENTIFIED BY '*kmEz4ffUH!yNj1A';
GRANT ALL PRIVILEGES ON `visitplanner`.* TO 'webapp'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE IF NOT EXISTS users (
    user_id              INT AUTO_INCREMENT PRIMARY KEY,
    username             VARCHAR(255) UNIQUE,
    first_name           VARCHAR(50) NOT NULL,
    last_name            VARCHAR(50),
    password_hash        VARCHAR(255) NOT NULL,
    user_type            VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS category (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS place (
    place_id           INT AUTO_INCREMENT PRIMARY KEY,
    place_name         VARCHAR(255) NOT NULL,
    place_description  TEXT NOT NULL,
    opening_time       VARCHAR(255) DEFAULT 'Not Applicable',
    travel_tip         VARCHAR(255),
    distance_km        DECIMAL(5,2),
    latitude           DECIMAL(10,7),
    longitude          DECIMAL(10,7),
    image_path         VARCHAR(255),
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    category_id        INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES category(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS travel_tip (
    tip_id       INT AUTO_INCREMENT PRIMARY KEY,
    place_id     INT NOT NULL,
    tip_category VARCHAR(50) NOT NULL,
    tip_text     TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (place_id) REFERENCES place(place_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS visit_plan (
    place_id     INT NOT NULL,
    user_id      INT NOT NULL,
    PRIMARY KEY (place_id, user_id),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (place_id) REFERENCES place(place_id) ON DELETE CASCADE
);

-- =========================================================
-- Categories
-- =========================================================
INSERT INTO category (category_name) VALUES
    ('Nature'),
    ('Religious'),
    ('Heritage'),
    ('Cultural'),
    ('Food');

-- =========================================================
-- Places of Interest
-- Using subqueries for category_id so this doesn't depend on
-- categories being auto-assigned IDs 1-5 in a specific order.
-- =========================================================
INSERT INTO place (place_name, place_description, opening_time, travel_tip, distance_km, latitude, longitude, image_path, category_id)
VALUES
('Kumara Ella',
 'A scenic waterfall in Puwakpitiya, Avissawella, known for its natural pools.',
 'Not applicable (outdoor site)',
 'Free to visit',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Nature')),

('Water World',
 "Sri Lanka's largest aquarium in Kelaniya, home to a variety of fish and bird species.",
 'Confirm with waterworld.lk',
 'Entry fee applies - check current pricing',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Cultural')),

('Nissarana Vanaya, Alms Hall',
 'A forest monastery offering a peaceful meditation environment and alms hall.',
 'Not applicable (outdoor/temple site)',
 'Free to visit; dress modestly',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Religious')),

('Wasala Banquets and Nature Resort',
 'A nature resort offering scenic surroundings and recreational activities.',
 'TBD - confirm on-site',
 'Best visited in the morning',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Nature')),

('Keragala Pathmawathi Pirivena',
 'A Buddhist monastic education center of religious significance.',
 'Not applicable (temple site)',
 'Free to visit; dress modestly',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Religious')),

('Pilikuttuwa Raja Maha Viharaya',
 'A historic temple in Yakkala, Gampaha, known for its ancient cave shrines.',
 'Not applicable (temple site)',
 'Free to visit; dress modestly',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Heritage')),

('Ranwala Thotupola',
 'A natural river bathing spot popular among locals and visitors.',
 'Not applicable (outdoor site)',
 'Free to visit; bring swimwear',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Nature')),

('Millathe Vesak Pahan Kuduwa',
 'A site known for Vesak lantern displays during the festival season.',
 'Seasonal (Vesak period only)',
 'Best visited during Vesak season',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Cultural')),

('Namaluwa Food Center',
 'A local food center in Dekatana offering traditional Sri Lankan street food.',
 'TBD - confirm on-site',
 'Try the local short eats',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Food')),

('Anods Chocolate Lounge',
 'A chocolate-themed cafe in Pugoda offering desserts and beverages.',
 'TBD - confirm on-site',
 'Great stop to end the day',
 NULL, NULL, NULL, NULL,
 (SELECT category_id FROM category WHERE category_name = 'Food')); 

UPDATE place SET image_path = 'images/kumari_ella/'          WHERE place_name = 'Kumara Ella';
UPDATE place SET image_path = 'images/water_world/'          WHERE place_name = 'Water World';
UPDATE place SET image_path = 'images/nissarana_wanaya/'     WHERE place_name = 'Nissarana Vanaya, Alms Hall';
UPDATE place SET image_path = 'images/wasala/'                WHERE place_name = 'Wasala Banquets and Nature Resort';
UPDATE place SET image_path = 'images/pathmawathi_piriwena/'  WHERE place_name = 'Keragala Pathmawathi Pirivena';
UPDATE place SET image_path = 'images/pilikuttuwa/'           WHERE place_name = 'Pilikuttuwa Raja Maha Viharaya';
UPDATE place SET image_path = 'images/ranwala/'                WHERE place_name = 'Ranwala Thotupola';
UPDATE place SET image_path = 'images/millathe_kuduwa/'       WHERE place_name = 'Millathe Vesak Pahan Kuduwa';
UPDATE place SET image_path = 'images/namaluwa/'               WHERE place_name = 'Namaluwa Food Center';
UPDATE place SET image_path = 'images/anods/'                  WHERE place_name = 'Anods Chocolate Lounge';

-- =========================================================
-- Travel Tips (Multiple categorized rows per place per SRS REQ-3.3)
-- Categories: 'entry-fee status', 'safety/what-to-bring', 'best-time-to-visit'
-- =========================================================
INSERT INTO travel_tip (place_id, tip_category, tip_text) VALUES
-- 1. Kumara Ella
((SELECT place_id FROM place WHERE place_name = 'Kumara Ella'), 'entry-fee status', 'Free to visit (outdoor natural site)'),
((SELECT place_id FROM place WHERE place_name = 'Kumara Ella'), 'safety/what-to-bring', 'Wear sturdy non-slip footwear and bring extra clothing; rocks can be slippery around natural pools'),
((SELECT place_id FROM place WHERE place_name = 'Kumara Ella'), 'best-time-to-visit', 'Best visited during daytime, ideally in the morning following moderate rainfall'),

-- 2. Water World
((SELECT place_id FROM place WHERE place_name = 'Water World'), 'entry-fee status', 'Entry fee applies - check current ticket rates on waterworld.lk'),
((SELECT place_id FROM place WHERE place_name = 'Water World'), 'safety/what-to-bring', 'Family-friendly destination; photography permitted without flash; keep admission tickets accessible'),
((SELECT place_id FROM place WHERE place_name = 'Water World'), 'best-time-to-visit', 'Best visited on weekday mornings or early afternoons to avoid peak weekend crowds'),

-- 3. Nissarana Vanaya, Alms Hall
((SELECT place_id FROM place WHERE place_name = 'Nissarana Vanaya, Alms Hall'), 'entry-fee status', 'Free to visit'),
((SELECT place_id FROM place WHERE place_name = 'Nissarana Vanaya, Alms Hall'), 'safety/what-to-bring', 'Dress respectfully covering shoulders and knees; maintain quietness in all meditation areas'),
((SELECT place_id FROM place WHERE place_name = 'Nissarana Vanaya, Alms Hall'), 'best-time-to-visit', 'Best visited in the morning before noon during daily alms-giving hours'),

-- 4. Wasala Banquets and Nature Resort
((SELECT place_id FROM place WHERE place_name = 'Wasala Banquets and Nature Resort'), 'entry-fee status', 'Activity and dining charges apply depending on chosen packages'),
((SELECT place_id FROM place WHERE place_name = 'Wasala Banquets and Nature Resort'), 'safety/what-to-bring', 'Casual outdoor attire and comfortable footwear recommended for walking around the grounds'),
((SELECT place_id FROM place WHERE place_name = 'Wasala Banquets and Nature Resort'), 'best-time-to-visit', 'Best visited in the morning or early evening for scenic natural views and cooler weather'),

-- 5. Keragala Pathmawathi Pirivena
((SELECT place_id FROM place WHERE place_name = 'Keragala Pathmawathi Pirivena'), 'entry-fee status', 'Free to visit'),
((SELECT place_id FROM place WHERE place_name = 'Keragala Pathmawathi Pirivena'), 'safety/what-to-bring', 'Modest clothing required (preferably white); remove shoes and headwear before entering temple grounds'),
((SELECT place_id FROM place WHERE place_name = 'Keragala Pathmawathi Pirivena'), 'best-time-to-visit', 'Best visited during morning or late afternoon puja (service) times'),

-- 6. Pilikuttuwa Raja Maha Viharaya
((SELECT place_id FROM place WHERE place_name = 'Pilikuttuwa Raja Maha Viharaya'), 'entry-fee status', 'Free to visit'),
((SELECT place_id FROM place WHERE place_name = 'Pilikuttuwa Raja Maha Viharaya'), 'safety/what-to-bring', 'Wear comfortable walking shoes suitable for stone stairways and forest paths; modest dress required'),
((SELECT place_id FROM place WHERE place_name = 'Pilikuttuwa Raja Maha Viharaya'), 'best-time-to-visit', 'Best visited early in the morning or late afternoon to avoid the midday heat when walking cave trails'),

-- 7. Ranwala Thotupola
((SELECT place_id FROM place WHERE place_name = 'Ranwala Thotupola'), 'entry-fee status', 'Free to visit'),
((SELECT place_id FROM place WHERE place_name = 'Ranwala Thotupola'), 'safety/what-to-bring', 'Bring swimwear, towels, and water shoes; exercise caution regarding river water levels and currents'),
((SELECT place_id FROM place WHERE place_name = 'Ranwala Thotupola'), 'best-time-to-visit', 'Best visited during sunny mornings or dry season when the river flow is gentle'),

-- 8. Millathe Vesak Pahan Kuduwa
((SELECT place_id FROM place WHERE place_name = 'Millathe Vesak Pahan Kuduwa'), 'entry-fee status', 'Free to visit'),
((SELECT place_id FROM place WHERE place_name = 'Millathe Vesak Pahan Kuduwa'), 'safety/what-to-bring', 'Bring a camera and flashlight for night walks; keep personal belongings secure in festival crowds'),
((SELECT place_id FROM place WHERE place_name = 'Millathe Vesak Pahan Kuduwa'), 'best-time-to-visit', 'Seasonal: Best visited during evening hours of the Vesak festival season (typically in May)'),

-- 9. Namaluwa Food Center
((SELECT place_id FROM place WHERE place_name = 'Namaluwa Food Center'), 'entry-fee status', 'Food purchase costs apply (affordable local street food pricing)'),
((SELECT place_id FROM place WHERE place_name = 'Namaluwa Food Center'), 'safety/what-to-bring', 'Carry cash for fast transactions; check food freshness and spice preferences with vendors'),
((SELECT place_id FROM place WHERE place_name = 'Namaluwa Food Center'), 'best-time-to-visit', 'Late afternoon and early evening when fresh short eats and hot snacks are prepared'),

-- 10. Anods Chocolate Lounge
((SELECT place_id FROM place WHERE place_name = 'Anods Chocolate Lounge'), 'entry-fee status', 'Menu purchase costs apply (café pricing for beverages and confectioneries)'),
((SELECT place_id FROM place WHERE place_name = 'Anods Chocolate Lounge'), 'safety/what-to-bring', 'Cash and card payments accepted; air-conditioned seating available'),
((SELECT place_id FROM place WHERE place_name = 'Anods Chocolate Lounge'), 'best-time-to-visit', 'Afternoon or early evening as a relaxing stop to conclude your day tour');

-- First admin user creation - password is Test@123
INSERT INTO users (username, first_name, last_name, password_hash, user_type) VALUES ('admin', 'Kumudu', 'Gamlath', '$2y$10$UZjn2NOmhJVglRHVhsxCWeMVex58962Fx5OoZ1F0ONMKtv/efQMZy', 'admin');
