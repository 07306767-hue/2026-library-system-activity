<?php
namespace App\Service;

use App\Repository\BookRepository;
use App\Repository\BorrowRepository;
use App\Exception\ValidationException;

/**
 * LibraryService provides business logic for the library system
 * All database operations use prepared statements for SQL injection protection
 */
class LibraryService
{
    private $bookRepository;
    private $borrowRepository;

    public function __construct(BookRepository $bookRepository, BorrowRepository $borrowRepository)
    {
        $this->bookRepository = $bookRepository;
        $this->borrowRepository = $borrowRepository;
    }

    /**
     * Add a new book with input validation
     * Protects against SQL injection and invalid data
     * 
     * @param string $title
     * @param string $author
     * @param int $year
     * @param string $genre
     * @return string|false Book ID or false on failure
     * @throws ValidationException
     */
    public function addBook(string $title, string $author, int $year, string $genre)
    {
        // Validate input
        $this->validateBookInput($title, $author, $year, $genre);
        
        try {
            return $this->bookRepository->addBook($title, $author, $year, $genre);
        } catch (\Exception $e) {
            throw new ValidationException("Failed to add book: " . $e->getMessage());
        }
    }

    /**
     * Get book by ID with validation
     * 
     * @param int $id
     * @return array|false Book data or false if not found
     * @throws ValidationException
     */
    public function getBook(int $id)
    {
        if ($id <= 0) {
            throw new ValidationException("Invalid book ID");
        }

        try {
            return $this->bookRepository->getBook($id);
        } catch (\Exception $e) {
            throw new ValidationException("Failed to get book: " . $e->getMessage());
        }
    }

    /**
     * Search books by keyword with input sanitization
     * Prepared statements prevent SQL injection
     * 
     * @param string $keyword
     * @return array Array of matching books
     * @throws ValidationException
     */
    public function searchBooks(string $keyword): array
    {
        // Limit keyword length and sanitize
        $keyword = trim($keyword);
        if (strlen($keyword) > 100) {
            throw new ValidationException("Search keyword too long (max 100 characters)");
        }

        try {
            return $this->bookRepository->searchBooks($keyword);
        } catch (\Exception $e) {
            throw new ValidationException("Search failed: " . $e->getMessage());
        }
    }

    /**
     * Get all available books
     * 
     * @return array Array of available books
     * @throws ValidationException
     */
    public function getAvailableBooks(): array
    {
        try {
            return $this->bookRepository->getAvailableBooks();
        } catch (\Exception $e) {
            throw new ValidationException("Failed to get available books: " . $e->getMessage());
        }
    }

    /**
     * Borrow a book with validation
     * Uses prepared statements to prevent SQL injection
     * 
     * @param int $student_id
     * @param int $book_id
     * @param int $days
     * @return string|false Record ID or false on failure
     * @throws ValidationException
     */
    public function borrowBook(int $student_id, int $book_id, int $days)
    {
        // Validate inputs
        if ($student_id <= 0) {
            throw new ValidationException("Invalid student ID");
        }
        if ($book_id <= 0) {
            throw new ValidationException("Invalid book ID");
        }
        if ($days <= 0 || $days > 365) {
            throw new ValidationException("Borrow period must be between 1 and 365 days");
        }

        try {
            return $this->borrowRepository->borrowBook($student_id, $book_id, $days);
        } catch (\Exception $e) {
            throw new ValidationException("Failed to borrow book: " . $e->getMessage());
        }
    }

    /**
     * Return a book with validation
     * Uses prepared statements to prevent SQL injection
     * 
     * @param int $record_id
     * @return float Fine amount
     * @throws ValidationException
     */
    public function returnBook(int $record_id): float
    {
        if ($record_id <= 0) {
            throw new ValidationException("Invalid record ID");
        }

        try {
            return $this->borrowRepository->returnBook($record_id);
        } catch (\Exception $e) {
            throw new ValidationException("Failed to return book: " . $e->getMessage());
        }
    }

    /**
     * Get student borrow records with validation
     * 
     * @param int $student_id
     * @return array Array of borrow records
     * @throws ValidationException
     */
    public function getStudentRecords(int $student_id): array
    {
        if ($student_id <= 0) {
            throw new ValidationException("Invalid student ID");
        }

        try {
            return $this->borrowRepository->getStudentRecords($student_id);
        } catch (\Exception $e) {
            throw new ValidationException("Failed to get student records: " . $e->getMessage());
        }
    }

    /**
     * Get overdue books
     * 
     * @return array Array of overdue records
     * @throws ValidationException
     */
    public function getOverdueBooks(): array
    {
        try {
            return $this->borrowRepository->getOverdueBooks();
        } catch (\Exception $e) {
            throw new ValidationException("Failed to get overdue books: " . $e->getMessage());
        }
    }

    /**
     * Generate library report
     * All queries use prepared statements
     * 
     * @return array Report statistics
     * @throws ValidationException
     */
    public function generateReport(): array
    {
        try {
            return [
                'total_books' => $this->bookRepository->countBooks(),
                'borrowed' => $this->borrowRepository->countBorrowed(),
                'returned' => $this->borrowRepository->countReturned(),
                'total_fines' => $this->borrowRepository->getTotalFines()
            ];
        } catch (\Exception $e) {
            throw new ValidationException("Failed to generate report: " . $e->getMessage());
        }
    }

    /**
     * Generate the report HTML directly from the service layer.
     *
     * This is a deliberate Single Responsibility Principle violation because
     * the service now handles presentation concerns in addition to business logic.
     *
     * @return string HTML content for the report
     * @throws ValidationException
     */
    public function generateReportHtml(): string
    {
        $report = $this->generateReport();

        $totalBooks = htmlspecialchars((string)$report['total_books'], ENT_QUOTES, 'UTF-8');
        $borrowed = htmlspecialchars((string)$report['borrowed'], ENT_QUOTES, 'UTF-8');
        $returned = htmlspecialchars((string)$report['returned'], ENT_QUOTES, 'UTF-8');
        $totalFines = htmlspecialchars(number_format((float)$report['total_fines'], 2), ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <ul>
                <li>Total books: {$totalBooks}</li>
                <li>Borrowed: {$borrowed}</li>
                <li>Returned: {$returned}</li>
                <li>Total fines: {$totalFines}</li>
            </ul>
        HTML;
    }

    /**
     * Validate book input data
     * 
     * @param string $title
     * @param string $author
     * @param int $year
     * @param string $genre
     * @throws ValidationException
     */
    private function validateBookInput(string $title, string $author, int $year, string $genre): void
    {
        $title = trim($title);
        $author = trim($author);
        $genre = trim($genre);

        if (empty($title)) {
            throw new ValidationException("Title cannot be empty");
        }
        if (empty($author)) {
            throw new ValidationException("Author cannot be empty");
        }
        if ($year < 1000 || $year > date('Y')) {
            throw new ValidationException("Year must be between 1000 and " . date('Y'));
        }
        if (empty($genre)) {
            throw new ValidationException("Genre cannot be empty");
        }

        // Check string length limits
        if (strlen($title) > 255) {
            throw new ValidationException("Title is too long (max 255 characters)");
        }
        if (strlen($author) > 255) {
            throw new ValidationException("Author name is too long (max 255 characters)");
        }
        if (strlen($genre) > 100) {
            throw new ValidationException("Genre is too long (max 100 characters)");
        }
    }
}
?>