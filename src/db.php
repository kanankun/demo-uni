<?php
/* Database connection + session, included first by every page. */
require_once __DIR__ . '/lab.php';

/* Silent mode: query() returns false and $conn->error holds the message,
 * so the gallery can print the driver's own error. It is meant to. */
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli('127.0.0.1', 'root', 'root', 'uam_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

session_start();

require_once __DIR__ . '/instance.php';
ss_inst_init();
ss_inst_reap();

require_once __DIR__ . '/lib.php';
