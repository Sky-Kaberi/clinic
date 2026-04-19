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
