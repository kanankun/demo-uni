<?php
include 'db.php';

$_SESSION = [];
session_destroy();
setcookie("role", "", time() - 3600, "/");

header("Location: index.php?msg=loggedout");
exit;
