-- SQL Script: แก้ไขนามสกุลไฟล์ button_image_filename ให้ถูกต้อง
-- สำหรับ existing data ที่อาจเก็บนามสกุลผิด
-- อัปเดต: 2026-02-07

-- ===== 1. Reset button_image_filename ให้เหมือน image_filename ก่อน =====
UPDATE valorant_maps 
SET button_image_filename = image_filename 
WHERE 1=1;

-- ===== 2. Manual fix examples (ถ้าจำเป็น) =====
-- ตัวอย่าง: ถ้ามี entry ที่ image_filename = 'ascent.png' แต่ไฟล์จริงเป็น 'ascent.webp'
-- UPDATE valorant_maps SET button_image_filename = 'ascent.webp' WHERE name = 'Ascent';

-- ===== 3. Verify ผลลัพธ์ =====
SELECT id, name, image_filename, button_image_filename FROM valorant_maps ORDER BY id;

-- ===== Notes =====
-- หลังจาก run script นี้ ให้เข้า admin map_table เพื่อแก้ไข map อีกครั้ง
-- ตอนแก้ไข ให้อพโหลดรูป button ใหม่ (หรือไม่อพ)
-- ระบบจะอัตโนมัติค้นหาไฟล์จริงและบันทึก extension ให้ถูกต้อง
