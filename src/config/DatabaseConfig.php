<?php
namespace App\Config;

use PDO;
use PDOException;

/**
 * DatabaseConfig - Secure database configuration and connection handler
 *
 * This class provides a secure way to manage database connections with
 * prepared statements enabled by default to prevent SQL injection attacks.
 */
class DatabaseConfig
{
    private $host = "localhost";
    private $dbname = "library_system";
    private $username = "root";
    private $password = "";
    private $pdo = null;

    /**
     * Create and return a secure PDO database connection
     *
     * @return PDO
     * @throws PDOException
     */
    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            return $this->pdo;
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed. Please try again later.");
        }
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }
}
