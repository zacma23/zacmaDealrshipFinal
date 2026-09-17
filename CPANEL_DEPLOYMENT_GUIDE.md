# Zacma AI Marketplace + CRM SaaS - cPanel Deployment Guide

This guide provides the complete, production-tested procedure for deploying the Zacma SaaS platform to standard cPanel shared or dedicated hosting environments.

---

## Architecture Compatibility for cPanel

- **PHP Version**: 8.2 or 8.3 (configured via cPanel **MultiPHP Manager**).
- **Database**: MySQL 8.0+ or MariaDB 10.4+ (configured via cPanel **MySQL Databases**).
- **Background Jobs**: Handled via Laravel `database` queue driver triggered by cPanel Cron. No Redis daemon required.
- **Frontend**: Pre-compiled production assets with fallback zero-node standalone bundles (`public/js/alpine.min.js`, `public/js/tailwind.min.js`). No Node.js daemon required.
- **Multi-Tenancy**: Subdomain and custom domain mapping via cPanel Subdomains or Addon Domains.

---

## Step 1: Set PHP Version & Required Extensions

1. Log into your **cPanel** account.
2. Under **Software**, click **MultiPHP Manager**.
3. Select your domain/subdomain and switch the PHP version to **PHP 8.2** or **PHP 8.3**.
4. Open **Select PHP Version** -> **Extensions** and ensure the following extensions are enabled:
   - `bcmath`, `curl`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `zip`.

---

## Step 2: Create MySQL Database & User

1. Navigate to **cPanel -> Databases -> MySQL Databases**.
2. Create a new database: e.g., `cpaneluser_zacma`.
3. Create a new MySQL user: e.g., `cpaneluser_dbuser` with a strong password.
4. Add the user to the database and grant **ALL PRIVILEGES**.

---

## Step 3: Deploy Code via cPanel Git Version Control

### Option A: Using cPanel Git Version Control (Recommended)
1. Go to **cPanel -> Files -> Git™ Version Control**.
2. Click **Create**.
3. Enter your repository URL (e.g. `git@github.com:your-org/zacma-saas.git`).
4. Set Repository Path: `/home/cpaneluser/zacma`.
5. Under repository details, notice `.cpanel.yml` is automatically detected.
6. Click **Deploy Head Commit** to pull and copy files to your web root.

### Option B: Using File Manager / ZIP Upload
1. Upload the project ZIP to `/home/cpaneluser/` and extract into a directory, e.g. `/home/cpaneluser/zacma`.

---

## Step 4: Configure Document Root & Web Server

Laravel requires web requests to point to the `public/` directory for security.

### If deploying to the primary domain (`public_html`):
In cPanel File Manager, create or edit `/home/cpaneluser/public_html/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```
Or create a symlink in Terminal / SSH:
```bash
cd /home/cpaneluser
ln -s zacma/public public_html
```

### If deploying to a subdomain (e.g., `app.yourdomain.com`):
In **cPanel -> Domains**, set the Document Root of your subdomain directly to:
`/home/cpaneluser/zacma/public`

---

## Step 5: Environment File Configuration (`.env`)

1. Copy `.env.cpanel.example` to `.env`:
   ```bash
   cp .env.cpanel.example .env
   ```
2. Open `.env` and fill in your details:
   ```ini
   APP_NAME="Zacma Marketplace SaaS"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://zacma.yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cpaneluser_zacma
   DB_USERNAME=cpaneluser_dbuser
   DB_PASSWORD=your_secure_password

   QUEUE_CONNECTION=database
   SESSION_DRIVER=file

   # Google Gemini API Key
   GEMINI_API_KEY=your_gemini_api_key_here

   # Payment Gateways (Optional: can also be configured via Super Admin Portal)
   SANTIMPAY_MERCHANT_ID=
   SANTIMPAY_PRIVATE_KEY=
   TELEBIRR_APP_ID=
   TELEBIRR_APP_KEY=
   CHAPA_SECRET_KEY=
   PAYPAL_CLIENT_ID=
   STRIPE_SECRET=
   ```

---

## Step 6: Initialize Application & Run Migrations

Open **cPanel Terminal** (or SSH) and run:
```bash
cd /home/cpaneluser/zacma

# Generate production encryption key
php artisan key:generate --force

# Create public storage symlink
php artisan storage:link

# Run database migrations and seed demonstration data
php artisan migrate:fresh --seed --force

# Cache routes, configuration, and views for optimal shared hosting performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 7: Configure cPanel Cron Jobs

Navigate to **cPanel -> Advanced -> Cron Jobs**.

### 1. Laravel Task Scheduler (Every Minute)
- Minute: `*`
- Hour: `*`
- Day: `*`
- Month: `*`
- Weekday: `*`
- Command:
  ```bash
  /usr/local/bin/php /home/cpaneluser/zacma/artisan schedule:run >> /dev/null 2>&1
  ```

### 2. Database Queue Worker (Every 5 Minutes)
- Minute: `*/5`
- Command:
  ```bash
  /usr/local/bin/php /home/cpaneluser/zacma/artisan queue:work database --stop-when-empty --tries=3 >> /dev/null 2>&1
  ```

---

## Initial Demonstration Logins

After running `--seed`, you can immediately access:

1. **Super Admin Portal**:
   - URL: `https://yourdomain.com/login`
   - Email: `admin@zacma.com`
   - Password: `password`

2. **Zacma Auto Dealership Portal**:
   - Email: `auto@zacma.com`
   - Password: `password`

3. **Zacma Property Real Estate Portal**:
   - Email: `property@zacma.com`
   - Password: `password`

4. **Zacma Electronics Portal**:
   - Email: `electronics@zacma.com`
   - Password: `password`

5. **Customer Account**:
   - Email: `customer@zacma.com`
   - Password: `password`
