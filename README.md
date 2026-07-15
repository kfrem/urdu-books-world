# Urdu Books World

Production codebase for **Urdu Books World**, a bilingual English/Urdu PHP and MySQL bookstore.

Primary production domain: `https://urdubooksworld.co.uk`

Legacy Hostinger deployment: `https://urdubooksworld.finaccord.pro`

The Porkbun deployment is intended to be independent from Hostinger. It needs its own uploaded files, its own MySQL database, and its own `/includes/config.local.php`.

## Requirements

- PHP 8.1 or newer
- MySQL or MariaDB
- FTP/SFTP or cPanel file upload access
- HTTPS/SSL enabled on the domain

## Server-Only Config

Production credentials must not be committed to Git.

Create `/includes/config.local.php` on the target server:

```php
<?php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'replace_with_porkbun_database_name',
    'DB_USER' => 'replace_with_porkbun_database_user',
    'DB_PASS' => 'replace_with_porkbun_database_password',
    'SITE_URL' => 'https://urdubooksworld.co.uk',
];
```

## Porkbun Deployment

Use Porkbun Easy PHP or cPanel hosting. Porkbun Static Hosting is not suitable because this site requires PHP and MySQL.

1. Create a MySQL database in Porkbun/cPanel.
2. Upload the site files to the web root for `urdubooksworld.co.uk`.
3. Import the live SQL export into the Porkbun database.
4. Add `/includes/config.local.php` with the Porkbun database credentials.
5. Ensure SSL is active for `urdubooksworld.co.uk`.
6. Verify `/`, `/category.php`, `/book.php?slug=aag-ka-darya`, `/cart.php`, and `/admin/login.php`.

## Fresh Install Alternative

If a live database clone is not required, upload the files and open:

`https://urdubooksworld.co.uk/admin/install.php`

The installer imports `sql/schema.sql` and `sql/seed.sql`, creates an admin account, writes `/includes/config.local.php`, and creates `/admin/install.lock`.

## Deployment Notes

- `/includes/config.local.php` is intentionally ignored by Git.
- `/admin/install.lock` is intentionally ignored by Git.
- `.deploy-temp/` is intentionally ignored because it may contain database exports and migration files.
- The confidential business plan and generated deployment ZIP files are excluded from Git.

## Verification Checklist

1. Homepage loads over HTTPS on `urdubooksworld.co.uk`.
2. Book covers load from local placeholder assets when real covers are missing.
3. Coming-soon books show disabled purchase buttons.
4. Search, category pages, and language toggle work.
5. Cart and checkout flows work for purchasable books.
6. Admin login works and the dashboard shows books/orders/settings.
