# SQL Injection Prevention Documentation

## Overview
This document outlines all the SQL injection prevention measures implemented in the Library System. The system has been refactored to use **prepared statements (parameterized queries)** throughout, which is the gold standard for preventing SQL injection attacks.

---

## Key Security Features Implemented

### 1. **Prepared Statements (Parameterized Queries)**

All database queries now use PDO prepared statements with named placeholders instead of string concatenation.

#### Before (Vulnerable):
```php
$sql = "SELECT * FROM books WHERE book_id=" . $id;
$result = $this->conn->query($sql);
```

#### After (Secure):
```php
$sql = "SELECT * FROM books WHERE book_id = :id";
$stmt = $this->conn->prepare($sql);
$stmt->execute([':id' => (int)$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

**How it works:**
- The SQL structure is sent to the database separately from the data
- The database engine recognizes the parameter placeholder `:id`
- User input cannot be interpreted as SQL code
- The database handles proper escaping and quoting automatically

### 2. **Type Casting**

Integer parameters are explicitly cast to `int` to ensure only numeric values are passed:

```php
$stmt->execute([
    ':student_id' => (int)$student_id,
    ':book_id' => (int)$book_id,
    ':year' => (int)$year
]);
```

**Benefits:**
- Prevents string-based SQL injection
- Ensures only valid data types are used
- Catches invalid input early

### 3. **Input Validation**

The `LibraryService` class validates all user input before database operations:

```php
// Example validation for book input
if (empty($title)) {
    throw new ValidationException("Title cannot be empty");
}
if (strlen($title) > 255) {
    throw new ValidationException("Title is too long (max 255 characters)");
}
if ($year < 1000 || $year > date('Y')) {
    throw new ValidationException("Year must be between 1000 and " . date('Y'));
}
```

**What it validates:**
- Empty fields
- String length limits
- Date range validation
- Numeric ranges
- Required field presence

### 4. **Output Escaping**

All data displayed to users is escaped using `htmlspecialchars()`:

```php
echo "<td>" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</td>";
```

**Prevents:**
- XSS (Cross-Site Scripting) attacks
- Unintended HTML interpretation
- JavaScript execution from user data

### 5. **PDO Error Handling**

PDO is configured to throw exceptions for better error handling:

```php
$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

**Benefits:**
- Errors are caught and logged properly
- Database details not exposed to users
- Cleaner error handling code

### 6. **Parameter Binding for Search Queries**

Even LIKE queries use parameter binding:

```php
// Before (Vulnerable):
$sql = "SELECT * FROM books WHERE title LIKE '%" . $kw . "%'";

// After (Secure):
$sql = "SELECT * FROM books WHERE title LIKE :keyword";
$stmt->execute([':keyword' => '%' . $kw . '%']);
```

The search term is concatenated with wildcards **after** being bound as a parameter, not before SQL parsing.

---

## File-by-File Changes

### 1. **legacy_library_system.php**
All database operations converted to prepared statements:
- `addBook()` - Uses named parameters for all fields
- `getBook()` - Parameterized ID lookup
- `borrowBook()` - All numeric values cast and parameterized
- `returnBook()` - Record retrieval and update both use prepared statements
- `searchBooks()` - LIKE query uses parameterized binding
- `getOverdueBooks()` - JOIN query with parameterized date and status
- `generateReport()` - All COUNT and SUM queries parameterized

Added input validation in request handling:
```php
if (isset($_GET['act'])) {
    $action = htmlspecialchars($_GET['act'], ENT_QUOTES, 'UTF-8');
    
    if ($action == 'add') {
        $title = trim($_POST['t']);
        $year = intval($_POST['y']);
        // Validation checks...
    }
}
```

### 2. **src/Repository/BookRepository.php**
Complete implementation with security features:
- `addBook()` - 6 methods with prepared statements
- `getBook()` - ID parameter cast to int
- `searchBooks()` - Keyword parameter binding with wildcards
- `getAllBooks()` - No dynamic parameters
- `getAvailableBooks()` - Subquery with parameterized status
- `updateBook()` - All fields parameterized
- `deleteBook()` - ID cast and parameterized
- `countBooks()` - No dynamic parameters

