# Smart Student Management & Placement Portal

A comprehensive, full-featured Campus Recruitment and Placement Management System built with **PHP 8+**, **MySQL**, **Bootstrap 5**, and **JavaScript** for Polytechnic CSE 5th Semester Mini-Project requirements.

---

## Key Features

### 🎓 Student Portal
- **Student Registration & Secure Authentication**: Password hashing using `PASSWORD_BCRYPT` with duplicate roll number & email prevention.
- **Student Dashboard**: Live overview of profile completion percentage, verified CGPA & attendance status badges, active placement drives, and application stats.
- **Profile & Account Management**: Edit contact details, gender, DOB, address, and update account password.
- **Academic & Attendance Record**: Verified read-only view of diploma CGPA, official attendance percentage, 10th/12th percentages, and standing backlogs.
- **Skill Management**: Add/remove technical skills with proficiency ratings (Beginner, Intermediate, Advanced) from the master catalog or add custom skills.
- **PDF Resume Manager**: Secure upload, preview, download, and replacement of verified `.pdf` resumes (validated for MIME type and 2MB limit).
- **Placement Drives Directory**: Browse active drives with real-time automated eligibility badges and criteria checklists.
- **1-Click Application Engine**: Instant application submission for eligible drives.
- **Application Tracker (My Applications)**: Track status progression (`Applied` → `Shortlisted` → `Interview` → `Selected` → `Rejected`) and read TPO interview notes/feedback in real-time.

### 🛡️ Admin Portal (T&P Cell)
- **Admin Dashboard**: Real-time KPI statistics (Total Students, Partner Companies, Active Drives, Total Applications Received, Placed Count, Placement Rate %).
- **Student Management (Full CRUD)**:
  - Search by Name, Roll Number, Email.
  - Filter by Department/Branch and CGPA range.
  - Add new student with initial academic and attendance profile.
  - Edit student profile and toggle account status (`Active` / `Inactive`).
  - View Student 360° Profile (contact info, verified scores, skills, PDF resume viewer, and application history).
  - Safe deletion with foreign key cascading.
- **Academic & Attendance Manager**:
  - Scorecard directory with quick filters for Low Attendance (<75%), Low CGPA (<6.0), and Students with Backlogs.
  - Quick popup modal for updating student CGPA, attendance %, and backlogs.
- **Skills Master Catalog**: Manage the global repository of technical skills categorized into programming languages, databases, web development, and frameworks.
- **Company / Recruiter Directory**: Manage partner companies (Name, Website, Location, HR Contact, Description).
- **Placement Drives Hub**:
  - Create new drives with custom criteria (Min CGPA, Min Attendance %, Max Backlogs, Allowed Branches, Dates, and Multi-Skill Checkbox selector).
  - Edit active drives.
  - **Drive Command Center**: View submitted candidate applications (with PDF resume links and status changer modal) + **Automated Eligible Student Pool** scan.
- **Application Status Pipeline**: Move candidates across stages (`Applied` → `Shortlisted` → `Interview` → `Selected` → `Rejected`) with customized interview venue and timing remarks.
- **Reports & Placement Analytics**:
  - Executive summary metrics (Placement Rate, Highest CTC, Average CTC).
  - Department-wise placement success breakdown with progress bars.
  - Company-wise hiring statistics.
  - Placed Candidates Honor Roll table with 1-click printer-friendly output (`window.print()`).

---

## 💻 How to Run Locally with XAMPP

1. **Start XAMPP**:
   - Open XAMPP Control Panel.
   - Start **Apache** and **MySQL** services.

2. **Place Project in XAMPP `htdocs`**:
   - Copy or clone this folder into `C:\xampp\htdocs\SmartStudentPlacement`.

3. **One-Click Database Setup**:
   - Open your browser and visit:  
     `http://localhost/SmartStudentPlacement/setup_db.php`
   - The script will automatically create the database `smart_placement_db`, create all 9 tables, setup foreign keys, and insert seed data!

4. **Access the Portal**:
   - Homepage: `http://localhost/SmartStudentPlacement/`
   - Login Page: `http://localhost/SmartStudentPlacement/auth/login.php`

---

## 🔑 Default Credentials

| Role | Email | Password |
|---|---|---|
| **Admin (T&P Cell)** | `admin@college.edu` | `admin123` |
| **Demo Student 1 (Eligible)** | `rahul.sharma@college.edu` | `student123` |
| **Demo Student 2 (Ineligible Demo)** | `priya.patel@college.edu` | `student123` |

*(You can also register brand new student accounts directly via the Registration form).*

---

## 🗄️ Database Tables
1. `users` — User authentication credentials and roles
2. `students` — Student profiles and personal details
3. `academic_records` — Verified CGPA, attendance %, and backlogs
4. `skills` — Master repository of technical skills
5. `student_skills` — Student-acquired skills with proficiency levels
6. `companies` — Recruiting companies and HR contact info
7. `placement_drives` — Campus placement drives and eligibility criteria
8. `drive_skills` — Required skills mapped to placement drives
9. `applications` — Student drive applications and stage tracking

---

## 🔒 Security Measures
- **SQL Injection Prevention**: 100% prepared statements using PDO.
- **XSS Protection**: Sanitization with `htmlspecialchars()` on all dynamic outputs.
- **Password Security**: Strong hashing with `password_hash()` and `password_verify()`.
- **Session Protection**: `session_regenerate_id()` on login, HTTP-only cookie parameters, and strict role guards (`requireAdmin()`, `requireStudent()`).
- **File Upload Security**: Strict PDF whitelist, MIME-type inspection, 2MB size cap, randomized unique filenames, and execution blocking in `uploads/resumes/.htaccess`.
