<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/* -------------------------
   Session Configuration
-------------------------- */
session_set_cookie_params([
    'path' => $_ENV['SESSION_PATH'] ?? '/',
    'secure' => ($_ENV['SESSION_SECURE'] ?? 'false') === 'true',
    'httponly' => true,
    'samesite' => $_ENV['SESSION_SAMESITE'] ?? 'Lax'
]);

session_name($_ENV['SESSION_NAME'] ?? 'script');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------------
   Database Connection
-------------------------- */
$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* -------------------------
   Additional Config
-------------------------- */
$ipServer = $_ENV['IP_SERVER'];
$hosCode = $_ENV['HOS_CODE'];
$apiKey = $_ENV['API_KEY'];
$summaryKey = $_ENV['SUMMARY_KEY'];
$nodejs = $_ENV['NODEJS_URL'];
?>