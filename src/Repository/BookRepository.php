<?php
namespace App\Repository;

use PDO;
use PDOException;

/**
 * BookRepository handles all database operations related to books
 * Uses prepared statements to prevent SQL injection attacks
 */
class BookRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Add a new book using prepared statement 
     * 
     * @param string $title
     * @param string $author
     * @param int $year
     * @param string $genre
     * @return string|false Last inserted ID or false on failure
     */
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

    /**
     * Get a book by ID using prepared statement 
     * 
     * @param int $id
     * @return array|false Book data or false if not found
     */
    public function getBook(int $id)
    {
        try {
            $sql = "SELECT * FROM books WHERE book_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting book: " . $e->getMessage());
        }
    }

    /**
     * Search books by keyword using prepared statement 
     * 
     * @param string $keyword
     * @return array Array of matching books
     */
    public function searchBooks(string $keyword): array
    {
        try {
            $sql = "SELECT * FROM books WHERE title LIKE :keyword OR author LIKE :keyword ORDER BY title";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':keyword' => '%' . $keyword . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error searching books: " . $e->getMessage());
        }
    }

    /**
     * Get all books using prepared statement
     * 
     * @return array Array of all books
     */
    public function getAllBooks(): array
    {
        try {
            $sql = "SELECT * FROM books ORDER BY title";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting all books: " . $e->getMessage());
        }
    }

    /**
     * Get available books (not currently borrowed) using prepared statement
     * 
     * @return array Array of available books
     */
    public function getAvailableBooks(): array
    {
        try {
            $sql = "SELECT b.* FROM books b 
                    WHERE b.book_id NOT IN (
                        SELECT book_id FROM borrow_records WHERE status = :status
                    )
                    ORDER BY b.title";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => 'borrowed']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Error getting available books: " . $e->getMessage());
        }
    }

    /**
     * Update book information using prepared statement 
     * 
     * @param int $id
     * @param array $data Array with keys: title, author, year, genre
     * @return bool True on success
     */
    public function updateBook(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE books SET title = :title, author = :author, year = :year, genre = :genre WHERE book_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'] ?? null,
                ':author' => $data['author'] ?? null,
                ':year' => $data['year'] ?? null,
                ':genre' => $data['genre'] ?? null,
                ':id' => $id
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \Exception("Error updating book: " . $e->getMessage());
        }
    }

    /**
     * Delete a book using prepared statement 
     * 
     * @param int $id
     * @return bool True on success
     */
    public function deleteBook(int $id): bool
    {
        try {
            $sql = "DELETE FROM books WHERE book_id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new \Exception("Error deleting book: " . $e->getMessage());
        }
    }

    /**
     * Count total books
     * 
     * @return int Total number of books
     */
    public function countBooks(): int
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM books";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (PDOException $e) {
            throw new \Exception("Error counting books: " . $e->getMessage());
        }
    }
}
?>