### 3. **src/Repository/BorrowRepository.php**
Complete implementation with security features:
- `borrowBook()` - All 4 parameters properly bound
- `returnBook()` - Record retrieval and update with parameters
- `getRecord()` - ID parameter cast to int
- `getStudentRecords()` - Student ID parameterized
- `getOverdueBooks()` - Date and status parameterized
- `getBorrowedBooks()` - Status parameterized
- `countBorrowed()` - Status parameterized
- `countReturned()` - Status parameterized
- `getTotalFines()` - No dynamic parameters
- `getStudentFines()` - Student ID parameterized

### 4. **src/Service/LibraryService.php**
Service layer with comprehensive validation:
- `addBook()` - Validates title, author, year, genre
- `searchBooks()` - Keyword length limit (100 chars max)
- `borrowBook()` - Validates student_id, book_id, days range
- `returnBook()` - Validates record_id > 0
- `getStudentRecords()` - Validates student_id > 0
- `validateBookInput()` - Private method with comprehensive checks

---

## SQL Injection Attack Scenarios - Now Protected

### Scenario 1: String-based Injection
**Attack Input:** `1' OR '1'='1`

Before: `SELECT * FROM books WHERE book_id=1' OR '1'='1` ❌ (Vulnerable)
After: Treated as literal string, cast to int(1) ✓ (Safe)

### Scenario 2: Comment-based Attack
**Attack Input:** `1; DROP TABLE books; --`

Before: Executes multiple statements ❌ (Vulnerable)
After: Cast to int(1), everything else ignored ✓ (Safe)

### Scenario 3: LIKE-based Injection
**Attack Input:** `%' UNION SELECT * FROM users WHERE '1'='1`

Before: `title LIKE '%' UNION SELECT * FROM users WHERE '1'='1%'` ❌ (Vulnerable)
After: Searches for literal string with UNION keyword ✓ (Safe)

### Scenario 4: Boolean-based Blind SQL Injection
**Attack Input:** `1 AND 1=1`

Before: Returns results if true, none if false ❌ (Vulnerable)
After: Cast to int(1), input validated ✓ (Safe)

---

## Best Practices Implemented

1. **Never use string concatenation for SQL queries** ✓
2. **Always use prepared statements** ✓
3. **Cast numeric parameters to int** ✓
4. **Validate input length and format** ✓
5. **Escape output for HTML display** ✓
6. **Use meaningful error messages without database details** ✓
7. **Set PDO to throw exceptions** ✓
8. **Use type hints in method signatures** ✓
9. **Separate database access from business logic** ✓
10. **Implement comprehensive validation** ✓

---

## Testing SQL Injection Prevention

### Test Case 1: Book Search
```
URL: ?act=search&kw=%' OR '1'='1%
Expected: Returns books with OR in title only, not all books
Result: ✓ Secure
```

### Test Case 2: Get Book by ID
```
URL: ?id=1' OR '1'='1
Expected: Returns book with ID 1, not all books
Result: ✓ Secure
```

### Test Case 3: Add Book
```
POST: title='; DROP TABLE books; --
Expected: Title saved as literal string, not executed
Result: ✓ Secure
```

---

## Maintenance Guidelines

When adding new database queries:

1. **Always use prepared statements:**
   ```php
   $sql = "SELECT * FROM table WHERE column = :param";
   $stmt = $db->prepare($sql);
   $stmt->execute([':param' => $value]);
   ```

2. **Cast numeric parameters:**
   ```php
   ':id' => (int)$id
   ```

3. **Use the repository pattern** - Don't add queries directly in views or controllers

4. **Validate input** in the service layer before passing to repositories

5. **Escape output** when displaying data in HTML

---

## Additional Security Recommendations

1. **Database User Permissions** - Use least privilege principle
2. **Error Logging** - Log errors securely (not displayed to users)
3. **Rate Limiting** - Prevent brute force attacks
4. **Authentication** - Add user authentication and authorization
5. **HTTPS** - Use SSL/TLS for data transmission
6. **CSP Headers** - Implement Content Security Policy
7. **Input Length Limits** - Enforce at both application and database level
8. **Stored Procedures** - Can be used for additional security layer (if needed)

---

## References

- [OWASP SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [PHP PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CWE-89: SQL Injection](https://cwe.mitre.org/data/definitions/89.html)
