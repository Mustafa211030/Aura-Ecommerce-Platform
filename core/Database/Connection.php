<?php
/**
 * Database Connection — PDO Singleton
 *
 * Ensures a single PDO instance is reused across the application.
 */

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOException;

class Connection
{
    /** @var Connection|null Singleton instance */
    private static ?Connection $instance = null;

    /** @var PDO The active PDO connection */
    private PDO $pdo;

    /**
     * Private constructor — opens the PDO connection.
     *
     * @throws \RuntimeException on connection failure
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose credentials — log internally
            error_log('[AURA DB] Connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed. Please try again later.');
        }
    }

    /**
     * Returns the single Connection instance.
     *
     * @return Connection
     */
    public static function getInstance(): Connection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the underlying PDO object.
     *
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /** Prevent cloning of the singleton */
    private function __clone() {}

    /** Prevent unserialization of the singleton */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton.');
    }
}
