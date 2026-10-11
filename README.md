# ICT Asset Loan Management System

A simple web-based system for managing ICT asset loans in a small organization (school or office IT department).

## Features

- **Asset Management**: Track ICT assets (laptops, projectors, tablets, cameras, etc.) with status tracking
- **Borrower Management**: Maintain a list of staff/students who borrow assets
- **Loan Tracking**: Issue loans, track due dates, record returns and conditions
- **QR Codes**: Each asset has a unique QR code that links to a public status page with return functionality
- **Reports**: Export loan history to CSV with date range filtering
- **User Roles**: Admin (full access) and Staff (can manage loans and view reports)
- **Overdue Tracking**: Dashboard shows overdue loans with days late

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer (for QR code library)
- Web server (Apache, Nginx, or built-in PHP server)

## Installation

### 1. Clone or Extract the Project

Place the project in your web server directory:
```bash
cd /var/www/html  # or your web root
# extract or clone the project here
```

### 2. Install Dependencies

```bash
cd asset-loan
composer install
```

This installs the `chillerlan/php-qrcode` library for QR code generation.

### 3. Create the Database

```bash
mysql -u root -p
```

```sql
-- Import the schema
source /path/to/asset-loan/database.sql;
```

Or using the command line:
```bash
mysql -u root -p < database.sql
```

### 4. Configure Database Connection

Edit `config/db.php` and update the database credentials:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'ict_asset_loan';
$DB_USER = 'root';
$DB_PASS = '';  // Change this!
```

Also change the CSRF and QR secrets to random strings:
```php
$CSRF_SECRET = 'your-random-string-here';
$QR_SECRET = 'your-qr-secret-here';
```

### 5. Set Admin Password

Run the install script to generate the admin password hash:

```bash
php install.php
```

This sets the admin password to `admin123`.

**IMPORTANT**: Delete `install.php` after running it:
```bash
rm install.php
```

### 6. Access the Application

Open your web browser and navigate to:
```
http://localhost/asset-loan/
```

Login with:
- **Username**: `admin`
- **Password**: `admin123`

**Change the admin password immediately after first login!**

## User Roles

### Admin
- Full access to all features
- Manage assets, borrowers, loans, and users
- View all reports
- Activate/deactivate users
- Reset user passwords

### Staff
- Create and manage loans
- Return assets
- View assets and borrowers
- View reports and export CSV
- Cannot manage users

## Usage Guide

### Adding Assets

1. Go to **Assets** → **Add Asset**
2. Fill in asset details (tag, name, category, brand, model, serial number)
3. Save the asset
4. Click the QR icon to view and print the QR code for the asset

### Issuing a Loan

1. Go to **Loans** → **New Loan**
2. Select an available asset and a borrower
3. Set the loan date and due date (defaults to 7 days)
4. Record the condition of the asset when it leaves
5. Submit the loan

### Returning an Asset

**Method 1: From the Loans Page**
1. Go to **Loans** → **Active Loans**
2. Click **Return** on the loan you want to return
3. Fill in the return date, condition on return, and any notes
4. Choose the new status (available, under repair, or retired)
5. Confirm the return

**Method 2: Using QR Code**
1. Scan the asset's QR code with a phone or scanner
2. The QR page shows the asset status and current loan info
3. Click **Return This Asset**
4. Fill in the return details and confirm

### Generating Reports

1. Go to **Reports**
2. Select a date range (optional)
3. Click **Filter** to preview
4. Click **Export CSV** to download the report

## Security Notes

- All forms use CSRF tokens to prevent cross-site request forgery
- All database queries use prepared statements to prevent SQL injection
- All output is escaped with `htmlspecialchars()` to prevent XSS
- Passwords are hashed using PHP's `password_hash()` with bcrypt
- Session-based authentication with role checks on protected pages
- The QR code public page (`asset_qr.php`) does not require login but uses signed tokens

## File Structure

```
asset-loan/
├── config/
│   └── db.php              # Database configuration and PDO connection
├── includes/
│   ├── auth.php            # Authentication and CSRF helpers
│   ├── functions.php       # Database helper functions
│   ├── header.php          # HTML header with navigation
│   ├── footer.php          # HTML footer
│   ├── asset_form.php      # Reusable asset form partial
│   └── borrower_form.php   # Reusable borrower form partial
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── app.js          # JavaScript helpers
├── vendor/                 # Composer dependencies (auto-generated)
├── composer.json           # PHP dependencies
├── database.sql            # Database schema and seed data
├── install.php             # Sets admin password (delete after use!)
├── index.php               # Redirects to dashboard
├── login.php               # Login page
├── logout.php              # Logout handler
├── dashboard.php           # Dashboard with stats and overdue loans
├── assets.php              # Asset management with QR codes
├── asset_qr.php            # Public QR code landing page
├── borrowers.php           # Borrower management
├── loans.php               # Loan management (new/active/history)
├── reports.php             # CSV export reports
├── users.php               # User management (admin only)
└── README.md               # This file
```

## Troubleshooting

### "Database connection failed"
- Check `config/db.php` credentials
- Verify the database exists and the user has permissions
- Ensure MySQL/MariaDB is running

### "Class 'chillerlan\QRCode\QRCode' not found"
- Run `composer install` to install the QR library
- Ensure `vendor/` directory exists

### QR codes not displaying
- Check that the GD extension is enabled in PHP: `php -m | grep gd`
- On Linux: `sudo apt-get install php-gd` (Debian/Ubuntu) or `sudo yum install php-gd` (CentOS/RHEL)
- Restart your web server after installing GD

### Cannot login
- Run `php install.php` to reset the admin password
- Check that the user is active in the database

## License

This is a simple project for educational/internal use. Feel free to modify as needed.

## Support

For issues or questions, refer to the inline code comments or contact your system administrator.
