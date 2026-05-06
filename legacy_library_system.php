<?php
declare(strict_types=1);
namespace legacy_library_system;
use PDO;
use PDOException;
class lib_system
{
    public $db_host = "localhost";
    public $db_user = "root";
    public $db_pass = "";
    public $db_name = "library_system";
    public $conn;
    public $fine_rate = 5;
    
    function connect()
    {
        try {
            $this->conn = new PDO("mysql:host={$this->db_host};dbname={$this->db_name}", $this->db_user, $this->db_pass);
            // Set PDO to throw exceptions and use prepared statements
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("db error: " . $e->getMessage());
        }
    }
    
    /**
     * Add a book using prepared statement (SQL Injection safe)
     */
    function addBook($title, $author, $year, $genre)
    {
        try {
            $sql = "INSERT INTO books(title, author, year, genre) VALUES(:title, :author, :year, :genre)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':year' => (int)$year,
                ':genre' => $genre
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            die("Error adding book: " . $e->getMessage());
        }
    }
    
    /**
     * Get a book by ID using prepared statement (SQL Injection safe)
     */
    function getBook($id)
    {
        try {
            $sql = "SELECT * FROM books WHERE book_id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error getting book: " . $e->getMessage());
        }
    }
    
    /**
     * Borrow a book using prepared statement (SQL Injection safe)
     */
    function borrowBook($student_id, $book_id, $days)
    {
        try {
            $due_date = date('Y-m-d', strtotime('+' . (int)$days . ' days'));
            $borrow_date = date('Y-m-d');
            
            $sql = "INSERT INTO borrow_records(student_id, book_id, borrow_date, due_date, status) 
                    VALUES(:student_id, :book_id, :borrow_date, :due_date, :status)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':student_id' => (int)$student_id,
                ':book_id' => (int)$book_id,
                ':borrow_date' => $borrow_date,
                ':due_date' => $due_date,
                ':status' => 'borrowed'
            ]);
            return true;
        } catch (PDOException $e) {
            die("Error borrowing book: " . $e->getMessage());
        }
    }
    
    /**
     * Return a book using prepared statement (SQL Injection safe)
     */
    function returnBook($return_id)
    {
        try {
            // Get the record using prepared statement
            $sql = "SELECT * FROM borrow_records WHERE record_id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => (int)$return_id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$r) {
                die("Record not found");
            }
            
            $due = strtotime($r['due_date']);
            $today = strtotime(date('Y-m-d'));
            $diff = ($today - $due) / (60 * 60 * 24);
            $fine = 0;
            if ($diff > 0) {
                $fine = $diff * $this->fine_rate;
            }
            
            // Update using prepared statement
            $sql2 = "UPDATE borrow_records SET return_date = :return_date, fine_amount = :fine, status = :status 
                     WHERE record_id = :id";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([
                ':return_date' => date('Y-m-d'),
                ':fine' => $fine,
                ':status' => 'returned',
                ':id' => (int)$return_id
            ]);
            return $fine;
        } catch (PDOException $e) {
            die("Error returning book: " . $e->getMessage());
        }
    }
    
    /**
     * List all books using prepared statement
     */
    function listBooks()
    {
        try {
            $sql = "SELECT * FROM books";
            $result = $this->conn->query($sql);
            echo "<table border='1'><tr><th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Genre</th></tr>";
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr><td>" . htmlspecialchars($row['book_id'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['year'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($row['genre'], ENT_QUOTES, 'UTF-8') . "</td></tr>";
            }
            echo "</table>";
        } catch (PDOException $e) {
            die("Error listing books: " . $e->getMessage());
        }
    }
    
    /**
     * Search books by keyword using prepared statement (SQL Injection safe)
     */
    function searchBooks($kw)
    {
        try {
            $sql = "SELECT * FROM books WHERE title LIKE :keyword OR author LIKE :keyword";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':keyword' => '%' . $kw . '%']);
            $books = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $books[] = $row;
            }
            return $books;
        } catch (PDOException $e) {
            die("Error searching books: " . $e->getMessage());
        }
    }
    
    /**
     * Get overdue books using prepared statement (SQL Injection safe)
     */
    function getOverdueBooks()
    {
        try {
            $sql = "SELECT br.*, b.title, s.name FROM borrow_records br 
                    JOIN books b ON br.book_id = b.book_id 
                    JOIN students s ON br.student_id = s.student_id 
                    WHERE br.due_date < :today AND br.status = :status";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':today' => date('Y-m-d'),
                ':status' => 'borrowed'
            ]);
            $list = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $list[] = $row;
            }
            return $list;
        } catch (PDOException $e) {
            die("Error getting overdue books: " . $e->getMessage());
        }
    }
    
    /**
     * Generate report using prepared statements
     */
    function generateReport()
    {
        try {
            // Total books
            $stmt = $this->conn->prepare("SELECT COUNT(*) as c FROM books");
            $stmt->execute();
            $totalBooks = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
            
            // Total borrowed
            $stmt = $this->conn->prepare("SELECT COUNT(*) as c FROM borrow_records WHERE status = :status");
            $stmt->execute([':status' => 'borrowed']);
            $totalBorrowed = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
            
            // Total returned
            $stmt = $this->conn->prepare("SELECT COUNT(*) as c FROM borrow_records WHERE status = :status");
            $stmt->execute([':status' => 'returned']);
            $totalReturned = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
            
            // Total fines
            $stmt = $this->conn->prepare("SELECT SUM(fine_amount) as s FROM borrow_records WHERE fine_amount > 0");
            $stmt->execute();
            $totalFines = $stmt->fetch(PDO::FETCH_ASSOC)['s'];
            
            echo "<h2>Library Report</h2>";
            echo "<p>Total Books: " . htmlspecialchars($totalBooks, ENT_QUOTES, 'UTF-8') . "</p>";
            echo "<p>Borrowed: " . htmlspecialchars($totalBorrowed, ENT_QUOTES, 'UTF-8') . "</p>";
            echo "<p>Returned: " . htmlspecialchars($totalReturned, ENT_QUOTES, 'UTF-8') . "</p>";
            echo "<p>Total Fines Collected: $" . htmlspecialchars($totalFines, ENT_QUOTES, 'UTF-8') . "</p>";
        } catch (PDOException $e) {
            die("Error generating report: " . $e->getMessage());
        }
    }
}
$library = new lib_system();
$library->connect();

if (isset($_GET['act'])) {
    // Sanitize GET parameter to prevent injection
    $action = htmlspecialchars($_GET['act'], ENT_QUOTES, 'UTF-8');
    
    if ($action == 'add') {
        // Validate POST parameters exist
        if (isset($_POST['t'], $_POST['a'], $_POST['y'], $_POST['g'])) {
            // Trim and validate input
            $title = trim($_POST['t']);
            $author = trim($_POST['a']);
            $year = intval($_POST['y']);
            $genre = trim($_POST['g']);
            
            // Validate required fields
            if (!empty($title) && !empty($author) && $year > 0 && !empty($genre)) {
                $library->addBook($title, $author, $year, $genre);
            } else {
                echo "Error: All fields are required and year must be positive.";
            }
        }
    } elseif ($action == 'list') {
        $library->listBooks();
    } elseif ($action == 'report') {
        $library->generateReport();
    } else {
        echo "Error: Invalid action.";
    }
}
