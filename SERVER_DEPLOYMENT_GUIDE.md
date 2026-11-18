# 🚀 Live Server Deployment Guide (Smart Student Placement Portal)

Yeh guide aapko is project ko kisi bhi live hosting server (jaise cPanel, Hostinger, GoDaddy, VPS, AWS) par upload aur run karne ke simple steps batata hai.

---

### Step 1: Files Upload Karein
1. Apne Hosting cPanel / File Manager mein jayein.
2. `public_html` (ya domain directory) ke andar is ZIP file ko upload karein.
3. ZIP file ko wahan **Extract** karein.

---

### Step 2: Database Setup Karein
1. cPanel mein **MySQL Databases** option par jayein:
   - Ek naya database banayein (e.g., `u123_placement_db`).
   - Ek naya MySQL user banayein aur strong password set karein.
   - User ko Database assign karein aur **"ALL PRIVILEGES"** grant karein.
2. **phpMyAdmin** open karein aur naye banaye database par click karein.
3. Top menu mein **Import** par jayein.
4. Project ke folder se yeh file choose karke Import dabayein:
   👉 `database/smart_placement_db_backup.sql`
   *(Saare tables, skills, drives, aur aapka admin account turant ready ho jayega!)*

---

### Step 3: Database Configuration Update Karein
Project ke folder mein `config/database.php` open karein aur live database ke credentials daal dein:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'aapka_database_naam');
define('DB_USER', 'aapka_database_username');
define('DB_PASS', 'aapka_database_password');
```

---

### 🔑 Default Login Credentials:

* **Primary Super Admin (T&P Cell):**
  - **Email:** `mrhimanshukumar343@gmail.com`
  - **Password:** `Sharma@#1432`

* **Demo Student 1:**
  - **Email:** `rahul.sharma@college.edu`
  - **Password:** `student123`

* **Demo Recruiter:**
  - **Email:** `recruiter@company.edu`
  - **Password:** `company123`

---

### 🔒 Security Check on Live Server:
- `config/` directory mein already `.htaccess` laga hua hai jo database credentials ko public access se block karta hai.
- `uploads/resumes/` mein strict execution block `.htaccess` active hai.
- Sabhi forms CSRF protected hain.
