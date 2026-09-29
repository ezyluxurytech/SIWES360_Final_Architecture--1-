<?php
/**
 * SIWES360 - Database Connection Configuration
 * Uses PHP Data Objects (PDO) for secure, prepared SQL execution.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'siwes360');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log error privately; never reveal raw credentials
            error_log("Database connection failure: " . $e->getMessage());
            die("Critical Error: Database connection could not be established. Ensure MySQL is running in XAMPP and 'siwes360' database is imported.");
        }
    }

    return $pdo;
}
