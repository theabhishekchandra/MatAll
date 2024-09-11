<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'u407379976_matall_user');
define('DB_PASSWORD', 'TECHupdates@2024');
define('DB_NAME', 'u407379976_db_matall');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
