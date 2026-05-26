# Urdu Books World — Subdomain Deployment Guide

This is the production-ready codebase for **Urdu Books World** (deployed at `urdubooksworld.finaccord.pro`), a modern bilingual (English and Urdu) online bookstore selling literature and academic publications to the diaspora in the United Kingdom.

Built using **native PHP 8.1+**, **MySQL 8.x**, custom **CSS3**, and **vanilla JavaScript**, this platform runs on standard Hostinger shared hosting without SSH, Composer, or npm compilation steps.

---

## Hostinger Step-by-Step Deployment

This repository is prepared for GitHub-backed deployment. Production secrets must stay out of Git:

- Database credentials are stored on the server in `/includes/config.local.php`.
- GitHub Actions deploys code updates over FTP when Hostinger FTP secrets are configured.
- The confidential business plan and deployment ZIP are intentionally excluded from Git.

Required GitHub repository secrets for automatic Hostinger deployment:

- `HOSTINGER_FTP_HOST`
- `HOSTINGER_FTP_USERNAME`
- `HOSTINGER_FTP_PASSWORD`
- `HOSTINGER_FTP_TARGET_DIR`

`HOSTINGER_FTP_TARGET_DIR` should point to the subdomain document root, for example `/public_html/urdubooksworld/` or `/domains/finaccord.pro/public_html/urdubooksworld/`.

Follow these simple instructions to launch the site live in under 10 minutes:

### 1. Create the Subdomain
1. Log in to your **Hostinger hPanel**.
2. Navigate to **Websites** → click **Manage** on your domain (`finaccord.pro`).
3. In the left sidebar, click **Domains** → **Subdomains**.
4. Enter `urdubooksworld` as the subdomain name.
5. Hostinger will automatically allocate the Document Root to `/public_html/urdubooksworld/` (or `/domains/finaccord.pro/public_html/urdubooksworld/`). Click **Create**.

### 2. Establish a MySQL Database
1. In your hPanel sidebar, navigate to **Databases** → **MySQL Databases**.
2. Under **Create New MySQL Database**, enter:
   - Database Name: `urdubooks` (Hostinger will prefix this, e.g., `u123456_urdubooks`)
   - Username: `dbuser` (e.g., `u123456_dbuser`)
   - Password: Choose a strong password.
3. Click **Create** and note down the **DB Name**, **DB User**, and **DB Password**.

### 3. Upload Project Files
1. Download the compiled `urdubooksworld.zip` from your developer package.
2. In hPanel, navigate to **Files** → **File Manager**.
3. Open your subdomain directory (e.g. `/public_html/urdubooksworld/`).
4. Click the **Upload** button (top right) → choose **File** → select `urdubooksworld.zip`.
5. Once uploaded, right-click the ZIP file and select **Extract**. Extract it directly into the root folder.
   *(Make sure files like `index.php` and folders like `/includes/` are in the main subdomain root, not nested inside another folder).*

### 4. Run the One-Time Automated Installer
1. Open your web browser and visit: `https://urdubooksworld.finaccord.pro/admin/install.php`
2. Enter the database credentials from **Step 2**:
   - DB Host: `localhost`
   - DB Name: `u123456_urdubooks`
   - DB User: `u123456_dbuser`
   - DB Password: `[Your DB Password]`
3. In the second section, enter the custom email and password you wish to use for your **Admin Panel** login:
   - Admin Email: `admin@urdubooksworld.co.uk`
   - Admin Password: `[Choose a Secure Password]`
4. Click **Run Setup & Import Tables**.
5. The installer will automatically:
   - Create all tables, indexes, and relations (`schema.sql`).
   - Seed the database with site configurations, 30 categories, 15 authors, 10 publishers, and **50 famous bilingual books** (`seed.sql`).
   - Write database configurations directly to `/includes/config.php`.
   - Write a secure lock file (`install.lock`) to **permanently disable** the installer from running again.

### 5. Force SSL Encryption (HTTPS)
1. Go back to Hostinger hPanel → **Security** → **SSL**.
2. Locate the subdomain `urdubooksworld.finaccord.pro` and install the free **Let's Encrypt SSL** certificate.
3. Once active, the custom `.htaccess` pre-packaged in this codebase will automatically force all HTTP traffic to secure HTTPS and inject core security headers to protect customer orders.

---

## Post-Install Verification & Launch Checklist

Once setup is complete, complete this simple test flow:
1. **Language Check**: Load the homepage and click the **English | اردو** toggle in the top bar. Verify the layout direction flips cleanly (LTR to RTL) and the text switches between English and Nastaliq scripts.
2. **AJAX Cart Test**: Search for a book, click **Add to Cart**, and verify the cart badge updates instantly without page reloads.
3. **Checkout Flow**: Complete a checkout as a guest or register a new customer account. Complete checkout choosing **Bank Transfer** or **Cash on Delivery**. Verify that the grand totals, delivery charges, and Barclays BACS coordinates display accurately on `/order-confirmation.php`.
4. **Admin Dashboard**: Log in to the administrator panel at `/admin/login.php` with your credentials. Verify that your recent order is logged, low-stock warnings show, and you can edit or add books in under 2 minutes.

---

## Technical Specifications
- **Language**: PHP 8.1+ with native PDO Prepared Statements (SQL-Injection protected).
- **Security**: Forms protected with cryptographic CSRF tokens, passwords hashed with bcrypt, and output escaped using HTMLSpecialChars.
- **Library Integration**: Full support for Library of Congress (LOC) Classifications and MARC bibliographic raw text.
