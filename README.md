# Clinic App (Non-Docker Setup)

This project is configured to run on a **standard local LAMP/WAMP/XAMPP-style stack** (no Docker required):

- PHP 8+
- MySQL 8+
- Apache/Nginx with PHP enabled
- Browser with JavaScript enabled (AJAX via `fetch`)

## 1) Create the database

```sql
CREATE DATABASE clinic_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then import the schema:

```bash
mysql -u root -p clinic_app < schema.sql
```

## 2) Configure DB credentials

Edit `config.php`:

```php
return [
  'host' => '127.0.0.1',
  'port' => 3306,
  'database' => 'clinic_app',
  'username' => 'root',
  'password' => '',
];
```

## 3) Run locally (no Docker)

From project root:

```bash
php -S 127.0.0.1:8000
```

Then open:

- `http://127.0.0.1:8000/index.php`

## Features

- Add patients with name, email, and phone
- List existing patients
- Uses PHP + MySQL backend and AJAX frontend
# Diagnostics Centre & Doctor's Clinic Management System

A ready-to-run **Core PHP + MySQL** application implementing the SRS modules for single-centre operations.

## Features Included

- Role-based login (Super Admin, Receptionist, Doctor, Technician, Radiologist, Lab Admin, Cashier, Centre Manager)
- Patient registration with UHID generation and repeat visit quick support
- Appointment scheduling (book/reschedule/cancel/walk-in token)
- OPD consultations with vitals, diagnosis, advice, follow-up
- Diagnostics booking for pathology/imaging/package services
- Laboratory workflow with status updates and result verification flow
- Radiology workflow with technician assignment and report approval flow
- Billing, partial/advance payments, receipts and outstanding calculations
- Report delivery eligibility (approved and paid logic)
- Inventory with low stock alerts and purchase/consumption tracking
- Dashboard and MIS reports (collection, revenue, pending reports, cancelled appointments, etc.)
- Communication log (SMS/Email/WhatsApp placeholders)
- Audit log for major operations

## Quick Start (Docker - Recommended)

```bash
docker compose up --build -d
```

Open: http://localhost:8080/public/login.php

Default users:

- Super Admin: `admin@clinic.local` / `admin123`
- Doctor: `doctor@clinic.local` / `doctor123`
- Cashier: `cashier@clinic.local` / `cashier123`

## Without Docker

1. Install PHP 8.1+ with PDO MySQL enabled.
2. Create database and import `sql/schema.sql`.
3. Configure env vars (or edit defaults in `config/database.php`):
   - DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
4. Serve project root with Apache/Nginx and open `/public/login.php`.

## Structure

- `public/` web entrypoints and login/logout
- `modules/` module pages for each SRS area
- `includes/` shared auth/layout/helpers
- `config/` database connection
- `sql/schema.sql` full DB schema + seed data

## Notes

- OTP, WhatsApp, barcode printer, thermal printer, SMTP and SMS gateway are implemented as integration-ready placeholders with communication logs.
- For production, enable HTTPS, strong secrets, cron-based backup, and session hardening.
