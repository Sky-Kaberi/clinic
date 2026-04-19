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
