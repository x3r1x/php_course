<?php
declare(strict_types=1);

namespace App\Connection;

use DateTime;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;

require_once __DIR__ . '/../../config.php';

class Database {

    static function connectDatabase() : PDO
    {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        try {
            return new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // arrays
                PDO::ATTR_EMULATE_PREPARES => false, // statements
                PDO::ATTR_STRINGIFY_FETCHES => false, // int -> str
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Error: " . $e->getMessage());
        }
    }
}