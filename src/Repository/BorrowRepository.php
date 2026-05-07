<?php
namespace App\Repository;

use PDO;
use PDOException;

/**
 * BorrowRepository handles all database operations related to borrow records
 * Uses prepared statements to prevent SQL injection attacks
 */
class BorrowRepository
{
    private $db;
    private $fine_rate = 5;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a borrow record using prepared statement 
     * 
     * @param int $student_id
     * @param int $book_id
     * @param int $days Number of days to borrow
     * @return string|false Last inserted ID or false on failure
     */
    public function borrowBook(int $student_id, int $book_id, int $days)
    {
        try {
            $borrow_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+' . $days . ' days'));
            
            $sql = "INSERT INTO borrow_records(student_id, book_id, borrow_date, due_date, status) 
                    VALUES(:student_id, :book_id, :borrow_date, :due_date, :status)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':student_id' => $student_id,
                ':book_id' => $book_id,
                ':borrow_date' => $borrow_date,
                ':due_date' => $due_date,
                ':status' => 'borrowed'
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new \Exception("Error borrowing book: " . $e->getMessage());
        }
    }

    /**
     * Return a book using prepared statement 
     * Calculates fine if book is overdue
     * 
     * @param int $record_id
     * @return float Fine amount
     */
    public function returnBook(int $record_id): float
    {
        try {
            // Get the record using prepared statement
            $sql = "SELECT * FROM borrow_records WHERE record_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                throw new \Exception("Record not found");
            }
            
            // Calculate fine
            $due = strtotime($record['due_date']);
            $today = strtotime(date('Y-m-d'));
            $diff = ($today - $due) / (60 * 60 * 24);
            $fine = max(0, $diff * $this->fine_rate);
            
            // Update record using prepared statement
            $sql = "UPDATE borrow_records SET return_date = :return_date, fine_amount = :fine, status = :status 
                    WHERE record_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':return_date' => date('Y-m-d'),
                ':fine' => $fine,
                ':status' => 'returned',
                ':id' => $record_id
            ]);
            
            return $fine;
        } catch (PDOException $e) {
            throw new \Exception("Error returning book: " . $e->getMessage());
        }
    }

    /**
     * Get a borrow record using prepared statement 
     * 
     * @param int $record_id
     * @return array|false Borrow record data or false if not found
     */
    public function getRecord(int $record_id)
    {
        try {
            $sql = "SELECT * FROM borrow_records WHERE record_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $record_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting record: " . $e->getMessage());
        }
    }

    /**
     * Get all borrow records for a specific student using prepared statement 
     * 
     * @param int $student_id
     * @return array Array of borrow records
     */
    public function getStudentRecords(int $student_id): array
    {
        try {
            $sql = "SELECT br.*, b.title, b.author FROM borrow_records br 
                    JOIN books b ON br.book_id = b.book_id 
                    WHERE br.student_id = :student_id 
                    ORDER BY br.borrow_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting student records: " . $e->getMessage());
        }
    }

    /**
     * Get all overdue books using prepared statement 
     * 
     * @return array Array of overdue borrow records
     */
    public function getOverdueBooks(): array
    {
        try {
            $sql = "SELECT br.*, b.title, b.author, s.name, s.email FROM borrow_records br 
                    JOIN books b ON br.book_id = b.book_id 
                    JOIN students s ON br.student_id = s.student_id 
                    WHERE br.due_date < :today AND br.status = :status 
                    ORDER BY br.due_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':today' => date('Y-m-d'),
                ':status' => 'borrowed'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting overdue books: " . $e->getMessage());
        }
    }

    /**
     * Get all currently borrowed books using prepared statement
     * 
     * @return array Array of borrowed borrow records
     */
    public function getBorrowedBooks(): array
    {
        try {
            $sql = "SELECT br.*, b.title, b.author, s.name FROM borrow_records br 
                    JOIN books b ON br.book_id = b.book_id 
                    JOIN students s ON br.student_id = s.student_id 
                    WHERE br.status = :status 
                    ORDER BY br.borrow_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => 'borrowed']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting borrowed books: " . $e->getMessage());
        }
    }

    /**
     * Count total borrowed records
     * 
     * @return int Total borrowed count
     */
    public function countBorrowed(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM borrow_records WHERE status = :status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => 'borrowed']);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            throw new \Exception("Error counting borrowed records: " . $e->getMessage());
        }
    }

    /**
     * Count total returned records
     * 
     * @return int Total returned count
     */
    public function countReturned(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM borrow_records WHERE status = :status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => 'returned']);
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            throw new \Exception("Error counting returned records: " . $e->getMessage());
        }
    }

    /**
     * Get total fines collected using prepared statement
     * 
     * @return float Total fines amount
     */
    public function getTotalFines(): float
    {
        try {
            $sql = "SELECT SUM(fine_amount) as total FROM borrow_records WHERE fine_amount > 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            throw new \Exception("Error getting total fines: " . $e->getMessage());
        }
    }

    /**
     * Get fines for a specific student using prepared statement 
     * 
     * @param int $student_id
     * @return float Total fines for student
     */
    public function getStudentFines(int $student_id): float
    {
        try {
            $sql = "SELECT SUM(fine_amount) as total FROM borrow_records WHERE student_id = :student_id AND fine_amount > 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':student_id' => $student_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            throw new \Exception("Error getting student fines: " . $e->getMessage());
        }
    }
}
?>