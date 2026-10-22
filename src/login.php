<?php
/* Each browser signs in through its own copy of the page, in its own
 * sandbox. This keeps old links working. */
include 'db.php';
header('Location: /' . ss_login_url());
exit;
