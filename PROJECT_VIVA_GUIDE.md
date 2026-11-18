# Smart Student Placement Portal (V2) - College Viva & Interview Guide

Yeh guide specially aapke college presentation, viva aur internal/external examiner ke sawalon ke liye banayi gayi hai.

---

## 1. Project Overview (Simple 1-Minute Introduction)

> *"Good morning Sir/Madam,  
> Mera project hai **Smart Student Management & Placement Portal (V2)**.  
> Yeh ek web-based automated campus placement management system hai jisme teen main users hain:  
> 1. **Student**  
> 2. **College TPO / Admin**  
> 3. **Company Recruiter**  
>  
> Iska main objective hai manual placement activities ko automate karna, jaise automated eligibility checking, drives management, student resume screening, job bookmarking, aur official placement offer letter generation."*

---

## 2. Key Modules & Features (Jo aapko explain karne hain)

### 1. Student Portal
* **Automated Eligibility Check:** Student ka CGPA, attendance percentage, backlogs aur required skills match karke system automatically batata hai ki student eligible hai ya nahi.
* **1-Click Apply:** Eligible drives me student single click me apply kar sakta hai.
* **Save / Bookmark Jobs (PlacementIndia Style):** Student drives ko shortlist / bookmark karke baad me apply kar sakta hai.
* **Resume Upload:** Student apna verified PDF resume upload karta hai.
* **Application Tracker:** Student dekh sakta hai ki uski application Applied, Shortlisted, Interview ya Selected status me hai.

### 2. Admin / TPO (Training & Placement Officer)
* **Student Academic & Attendance Management:** CGPA, backlogs aur attendance percentage maintain karna.
* **Companies & Drives Management:** Naye placement drives create aur edit karna.
* **Excel / CSV Export:** 1-click me sabhi applicants ka data Excel me download karna.
* **Formal Offer Letter:** Select hone wale students ke liye printable official college offer letter generate karna.

### 3. Company Recruiter Portal
* **Self-Service Job Posting:** Company khud login karke drive post kar sakti hai.
* **Talent Search Engine (PlacementIndia Style):** Recruiter students ko branch, CGPA cutoff, backlogs aur specific skills ke base par filter karke unka resume dekh sakta hai.
* **Application Status Update:** Recruiter shortlisted ya selected candidates ka status real-time update kar sakta hai.

---

## 3. Technology Stack (Technical Details)

| Component | Technology Used |
| :--- | :--- |
| **Backend** | PHP 8.x (Pure Core PHP, MVC-inspired clean architecture) |
| **Database** | MySQL / MariaDB (InnoDB engine, Relational Foreign Keys, Cascades) |
| **Frontend** | HTML5, Bootstrap 5, CSS3, JavaScript |
| **Icons & Design** | Bootstrap Icons |
| **Security** | PDO Prepared Statements (SQL Injection Safe), CSRF Tokens, XSS Filtering (`htmlspecialchars`), Password Hashing (`bcrypt`), Session Hijacking Protection |

---

## 4. Top 10 Viva Questions & Exact Answers

#### Q1: Project me database connectivity kaise ki hai?
**Answer:** Humne PHP ka **PDO (PHP Data Objects)** use kiya hai with **Prepared Statements** aur parameterized queries. Isse system complete **SQL Injection attacks se 100% safe** rehta hai.

#### Q2: Passwords database me kaise store hote hain?
**Answer:** Passwords plain text me store nahi hote. Hum PHP ke standard `password_hash()` function with **BCRYPT algorithm** ka use karte hain, jo salt automatically generate karta hai. Verify karne ke liye `password_verify()` use hota hai.

#### Q3: CSRF kya hota hai aur aapne ise kaise protect kiya?
**Answer:** CSRF (Cross-Site Request Forgery) ek attack hai jisme user ke logged-in session ka misuse karke unauthorized action perform kiya jata hai. Humne har POST form me ek unique cryptographically secure `csrf_token` generate kiya hai jo session me match hota hai, jisse unauthorized requests reject ho jaati hain.

#### Q4: Automated Eligibility Engine kaise kaam karta hai?
**Answer:** Jab koi student kisi drive ko dekhta hai, system student ke record se:
1. `cgpa >= drive.min_cgpa`
2. `attendance >= drive.min_attendance`
3. `active_backlogs <= drive.max_backlogs`
4. `skills` comparison karta hai.  
Agar sabhi criteria match hote hain to system "Eligible" dikhata hai, warna reasons explain karta hai.

#### Q5: PlacementIndia jaisa feature isme kya hai?
**Answer:** Humne do main PlacementIndia-style features integrate kiye hain:
1. **Saved Jobs / Bookmarks:** Students apni pasand ke drives ko save karke alag dashboard me dekh sakte hain.
2. **Recruiter Talent Search:** Companies CGPA, branch aur technical skills ke filters laga kar pre-screened students ke resume download/preview kar sakti hain.

#### Q6: Agar do students apply karein to duplicate application kaise rukti hai?
**Answer:** Database me `applications` table par `UNIQUE KEY (drive_id, student_id)` constraint lagaya gaya hai. Ek student ek drive me ek hi baar apply kar sakta hai.

#### Q7: Database me kaunse main tables hain?
**Answer:**
1. `users` (Login authentication, roles)
2. `students` (Student profile, branch, contact)
3. `academic_records` (CGPA, attendance %, backlogs)
4. `companies` (Recruiting partners)
5. `placement_drives` (Job postings, criteria, packages)
6. `applications` (Job applications & status)
7. `skills` & `student_skills` (Technical skills mapping)
8. `saved_jobs` (Student bookmarked drives)

#### Q8: Excel export kaise generate hota hai?
**Answer:** Hum PHP ke native stream `php://output` aur `fputcsv()` ka use karke sanitized CSV stream generate karte hain, jisse bina kisi heavy third-party library ke file seedha Excel me open ho jaati hai.

#### Q9: Offer Letter kaise print hota hai?
**Answer:** Selected candidates ke liye system college header, formal joining details aur digital seal ke saath CSS `@media print` optimized page render karta hai, jise single click me PDF save ya print kiya ja sakta hai.

#### Q10: Project ka deployment kaise kiya hai?
**Answer:** Local testing ke liye **XAMPP (Apache + MySQL)** use hua hai, aur mobile ya remote testing ke liye **Ngrok secure tunneling** use kiya gaya hai.

---

## 5. Login Credentials for Demo

* **Admin Login:**
  * URL: `http://localhost/SmartStudentPlacement_V2/auth/login.php`
  * Email: `mrhimanshukumar343@gmail.com`
  * Password: `Sharma@#1432`

* **Demo Student:**
  * Email: `rahul.sharma@college.edu`
  * Password: `Student@123`

* **Demo Company:**
  * Email: `recruiter@tcs.com`
  * Password: `Company@123`
