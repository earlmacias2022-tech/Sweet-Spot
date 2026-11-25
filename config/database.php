<?php
/**
 * Sweet Spot - Database Connection
 *
 * XAMPP defaults:
 * Host: localhost
 * Database: sweet_spot_db
 * Username: root
 * Password: (empty)
 *
 * If your MySQL username or password is different, update the values below.
 */

$host = 'localhost';
$dbname = 'sweet_spot_db';
$username = 'root';
$password = '';

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    // Keep database details out of public error messages.
    error_log('Sweet Spot database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Unable to connect to the database. Please check the server configuration.');
}
