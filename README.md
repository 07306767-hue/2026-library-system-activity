# 2026 Library System Activity

A secure PHP-based library management system designed to demonstrate best practices for preventing SQL injection attacks. This project implements a complete library system with book management, student borrowing records, and fine calculations using prepared statements and parameterized queries.

## Features

- **Secure Database Operations**: All database interactions use PDO prepared statements to prevent SQL injection
- **Book Management**: Add, search, update, and delete books with proper validation
- **Borrowing System**: Track book loans with due dates and fine calculations
- **Student Management**: Manage student records and borrowing history
- **Reporting**: Generate reports on borrowed books, overdue items, and fines
- **Input Validation**: Comprehensive validation layer for all user inputs
- **Error Handling**: Proper exception handling and user-friendly error messages

## Project Structure

```
2026-library-system-activity/
├── composer.json              # PHP dependencies and autoloading
├── legacy_library_system.php  # Original legacy system (secured)
├── public/
│   └── index.php             # Web entry point
├── src/
│   ├── config/
│   │   ├── DatabaseConfig.php # Database configuration
│   │   └── LibraryConfig.php  # Application configuration
│   ├── Entity/               # Data models
│   │   ├── Book.php
│   │   ├── BorrowRecord.php
│   │   └── Student.php
│   ├── Exception/            # Custom exceptions
│   │   ├── DatabaseException.php
│   │   └── ValidationException.php
│   ├── Repository/           # Data access layer
│   │   ├── BookRepository.php
│   │   └── BorrowRepository.php
│   ├── Service/              # Business logic
│   │   └── LibraryService.php
│   └── View/                 # Presentation layer
│       ├── book_list.php
│       ├── borrow_form.php
│       └── report_view.php
├── vendor/                   # Composer dependencies
└── doc/                      # Documentation
    ├── DATABASE_SECURITY_SETUP.md
    ├── IMPLEMENTATION_SUMMARY.md
    ├── SECURITY_QUICK_REFERENCE.md
    └── SQL_INJECTION_PREVENTION.md
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (PHP dependency manager)
- Web server (Apache/Nginx) or XAMPP

### Steps

1. **Clone or download the project** to your web server's document root (e.g., `htdocs` for XAMPP)

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Set up the database**:
   - Create a MySQL database named `library_system`
   - Run the SQL scripts from `doc/DATABASE_SECURITY_SETUP.md` to create tables and users
   - Update database credentials in `src/config/DatabaseConfig.php` if needed

4. **Configure web server**:
   - Ensure the `public/` directory is the document root or configure URL rewriting
   - Make sure PHP has access to the project files

## Usage

1. **Start your web server** (if using XAMPP, start Apache and MySQL)

2. **Access the application**:
   - Open your browser and navigate to `http://localhost/2026-library-system-activity/public/`
   - Or if configured as document root: `http://localhost/`

3. **Using the system**:
   - Add books through the book management interface
   - Register students
   - Process book borrowing and returns
   - View reports and overdue items

## Security Features

- **Prepared Statements**: All database queries use parameterized prepared statements
- **Input Validation**: Server-side validation for all user inputs
- **Output Escaping**: HTML output is properly escaped to prevent XSS
- **Least Privilege Database User**: Application uses a database user with minimal required permissions
- **Error Handling**: Sensitive information is not exposed in error messages

## Documentation

Detailed documentation is available in the `doc/` directory:

- **[DATABASE_SECURITY_SETUP.md](doc/DATABASE_SECURITY_SETUP.md)**: Complete database setup and security configuration
- **[IMPLEMENTATION_SUMMARY.md](doc/IMPLEMENTATION_SUMMARY.md)**: Technical implementation details and security improvements
- **[SECURITY_QUICK_REFERENCE.md](doc/SECURITY_QUICK_REFERENCE.md)**: Quick security checklist
- **[SQL_INJECTION_PREVENTION.md](doc/SQL_INJECTION_PREVENTION.md)**: Detailed SQL injection prevention guide

## Development

### Running Tests
```bash
# No automated tests are currently implemented
# Manual testing through the web interface is recommended
```

### Code Style
- Follows PSR-4 autoloading standards
- Uses strict typing where possible
- Comprehensive PHPDoc comments

## Contributing

This is an educational project demonstrating secure PHP development practices. For improvements:

1. Ensure all database operations use prepared statements
2. Add input validation for new features
3. Update documentation for changes
4. Test thoroughly for security vulnerabilities

## License

This project is for educational purposes. See composer.json for author information.

## Support

For issues or questions:
- Check the documentation in `doc/`
- Review error logs for database connection issues
- Ensure PHP PDO MySQL extension is enabled