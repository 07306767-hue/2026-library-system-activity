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
     * Get the database host
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the database name
     * @return string
     */
    public function getDbName(): string
    {
        return $this->dbname;
    }

    /**
     * Get the database username
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the database password
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Create and return a secure PDO database connection
     * 
     * Features:
     * - Error mode set to throw exceptions
     * - Uses prepared statements (prevents SQL injection)
     * - Charset set to UTF-8
     * 
     * @return PDO A configured PDO connection object
     * @throws PDOException If connection fails
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
                    // Set error mode to throw exceptions
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    
                    // Use prepared statements (essential for SQL injection prevention)
                    PDO::ATTR_EMULATE_PREPARES => false,
                    
                    // Set default fetch mode
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    
                    // Enable persistent connections (optional, for performance)
                    // PDO::ATTR_PERSISTENT => true,
                ]
            );

            return $this->pdo;
        } catch (PDOException $e) {
            // Log error securely, don't expose details to users
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed. Please try again later.");
        }
    }

    /**
     * Close the database connection
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Check if connected to database
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }
}
?>