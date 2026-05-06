<?php
namespace App\Config;
class BorrowRecord
{
    private $id;
    private $studentId;
    private $bookId;
    private $borrowDate;
    private $dueDate;
    private $returnDate;
    private $fineAmount;
    private $status;

    public function __construct($id, $studentId, $bookId, $borrowDate, $dueDate, $returnDate, $fineAmount, $status)
    {
        $this->id = $id;
        $this->studentId = $studentId;
        $this->bookId = $bookId;
        $this->borrowDate = $borrowDate;
        $this->dueDate = $dueDate;
        $this->returnDate = $returnDate;
        $this->fineAmount = $fineAmount;
        $this->status = $status;
    }

    // Getters and setters for each property
    public function getId()
    {
        return $this->id;
    }

    public function getStudentId()
    {
        return $this->studentId;
    }

    public function getBookId()
    {
        return $this->bookId;
    }

    public function getBorrowDate()
    {
        return $this->borrowDate;
    }

    public function getDueDate()
    {
        return $this->dueDate;
    }

    public function getReturnDate()
    {
        return $this->returnDate;
    }

    public function getFineAmount()
    {
        return $this->fineAmount;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
