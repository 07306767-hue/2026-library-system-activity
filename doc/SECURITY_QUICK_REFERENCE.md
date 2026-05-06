# Security Implementation Quick Reference

## Prepared Statements - Basic Pattern

### Single Integer Parameter
```php
$sql = "SELECT * FROM books WHERE book_id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => (int)$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Multiple Parameters
```php
$sql = "INSERT INTO books(title, author, year, genre) VALUES(:title, :author, :year, :genre)";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':title' => $title,
    ':author' => $author,
    ':year' => (int)$year,
    ':genre' => $genre
]);
```

### LIKE with Wildcards
```php
// ✓ CORRECT - Wildcards added after binding
$sql = "SELECT * FROM books WHERE title LIKE :keyword";
$stmt = $db->prepare($sql);
$stmt->execute([':keyword' => '%' . $searchTerm . '%']);

// ✗ WRONG - Do not concatenate before binding
// $sql = "SELECT * FROM books WHERE title LIKE '%" . $searchTerm . "%'";
```

### JOIN with Parameters
```php
$sql = "SELECT br.*, b.title FROM borrow_records br 
        JOIN books b ON br.book_id = b.book_id 
        WHERE br.due_date < :today AND br.status = :status";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':today' => date('Y-m-d'),
    ':status' => 'borrowed'
]);
```

---

## Input Validation Pattern

```php
public function validateBookInput(string $title, string $author, int $year, string $genre): void
{
    // Trim whitespace
    $title = trim($title);
    $author = trim($author);
    
    // Check empty
    if (empty($title)) {
        throw new ValidationException("Title cannot be empty");
    }
    
    // Check length
    if (strlen($title) > 255) {
        throw new ValidationException("Title is too long (max 255 characters)");
    }
    
    // Check range
    if ($year < 1000 || $year > date('Y')) {
        throw new ValidationException("Year must be between 1000 and " . date('Y'));
    }
}
```

---

## Output Escaping Pattern

```php
// ✓ CORRECT - Always escape when outputting HTML
echo "<td>" . htmlspecialchars($data, ENT_QUOTES, 'UTF-8') . "</td>";

// ✗ WRONG - Direct output allows XSS
// echo "<td>" . $data . "</td>";
```

---

## Error Handling Pattern

```php
try {
    $sql = "SELECT * FROM books WHERE book_id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => (int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ✓ Log error securely, don't expose database details
    error_log("Database error: " . $e->getMessage());
    // Return user-friendly message
    throw new Exception("Failed to retrieve book");
}
```

---

## Request Handling Pattern

```php
// ✓ CORRECT - Validate and sanitize GET parameters
if (isset($_GET['act'])) {
    $action = htmlspecialchars($_GET['act'], ENT_QUOTES, 'UTF-8');
    
    if ($action == 'add') {
        // Validate POST data exists
        if (isset($_POST['title'], $_POST['author'])) {
            $title = trim($_POST['title']);
            
            // Validate before use
            if (!empty($title)) {
                // Use prepared statement
                $library->addBook($title, ...);
            }
        }
    }
}

// ✗ WRONG - Direct use of $_GET
// if ($_GET['act'] == 'add') { ... }
```

---

## Repository Pattern Example

```php
// In BookRepository.php
public function addBook(string $title, string $author, int $year, string $genre)
{
    try {
        $sql = "INSERT INTO books(title, author, year, genre) VALUES(:title, :author, :year, :genre)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':year' => $year,
            ':genre' => $genre
        ]);
        return $this->db->lastInsertId();
    } catch (PDOException $e) {
        throw new \Exception("Error adding book: " . $e->getMessage());
    }
}

// In LibraryService.php - Call repository with validation
public function addBook(string $title, string $author, int $year, string $genre)
{
    // Validate
    $this->validateBookInput($title, $author, $year, $genre);
    
    try {
        // Use repository
        return $this->bookRepository->addBook($title, $author, $year, $genre);
    } catch (\Exception $e) {
        throw new ValidationException("Failed to add book: " . $e->getMessage());
    }
}
```

---

## Common Mistakes to Avoid

### ✗ String Concatenation
```php
// NEVER do this
$sql = "SELECT * FROM books WHERE title = '" . $title . "'";
$sql = "SELECT * FROM books WHERE book_id = " . $id;
```

### ✗ sprintf() for SQL
```php
// NEVER do this
$sql = sprintf("SELECT * FROM books WHERE author = '%s'", $author);
```

### ✗ Mixing Parameters and Concatenation
```php
// NEVER do this
$sql = "SELECT * FROM books WHERE title LIKE '%" . $kw . "%'";  // Wrong
```

### ✗ Not Casting Numeric Values
```php
// BAD - Could accept string values
$stmt->execute([':id' => $id]);

// GOOD - Always cast
$stmt->execute([':id' => (int)$id]);
```

---

## Testing Security

### SQL Injection Test Cases

| Input | Vulnerable Result | Our Result |
|-------|------------------|-----------|
| `1' OR '1'='1` | All records | Book with ID 1 |
| `1; DROP TABLE books;` | Table deleted | ID casting prevents |
| `%' UNION SELECT * FROM users` | Retrieved user data | Literal string search |
| `1 AND SLEEP(5)` | 5 second delay | Cast to int(1) |

---

## Code Review Checklist

- [ ] All database queries use prepared statements
- [ ] Numeric parameters are cast to (int)
- [ ] String parameters are properly bound (not concatenated)
- [ ] User input is validated before database operations
- [ ] Output is escaped with htmlspecialchars() for HTML display
- [ ] Error messages don't expose database details
- [ ] Try-catch blocks handle PDOException
- [ ] Type hints are used in method signatures
- [ ] Repository pattern is used for database access
- [ ] No direct SQL in view files

---

## References for Developers

- **prepared statements**: Separate SQL structure from data
- **parameter binding**: Bind user input as data, not SQL code
- **type casting**: Enforce data types for numeric parameters
- **input validation**: Check length, format, range before use
- **output escaping**: Escape when displaying in HTML/JavaScript
- **error handling**: Catch exceptions, don't expose details
- **repository pattern**: Centralize database access
- **service layer**: Implement business logic with validation

