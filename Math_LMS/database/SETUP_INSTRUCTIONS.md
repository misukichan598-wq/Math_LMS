# Database Setup Instructions

## Prerequisites

- MySQL 8.0+ or MariaDB 10.3+ installed
- PHP 8.3+ with PDO MySQL extension
- Composer installed
- Access to MySQL command line or GUI tool (phpMyAdmin, MySQL Workbench, etc.)

---

## Option 1: Using MySQL Command Line

### Step 1: Open MySQL Command Line

```bash
# Windows
mysql -u root -p

# Linux/Mac
mysql -u root -p
```

### Step 2: Create Database

```sql
-- Create the database
CREATE DATABASE IF NOT EXISTS math_lms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Verify database was created
SHOW DATABASES LIKE 'math_lms';
```

### Step 3: Create Database User (Recommended)

```sql
-- Create user for local development
CREATE USER IF NOT EXISTS 'math_lms_user'@'localhost' 
    IDENTIFIED BY 'YourStrongPassword123!';

-- Grant privileges
GRANT ALL PRIVILEGES ON math_lms.* 
    TO 'math_lms_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### Step 4: Configure Laravel Environment

Edit your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=math_lms
DB_USERNAME=math_lms_user
DB_PASSWORD=YourStrongPassword123!
```

### Step 5: Run Migrations

```bash
# Run all migrations
php artisan migrate

# If you need to reset and re-run
php artisan migrate:fresh

# Run migrations with seeders (when available)
php artisan migrate --seed
```

---

## Option 2: Using SQL File

### Step 1: Run the SQL File

```bash
# From the project root directory
mysql -u root -p < database/CREATE_DATABASE.sql

# Or specify the file path
mysql -u root -p < d:\Math_LMS\database\CREATE_DATABASE.sql
```

### Step 2: Update .env and Run Migrations

Follow Steps 4 and 5 from Option 1 above.

---

## Option 3: Using phpMyAdmin

### Step 1: Access phpMyAdmin
- Open your browser and navigate to `http://localhost/phpmyadmin`
- Login with your root credentials

### Step 2: Create Database
1. Click on "Databases" tab
2. Enter database name: `math_lms`
3. Select collation: `utf8mb4_unicode_ci`
4. Click "Create"

### Step 3: Create User (Optional)
1. Click on "User accounts" tab
2. Click "Add user account"
3. Fill in the details:
   - User name: `math_lms_user`
   - Host name: `localhost`
   - Password: (enter a strong password)
4. In "Database for user account" section:
   - Select "Grant all privileges on database math_lms"
5. Click "Go"

### Step 4: Configure Laravel
Update your `.env` file with the database credentials (see Step 4 in Option 1)

### Step 5: Run Migrations
```bash
php artisan migrate
```

---

## Option 4: Using Laravel Artisan

Laravel can create the database automatically if you have proper permissions:

### Step 1: Update .env

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=math_lms
DB_USERNAME=root
DB_PASSWORD=your_root_password
```

### Step 2: Create Database via Artisan

```bash
# This command will prompt to create database if it doesn't exist
php artisan migrate
```

When prompted "The database 'math_lms' does not exist. Would you like to create it?", type `yes`.

---

## Verification

After setup, verify your database:

```sql
-- Connect to MySQL
mysql -u math_lms_user -p

-- Use the database
USE math_lms;

-- Show all tables (after running migrations)
SHOW TABLES;

-- Check a specific table structure
DESCRIBE users;

