<?php
/* Start over. Wipes this browser's sandbox and signs it out. */
include 'db.php';
ss_inst_reset();
session_unset();
session_destroy();
setcookie('role', '', time() - 3600, '/');
header('Location: index.php?msg=reset');
exit;
