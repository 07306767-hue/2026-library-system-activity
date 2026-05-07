<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\DatabaseConfig;
use App\Repository\BookRepository;
use App\Repository\BorrowRepository;
use App\Service\LibraryService;
use App\Exception\ValidationException;

$pdo = (new DatabaseConfig())->connect();
$bookRepository = new BookRepository($pdo);
$borrowRepository = new BorrowRepository($pdo);
$libraryService = new LibraryService($bookRepository, $borrowRepository);

$action = $_REQUEST['action'] ?? 'list';
$messages = [];
$errors = [];
$books = [];
$searchResults = [];
$overdueRecords = [];
$reportHtml = '';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderTable(array $rows): void
{
    if (empty($rows)) {
        echo '<p>No records found.</p>';
        return;
    }

    echo '<table border="1" cellpadding="6" cellspacing="0">';
    echo '<thead><tr>';
    foreach (array_keys($rows[0]) as $column) {
        echo '<th>' . escape($column) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . escape((string)$value) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'add_book':
                $title = trim($_POST['title'] ?? '');
                $author = trim($_POST['author'] ?? '');
                $year = (int)($_POST['year'] ?? 0);
                $genre = trim($_POST['genre'] ?? '');
                $id = $libraryService->addBook($title, $author, $year, $genre);
                $messages[] = 'Book added successfully with ID ' . escape((string)$id) . '.';
                break;

            case 'borrow_book':
                $studentId = (int)($_POST['student_id'] ?? 0);
                $bookId = (int)($_POST['book_id'] ?? 0);
                $days = (int)($_POST['days'] ?? 14);
                $recordId = $libraryService->borrowBook($studentId, $bookId, $days);
                $messages[] = 'Borrow record created with ID ' . escape((string)$recordId) . '.';
                break;

            case 'return_book':
                $recordId = (int)($_POST['record_id'] ?? 0);
                $fine = $libraryService->returnBook($recordId);
                $messages[] = 'Book returned successfully. Fine: ' . escape(number_format($fine, 2)) . '.';
                break;

            default:
                break;
        }
    } catch (ValidationException $e) {
        $errors[] = $e->getMessage();
    } catch (Exception $e) {
        $errors[] = 'System error: ' . $e->getMessage();
    }
}

if ($action === 'search' && isset($_REQUEST['keyword'])) {
    try {
        $searchResults = $libraryService->searchBooks((string)($_REQUEST['keyword'] ?? ''));
    } catch (ValidationException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($action === 'overdue') {
    try {
        $overdueRecords = $libraryService->getOverdueBooks();
    } catch (ValidationException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($action === 'report') {
    try {
        $reportHtml = $libraryService->generateReportHtml();
    } catch (ValidationException $e) {
        $errors[] = $e->getMessage();
    }
}

if ($action === 'list') {
    $books = $bookRepository->getAllBooks();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        nav a { margin-right: 12px; text-decoration: none; }
        form { margin-bottom: 24px; padding: 12px; border: 1px solid #ccc; width: 100%; max-width: 640px; }
        label { display: block; margin: 8px 0 4px; }
        input[type="text"], input[type="number"] { width: 100%; max-width: 320px; padding: 6px; }
        button { padding: 8px 14px; margin-top: 8px; }
        .message { background: #e7f7e7; border: 1px solid #8fbc8f; padding: 10px; margin-bottom: 12px; }
        .error { background: #f7e7e7; border: 1px solid #d18f8f; padding: 10px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Library System</h1>
    <nav>
        <a href="?action=list">All Books</a>
        <a href="?action=search">Search Books</a>
        <a href="?action=add_book">Add Book</a>
        <a href="?action=borrow_book">Borrow Book</a>
        <a href="?action=return_book">Return Book</a>
        <a href="?action=overdue">Overdue</a>
        <a href="?action=report">Report</a>
    </nav>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="message"><?php echo escape($message); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="error"><?php echo escape($error); ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($action === 'add_book'): ?>
        <form method="post" action="?action=add_book">
            <input type="hidden" name="action" value="add_book">
            <h2>Add Book</h2>
            <label for="title">Title</label>
            <input id="title" name="title" type="text" required>
            <label for="author">Author</label>
            <input id="author" name="author" type="text" required>
            <label for="year">Year</label>
            <input id="year" name="year" type="number" min="1000" max="2100" required>
            <label for="genre">Genre</label>
            <input id="genre" name="genre" type="text" required>
            <button type="submit">Save Book</button>
        </form>
    <?php elseif ($action === 'borrow_book'): ?>
        <form method="post" action="?action=borrow_book">
            <input type="hidden" name="action" value="borrow_book">
            <h2>Borrow Book</h2>
            <label for="student_id">Student ID</label>
            <input id="student_id" name="student_id" type="number" min="1" required>
            <label for="book_id">Book ID</label>
            <input id="book_id" name="book_id" type="number" min="1" required>
            <label for="days">Borrow Days</label>
            <input id="days" name="days" type="number" min="1" max="365" value="14" required>
            <button type="submit">Borrow</button>
        </form>
    <?php elseif ($action === 'return_book'): ?>
        <form method="post" action="?action=return_book">
            <input type="hidden" name="action" value="return_book">
            <h2>Return Book</h2>
            <label for="record_id">Borrow Record ID</label>
            <input id="record_id" name="record_id" type="number" min="1" required>
            <button type="submit">Return</button>
        </form>
    <?php elseif ($action === 'search'): ?>
        <form method="get" action=".">
            <input type="hidden" name="action" value="search">
            <h2>Search Books</h2>
            <label for="keyword">Keyword</label>
            <input id="keyword" name="keyword" type="text" required>
            <button type="submit">Search</button>
        </form>
        <?php if (isset($_REQUEST['keyword'])): ?>
            <h2>Search Results</h2>
            <?php renderTable($searchResults); ?>
        <?php endif; ?>
    <?php elseif ($action === 'overdue'): ?>
        <h2>Overdue Records</h2>
        <?php renderTable($overdueRecords); ?>
    <?php elseif ($action === 'report'): ?>
        <h2>Report</h2>
        <?php echo $reportHtml ?: '<p>No report data available.</p>'; ?>
    <?php else: ?>
        <h2>All Books</h2>
        <?php renderTable($books); ?>
    <?php endif; ?>
</body>
</html>
