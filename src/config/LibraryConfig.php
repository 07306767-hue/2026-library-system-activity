<?php
namespace App\Config;
class LibraryConfig
{
    private $maxBorrowDays = 14;
    private $finePerDay = 0.50;

    public function getMaxBorrowDays()
    {
        return $this->maxBorrowDays;
    }

    public function getFinePerDay()
    {
        return $this->finePerDay;
    }
}
?>