# 🚀 CareerNexaa - Complete Live Deployment Guide (Website Ko Live Karne Ka Mukammal Tareeqa)

Yeh guide aapko aapki website ko kisi bhi live hosting (cPanel, Hostinger, Namecheap, VPS, Cloud) par upload aur run karne ke asaan steps batati hai.

---

## 📋 Pre-Deployment Check (Jo Humne Check Kar Liya Hai)
✓ **Database Schema:** `database.sql` bilkul fresh aur complete hai (tamam 14 tables, section visibility, aur admin accounts ke sath).  
✓ **Security Shields:** `.htaccess` file `.env`, `.sql`, aur `.git` ko public access se block karti hai.  
✓ **Uploads Shield:** `uploads/.htaccess` kisi bhi PHP file execution ko block karta hai.  
✓ **SEO & URLs:** Clean URLs (`/articles`, `/categories`, `/article/slug`, `/about`, waghera) 100% active hain.  
✓ **Login & Reset System:** 30 seconds rate-limit lockout aur Gmail SMTP live ke liye tayyar hain.  
✓ **Production Error Masking:** Live mode mein visitors ko koi internal PHP errors show nahi honge (`log_errors = 1`).

---

## 🛠️ Step 1: Files Ko Zip Bana Kar Hosting Par Upload Karein
1. Apne `job-portal` folder ke andar tamam files ko select karein aur `.zip` file banayein (e.g. `website.zip`).
   *(Note: `scratch/` folder ko zip mein include karne ki zaroorat nahi hai).*
2. Apne cPanel ya Hosting ke **File Manager** mein jayein.
3. **`public_html`** folder kholein.
4. `website.zip` ko upload karein aur wahan **Extract (Unzip)** kar dein.
5. Ensure karein k files direct `public_html` ke andar hon (na k kisi subfolder mein).

---

## 🗄️ Step 2: Database Banayein Aur `database.sql` Import Karein
1. cPanel mein **MySQL Databases** (ya phpMyAdmin) kholein.
2. Naya database banayein (maslan: `cpaneluser_jobportal`).
3. Naya database user banayein aur strong password set karein.
4. User ko Database ke sath connect karein aur **"ALL PRIVILEGES"** check karein.
5. cPanel mein **phpMyAdmin** kholein.
6. Apne naye database par click karein aur upar **Import** tab par jayein.
7. Project root mein maujood **`database.sql`** file ko choose karein aur **Import** / **Go** par click karein.
   *(Tamam 14 tables foran import ho jayenge).*

---

## ⚙️ Step 3: `.env` File Configure Karein
1. Live server ke `public_html` mein **`.env`** file banayein (ya `.env.example` ko copy/rename karein `.env` mein).
2. Is mein apni live hosting ki settings enter karein:

```env
# Live Environment
APP_ENV=production

# Live Domain (Baghair aakhri slash ke)
APP_URL=https://yourdomain.com

# Database Connection (Step 2 wali details)
DB_HOST=localhost
DB_NAME=cpaneluser_jobportal
DB_USER=cpaneluser_jobuser
DB_PASS=YourDatabasePasswordHere
DB_CHARSET=utf8mb4

# Gmail SMTP Email Settings
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=miansbb67@gmail.com
SMTP_PASS=itoczfytpruodvgz
SMTP_SECURE=tls
SMTP_FROM_EMAIL=miansbb67@gmail.com
SMTP_FROM_NAME="CareerNexaa Admin"
```

---

## 🔒 Step 4: Folder Permissions Check Karein
1. Ensure karein k **`uploads/`** aur **`uploads/articles/`** folder ki permissions **`755`** ya **`775`** hon taake naye articles ki images upload ho sakein.
2. `public_html/.htaccess` file mojood honi chahiye.

---

## 🌐 Step 5: Test Karein Aur Live Enjoy Karein!
1. Apni website open karein: `https://yourdomain.com/`
2. Admin panel open karein: `https://yourdomain.com/admin/login.php`
3. Super Admin credentials:
   - **Username:** `admin` ya `Faizan`
   - **Email:** `mianfaizanahmed8@gmail.com`
4. Admin panel mein ja kar **SEO ➔ Sitemap** par click karein aur **"Regenerate Sitemap.xml Now"** daba dein taake sitemap aapke naye live domain ke sath sync ho jaye.

Mubarak ho! Aapki website 100% production-ready aur live deployment ke liye tayyar hai! 🚀
