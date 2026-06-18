-- SQL Script: Add Map Button Image support separately
-- Updated: 2026-02-07

-- ===== 1. Add button_image_filename column to valorant_maps table =====
ALTER TABLE valorant_maps 
ADD COLUMN button_image_filename VARCHAR(255) NULL DEFAULT NULL COMMENT 'Map Button image filename (preview)' AFTER image_filename;

-- ===== 2. Set button_image_filename = image_filename for existing maps =====
UPDATE valorant_maps 
SET button_image_filename = image_filename 
WHERE button_image_filename IS NULL;

-- ===== 3. Check table structure =====
-- DESCRIBE valorant_maps;
-- SELECT id, name, image_filename, button_image_filename, is_active FROM valorant_maps;

-- ===== 4. Reference SQL queries for usage (examples) =====
-- Add new map with button_image_filename:
-- INSERT INTO valorant_maps (name, image_filename, button_image_filename, display_order, is_active) 
-- VALUES ('Ascent', 'ascent.png', 'ascent_btn.png', 1, 1);

-- ดึง map พร้อม button_image_filename:
-- SELECT id, name, image_filename, button_image_filename FROM valorant_maps WHERE is_active = 1 ORDER BY display_order;

-- อัปเดต button_image_filename:
-- UPDATE valorant_maps SET button_image_filename='ascent_preview.png' WHERE id=1;