-- Count records (will be 0 initially)
SELECT COUNT(*) FROM users;
```

Expected tables after migration:
- users
- personal_access_tokens
- password_reset_tokens
- sessions
- student_profiles
- lessons
- lesson_sections
- activities
- activity_questions
- assessments
- assessment_questions
- student_progress
- activity_attempts
- assessment_attempts
- assessment_answers
- student_scores
- hall_of_fame
- announcements
- notifications
- learning_history
- bookmarks
- audit_logs
- migrations

---

## Troubleshooting

### Error: "Access denied for user"
**Solution**: Check your username and password in `.env` file

### Error: "Unknown database 'math_lms'"
**Solution**: The database wasn't created. Run the CREATE DATABASE command manually

### Error: "SQLSTATE[HY000] [2002] Connection refused"
**Solution**: 
- Make sure MySQL service is running
- Check if DB_HOST and DB_PORT are correct in .env

### Error: "Syntax error or access violation: 1071 Specified key was too long"
**Solution**: Make sure your database uses `utf8mb4` charset

To fix:
```sql
ALTER DATABASE math_lms 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;
```

### Error: "PDO::__construct(): Argument #1 ($dsn) must be a valid data source name"
**Solution**: Make sure all database credentials in .env are set correctly

---

## For Production/Railway Deployment

Railway and similar platforms typically provide:
- Database URL in format: `mysql://user:password@host:port/database`

### Convert URL to .env format:

If you receive: `mysql://user:pass@host.railway.app:3306/railway`

Update .env:
```env
DB_CONNECTION=mysql
DB_HOST=host.railway.app
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=user
DB_PASSWORD=pass
```

Or use the full URL:
```env
DATABASE_URL=mysql://user:pass@host.railway.app:3306/railway
```

### Run migrations on Railway:

Railway will automatically run migrations if configured in `railway.json` or you can manually trigger:

```bash
# Via Railway CLI
railway run php artisan migrate --force

# The --force flag is required in production
```

---

## Quick Setup Script (PowerShell - Windows)

Save as `setup-database.ps1`:

```powershell
# Math LMS Database Setup Script

Write-Host "=== Math LMS Database Setup ===" -ForegroundColor Green

# Get MySQL credentials
$mysqlUser = Read-Host "Enter MySQL root username (default: root)"
if ([string]::IsNullOrWhiteSpace($mysqlUser)) { $mysqlUser = "root" }

$mysqlPass = Read-Host "Enter MySQL root password" -AsSecureString
$mysqlPassPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($mysqlPass)
)

# Create database
Write-Host "`nCreating database..." -ForegroundColor Yellow
$createDb = @"
CREATE DATABASE IF NOT EXISTS math_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SHOW DATABASES LIKE 'math_lms';
"@

$createDb | mysql -u $mysqlUser -p$mysqlPassPlain

if ($LASTEXITCODE -eq 0) {
    Write-Host "Database created successfully!" -ForegroundColor Green
    
    # Run migrations
    Write-Host "`nRunning migrations..." -ForegroundColor Yellow
    php artisan migrate
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`nSetup completed successfully!" -ForegroundColor Green
    } else {
        Write-Host "`nMigration failed. Please check your .env file." -ForegroundColor Red
    }
} else {
    Write-Host "`nDatabase creation failed." -ForegroundColor Red
}
```

Run with: `powershell -ExecutionPolicy Bypass -File setup-database.ps1`

---

## Security Notes

1. **Never commit .env to version control** - It contains sensitive credentials
2. **Use strong passwords** - Especially for production
3. **Create dedicated database user** - Don't use root for the application
4. **Restrict database user permissions** - Grant only necessary privileges
5. **Use different credentials for production** - Never use development credentials in production
6. **Enable SSL for production databases** - Encrypt data in transit
7. **Regular backups** - Set up automated database backups

---

## Next Steps After Database Setup

1. ✅ Database created
2. ✅ Migrations run
3. ⏳ Create admin user (via seeder or manually)
4. ⏳ Upload lesson PDFs
5. ⏳ Create assessment questions
6. ⏳ Test the application

For creating an admin user manually:

```sql
-- Insert admin user (password will be hashed by Laravel)
INSERT INTO users (name, email, password, role, created_at, updated_at) 
VALUES (
    'Admin User', 
    'admin@mathlms.com', 
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5Ld4a6yHYXLZK', -- password: password
    'admin', 
    NOW(), 
    NOW()
);
```

**Note**: It's better to create admin users via seeders once they're implemented.
