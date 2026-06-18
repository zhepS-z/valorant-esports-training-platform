# วิธีทดสอบระบบแบน (Ban System Testing Guide)

## ✅ ตรวจสอบ Fix ที่ทำแล้ว

### 1. **Login Page** (`auth/login.php`) 
   - ✅ ตรวจสอบแบนตอน login
   - ✅ ปฏิเสธการล็อกอินพร้อมข้อความแจ้ง

### 2. **Auth Check** (`auth/auth_check.php`)
   - ✅ ตรวจสอบแบนประมาณทีละหลังเข้า page
   - ✅ Auto include db.php ถ้ายังไม่มี connection
   - ✅ ป้องกันการเข้า page ที่มี auth_check

### 3. **Database Layer** (`utils/db.php`)
   - ✅ ตรวจสอบแบนตอน load user data
   - ✅ ป้องกัน infinite redirect ด้วย SKIP_BAN_CHECK

---

## 🧪 สถานการณ์ทดสอบ

### **สถานการณ์ 1: ตรวจสอบแบน ตอน Login**
```
ขั้นตอน:
1. Login ด้วย email ของ user ที่ตัวเองบัญชีปกติ (ยังไม่แบน)
   ✅ ผลควร: สามารถล็อกอินได้
   
2. Admin แบน user นั้น (ระยะเวลา: 30 วัน)

3. Admin ไป admin_dashboard/user_table.php
   → คลิกปุ่ม Ban บน user นั้น
   → เลือก "30 วัน"
   → คลิก "Confirm Ban"

4. User logout และพยายาม login อีกครั้ง
   ❌ ผลควร: ไม่สามารถล็อกอินได้
   → แสดงข้อความ: "บัญชีของคุณถูกแบนชั่วคราว สามารถล็อกอินได้ใน 30 วัน"
```

---

### **สถานการณ์ 2: ตรวจสอบแบน ตอน Access Page (Session ค้างไว้)**
```
ขั้นตอน:
1. User login ด้วยบัญชีปกติ
   ✅ ล็อกอินสำเร็จ

2. User navigate ไป หน้า profile/index.php ได้ปกติ
   ✅ สามารถเข้า page ได้

3. Admin แบน user นั้นขณะ user ยังเข้าอยู่
   → Admin ไป admin_dashboard/user_table.php
   → คลิก Ban บน user
   → เลือก "Permanent"
   → คลิก "Confirm Ban"

4. User navigate ไป หน้า career/career.php
   ❌ ผลควร: ถูก redirect ไปหน้า login
   → แสดง alert: "บัญชีของคุณถูกแบนอย่างถาวรและไม่สามารถเข้าใช้งานได้"
   → Session ถูก destroy โดยอัตโนมัติ
```

---

### **สถานการณ์ 3: ตรวจสอบแบน ตอน API Call**
```
ขั้นตอน:
1. User login ได้ปกติ

2. User สามารถเข้า API endpoints ได้
   ✅ เช่น: team/api/create_lfp.php

3. Admin แบน user

4. User พยายาม call API
   ❌ ผลควร: ถูก redirect
   → เนื่องจาก API มี require '../auth/auth_check.php'
```

---

### **สถานการณ์ 4: ตรวจสอบ Unban**
```
ขั้นตอน:
1. User ถูกแบน 30 วัน

2. Admin ปลดแบน user
   → Admin ไป admin_dashboard/user_table.php
   → หา user ที่ถูกแบน
   → คลิกปุ่ม "Unlock" (ปุ่มสีเขียว)

3. User พยายาม login อีกครั้ง
   ✅ ผลควร: สามารถล็อกอินได้ปกติ

4. User สามารถ navigate ทั่วไป page ได้
   ✅ ผลควร: ทำงานปกติ
```

---

### **สถานการณ์ 5: ตรวจสอบแบนถาวร**
```
ขั้นตอน:
1. Admin แบน user เป็น "Permanent"

2. User พยายาม login
   ❌ ผลควร: ไม่สามารถล็อกอินได้
   → แสดงข้อความ: "บัญชีของคุณถูกแบนอย่างถาวรและไม่สามารถเข้าใช้งานได้"

3. User พยายาม navigate ไป page (ถ้า session ยังมี)
   ❌ ผลควร: ถูก logout และ redirect

4. Admin ปลดแบน
   → ตั้ง ban_until = NULL

5. User เข้าใช้งานได้ปกติแล้ว
   ✅ ผลควร: สามารถล็อกอินและ navigate ได้
```

---

## 🔍 วิธี Check Status

### **ที่ Admin Dashboard**
```
1. ไป admin_dashboard/user_table.php
2. ดู column "Ban Status"
   - "Not banned" = ไม่ถูกแบน
   - "30 days" = แบน 30 วัน (ถ้าเหลือ 28 วัน จะแสดง "28 days")
   - "Permanent" = แบนถาวร
3. สามารถคลิก "History" Button เพื่อดู ban history
```

### **ที่ Database (SQL Query)**
```sql
SELECT user_id, email, ban_until FROM users WHERE ban_until IS NOT NULL;
```

---

## ⚠️ Troubleshooting

### **ปัญหา: User ยังสามารถเข้า page ได้หลังแบน**

**สาเหตุที่เป็นไปได้:**
1. ❌ ไฟล์ page ไม่มี `require_once '../auth/auth_check.php'`
   - **วิธีแก้:** เพิ่ม auth_check.php ที่ด้านบนของไฟล์

2. ❌ Session ยังมี user_id
   - **วิธีแก้:** ลบ browser cache หรือ clear cookies

3. ❌ Database connection fail
   - **วิธีแก้:** ตรวจสอบว่า db.php ทำงานได้หรือไม่

### **ปัญหา: User ไม่สามารถล็อกอินหลังแบน หมด期限**

ปัจจุบันยังไม่มี automatic unban เพราะต้องให้ admin ปลดแบนเอง สามารถเพิ่ม cron job ในอนาคตได้

### **ปัญหา: Ban status ไม่อัพเดต ใน admin dashboard**

- ลอง refresh page: Ctrl+F5
- ลองเข้ามา logout แล้ว login อีกครั้ง

---

## 📋 Checklist

- [ ] Ban during login works ✅ กรณีที่ 1
- [ ] Ban during page access works ✅ กรณีที่ 2  
- [ ] Ban during API call works ✅ กรณีที่ 3
- [ ] Unban works ✅ กรณีที่ 4
- [ ] Permanent ban works ✅ กรณีที่ 5
- [ ] Ban history shows correctly ✅ admin_dashboard
- [ ] Session is destroyed after ban ✅ ทั้งหมด

---

## 📝 Notes

- ระบบแบนใช้ `ban_until` column ในตาราง `users`
- ถ้า `ban_until` = `9999-12-31 23:59:59` = แบนถาวร
- ถ้า `ban_until` > `NOW()` = ถูกแบนอยู่
- ถ้า `ban_until` = `NULL` = ไม่ถูกแบน
- ประวัติการแบนทั้งหมดเก็บไว้ในตาราง `ban_history`

---

**สรุป: ระบบแบนในปัจจุบันครอบคลุม 3 ชั้น (Login, Auth, DB)**
