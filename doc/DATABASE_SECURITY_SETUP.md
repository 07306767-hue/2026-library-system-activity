# Database Security Setup Guide

## Overview
This guide provides instructions for securely setting up and maintaining the database for the Library System with SQL injection prevention in mind.

---

## Initial Database Setup

### 1. Create Database and Tables

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS library_system;
USE library_system;

-- Create books table
CREATE TABLE books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    genre VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_genre (genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create students table
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    enrollment_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create borrow_records table
CREATE TABLE borrow_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('borrowed', 'returned') DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE RESTRICT,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE RESTRICT,
    INDEX idx_student_id (student_id),
    INDEX idx_book_id (book_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date),
    INDEX idx_borrow_date (borrow_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Create Database User with Limited Privileges

```sql
-- Create application user with minimal required privileges
CREATE USER 'library_app'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant only necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON library_system.* TO 'library_app'@'localhost';

-- Revoke dangerous privileges
REVOKE CREATE, ALTER, DROP, GRANT OPTION ON library_system.* FROM 'library_app'@'localhost';

-- Apply privileges
FLUSH PRIVILEGES;
```

### 3. Create Read-Only User for Reporting

```sql
-- Create read-only user for reports
CREATE USER 'library_report'@'localhost' IDENTIFIED BY 'report_password_here';

-- Grant only SELECT privilege
GRANT SELECT ON library_system.* TO 'library_report'@'localhost';

-- Apply privileges
FLUSH PRIVILEGES;
```

---

## PHP Configuration

### DatabaseConfig.php Setup

Update your `DatabaseConfig.php` to use the application user:

```php
private $host = "localhost";
private $dbname = "library_system";
private $username = "library_app";
private $password = "secure_password_here";
```

### PDO Connection Settings

The system uses these secure PDO settings:

```php
$dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";

$this->pdo = new PDO(
    $dsn,
    $this->username,
    $this->password,
    [
        // Always throw exceptions for errors
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        
        // Disable emulated prepared statements (forces true prepared statements)
        PDO::ATTR_EMULATE_PREPARES => false,
        
        // Use UTF-8 for all string data
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
```

---

## Security Best Practices

### 1. Connection Security

✅ **DO:**
- Use localhost for database connections
- Store credentials outside web root
- Use environment variables for sensitive data
- Implement connection timeouts

❌ **DON'T:**
- Hardcode passwords in version control
- Use generic 'root' user for application
- Allow remote connections from untrusted hosts
- Log database credentials

### 2. User Privileges

✅ **Principle of Least Privilege:**
- Application user: SELECT, INSERT, UPDATE, DELETE only
- Report user: SELECT only
- Admin user: All privileges (for maintenance only)
- Never grant: CREATE, ALTER, DROP to application user

### 3. Database Maintenance

```sql
-- Regular backups
mysqldump -u library_app -p library_system > backup_$(date +%Y%m%d).sql

-- Check table integrity
CHECK TABLE books;
CHECK TABLE students;
CHECK TABLE borrow_records;

-- Optimize tables (monthly)
OPTIMIZE TABLE books;
OPTIMIZE TABLE students;
OPTIMIZE TABLE borrow_records;

-- View current users
SELECT user, host FROM mysql.user;

-- Check user privileges
SHOW GRANTS FOR 'library_app'@'localhost';
```

### 4. Logging and Monitoring

Enable general query log during development (disable in production):

```sql
-- Enable logging
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';

-- View logs
SELECT * FROM mysql.general_log ORDER BY event_time DESC LIMIT 10;

-- Disable logging (production)
SET GLOBAL general_log = 'OFF';
```

---

## Common Queries for Maintenance

### View Database Size
```sql
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'library_system'
ORDER BY (data_length + index_length) DESC;
```

### View Record Counts
```sql
SELECT 
    'books' AS table_name,
    COUNT(*) AS count
FROM books
UNION ALL
SELECT 'students', COUNT(*) FROM students
UNION ALL
SELECT 'borrow_records', COUNT(*) FROM borrow_records;
```

### Find Overdue Books
```sql
SELECT 
    br.record_id,
    b.title,
    s.name,
    b.author,
    br.due_date,
    DATEDIFF(CURDATE(), br.due_date) AS days_overdue
FROM borrow_records br
JOIN books b ON br.book_id = b.book_id
JOIN students s ON br.student_id = s.student_id
WHERE br.due_date < CURDATE() AND br.status = 'borrowed'
ORDER BY br.due_date ASC;
```

### Calculate Fines
```sql
SELECT 
    s.name,
    COUNT(br.record_id) AS overdue_count,
    SUM(br.fine_amount) AS total_fines
FROM borrow_records br
JOIN students s ON br.student_id = s.student_id
WHERE br.fine_amount > 0
GROUP BY br.student_id, s.name
ORDER BY total_fines DESC;
```

---

## Backup Strategy

### Daily Automated Backup Script

Create `backup.sh`:
```bash
#!/bin/bash

BACKUP_DIR="/backups/library_system"
DATE=$(date +%Y%m%d_%H%M%S)
DB_USER="library_app"
DB_PASS="secure_password_here"
DB_NAME="library_system"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Backup database
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"

# Keep only last 30 days of backups
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"
```

### Restore from Backup

```bash
# List available backups
ls -la /backups/library_system/

# Restore specific backup
gunzip < /backups/library_system/library_system_20260506_120000.sql.gz | mysql -u library_app -p library_system
```

---

## SQL Injection Prevention Verification

### Test Queries (Safe - Won't Cause Harm)

```php
// Test 1: Boolean-based injection
$testInput = "1' OR '1'='1";
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :id");
$stmt->execute([':id' => (int)$testInput]); // Cast to int(1)
// Result: Returns book_id = 1 only

// Test 2: LIKE injection
$testInput = "%' UNION SELECT * FROM students WHERE '1'='1";
$stmt = $pdo->prepare("SELECT * FROM books WHERE title LIKE :keyword");
$stmt->execute([':keyword' => $testInput]); // Treated as literal string
// Result: Searches for the literal string, UNION ignored

// Test 3: Comment-based injection
$testInput = "1; DROP TABLE books; --";
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :id");
$stmt->execute([':id' => (int)$testInput]); // Cast to int(1)
// Result: Returns book_id = 1, DROP ignored
```

All test inputs are safely handled due to prepared statements!

---

## Environment-Specific Configuration

### Development (db-config.dev.php)
```php
return [
    'host' => 'localhost',
    'dbname' => 'library_system_dev',
    'username' => 'library_dev',
    'password' => 'dev_password',
];
```

### Testing (db-config.test.php)
```php
return [
    'host' => 'localhost',
    'dbname' => 'library_system_test',
    'username' => 'library_test',
    'password' => 'test_password',
];
```

### Production (db-config.prod.php)
```php
return [
    'host' => 'database.internal',
    'dbname' => 'library_system_prod',
    'username' => 'library_app',
    'password' => getenv('DB_PASSWORD'), // Use environment variables
];
```

Load config based on environment:
```php
$env = getenv('APP_ENV') ?: 'production';
$config = require("db-config.{$env}.php");
```

---

## Performance Optimization

### Add Indexes for Common Queries

```sql
-- Search optimization
ALTER TABLE books ADD FULLTEXT INDEX ft_search (title, author);

-- Foreign key queries
ALTER TABLE borrow_records ADD INDEX idx_student_book (student_id, book_id);

-- Date range queries
ALTER TABLE borrow_records ADD INDEX idx_date_range (borrow_date, due_date, status);
```

### Query Optimization Tips

1. Always use indexes for WHERE clauses
2. Limit results with LIMIT clause
3. Use EXPLAIN to analyze slow queries
4. Join efficiently (prefer INNER JOIN)
5. Avoid SELECT * (specify columns needed)

```sql
-- Analyze slow query
EXPLAIN SELECT * FROM books WHERE title LIKE '%sql%';

-- Optimized version
EXPLAIN SELECT book_id, title, author FROM books WHERE title LIKE '%sql%' LIMIT 10;
```

---

## Troubleshooting

### Connection Issues

```php
try {
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    // Check:
    // 1. MySQL server running
    // 2. Database exists
    // 3. Username/password correct
    // 4. User has privileges
    // 5. Charset support
}
```

### Prepared Statement Issues

```php
// Problem: Using string interpolation
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = $id"); // WRONG

// Solution: Use parameter
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :id"); // CORRECT
$stmt->execute([':id' => $id]);

// Problem: Not casting numeric values
$stmt->execute([':id' => $userId]); // Could be string

// Solution: Cast to proper type
$stmt->execute([':id' => (int)$userId]); // Always integer
```

---

## Security Checklist

- [ ] Database user created with limited privileges
- [ ] Application user can only SELECT, INSERT, UPDATE, DELETE
- [ ] Report user created with SELECT only
- [ ] Root password changed from default
- [ ] All connection strings use localhost
- [ ] Credentials stored outside web root
- [ ] PDO error mode set to EXCEPTIONS
- [ ] Prepared statements used throughout
- [ ] Backups automated and tested
- [ ] Regular backups kept off-site
- [ ] Database logs monitored
- [ ] Indexes created for common queries
- [ ] UTF-8 charset configured
- [ ] Foreign keys enabled
- [ ] Regular maintenance scheduled

---

## References

- [MySQL Security Documentation](https://dev.mysql.com/doc/refman/8.0/en/security.html)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [SQL Injection Prevention](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [Database Security Best Practices](https://owasp.org/www-community/attacks/SQL_Injection)

