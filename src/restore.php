<?php
/* Put the homepage back. Leaves everything else in the sandbox alone, so a
 * player keeps their shell and their captures. */
include 'db.php';
@unlink(ss_deface_file());
header('Location: index.php?msg=restored');
exit;
