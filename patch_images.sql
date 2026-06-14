-- ============================================================
-- AURA — Image URL Patch
-- Run this if you already imported setup.sql with old filenames.
-- This updates all 8 original products + adds 3 new ones.
-- ============================================================

USE aura_ecommerce;

UPDATE products SET image = 'https://suitharbor.com/cdn/shop/files/men-blazer-outfit_44_bcf5fc19-2ef0-493f-9133-b3b917f961f9.jpg?v=1726386212&width=990'
  WHERE slug = 'obsidian-slim-blazer';

UPDATE products SET image = 'https://images.unsplash.com/photo-1621072156002-e2fcced0b170?q=80&w=1200&auto=format&fit=crop'
  WHERE slug = 'sovereign-white-dress-shirt';

UPDATE products SET image = 'https://images.pexels.com/photos/9849503/pexels-photo-9849503.jpeg?auto=compress&cs=tinysrgb&w=1200'
  WHERE slug = 'velvet-evening-gown';

UPDATE products SET image = 'https://i.etsystatic.com/31389789/r/il/94c7a4/7340809922/il_600x600.7340809922_niuf.jpg'
  WHERE slug = 'silk-wrap-dress';

UPDATE products SET image = 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=1200&auto=format&fit=crop'
  WHERE slug = 'gold-clasp-leather-belt';

UPDATE products SET image = 'https://sowears.net/cdn/shop/files/4_58df46a2-3079-40db-a68c-ec724f1ac827_600x.jpg?v=1769245130'
  WHERE slug = 'structured-tote-bag';

UPDATE products SET image = 'https://images.unsplash.com/photo-1624222247344-550fbadfe94e?q=80&w=1200&auto=format&fit=crop'
  WHERE slug = 'cashmere-ribbed-turtleneck';

UPDATE products SET image = 'https://khalil-ahmed.com/cdn/shop/files/BROWN2.jpg?v=1719935882&width=990'
  WHERE slug = 'tailored-wide-leg-trousers';

-- Add 3 new products (safe to run — uses INSERT IGNORE on slug)
INSERT IGNORE INTO products (category_id, name, slug, description, price, sale_price, image, stock, featured)
SELECT c.id, 'Heritage Linen Overshirt', 'heritage-linen-overshirt',
  'Relaxed-fit linen overshirt in warm sand. Camp collar, chest patch pockets, mother-of-pearl buttons.',
  185.00, NULL,
  'https://fineur.pk/cdn/shop/files/2_44661394-5101-4446-a1cb-daae173b0b5f_1066x.jpg?v=1753868722',
  22, 0
FROM categories c WHERE c.slug = 'men' LIMIT 1;

INSERT IGNORE INTO products (category_id, name, slug, description, price, sale_price, image, stock, featured)
SELECT c.id, 'Burnished Leather Loafer', 'burnished-leather-loafer',
  'Hand-burnished calfskin penny loafer with leather sole and gold horsebit accent. Italian craftsmanship.',
  395.00, NULL,
  'https://www.tods.com/fashion/tods/X8MC1518590VRHB009/X8MC1518590VRHB009-34.jpg?imwidth=1620',
  10, 1
FROM categories c WHERE c.slug = 'accessories' LIMIT 1;

INSERT IGNORE INTO products (category_id, name, slug, description, price, sale_price, image, stock, featured)
SELECT c.id, 'Draped Satin Co-ord Set', 'draped-satin-co-ord-set',
  'Fluid draped satin two-piece set: crossover top and wide palazzo trousers. Luxurious and effortless.',
  320.00, 280.00,
  'https://wewearpk.com/cdn/shop/files/007_6_0da0e13f-77c3-44f7-88a0-b122d8000538.jpg?v=1756208345&width=1200',
  14, 1
FROM categories c WHERE c.slug = 'women' LIMIT 1;

SELECT id, name, LEFT(image,60) AS image_preview FROM products ORDER BY id;
