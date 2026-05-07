<?php
namespace App\Config;

class LibraryConfig
{
    private $maxBorrowDays = 14;
    private $finePerDay = 0.50;

    public function getMaxBorrowDays(): int
    {
        return $this->maxBorrowDays;
    }

    public function getFinePerDay(): float
    {
        return $this->finePerDay;
    }
}
