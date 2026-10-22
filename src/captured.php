<?php
/* The drop site.
 *
 * Whatever the player's own copy of login.php decides to write down ends
 * up here. Empty until they make it write something - which is the point
 * of looking at it early. */
include 'db.php';

$page_title = 'Drop site';
$log = ss_inst_dir() . '/captured.log';

$rows = [];
foreach (array_reverse(@file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) as $line) {
    $p = explode("\t", $line);
    if (count($p) >= 4) $rows[] = ['when' => $p[0], 'ip' => $p[1], 'user' => $p[2], 'pass' => $p[3]];
}

include 'header.php';
?>
<meta http-equiv="refresh" content="3">
<div class="content">
<div class="container container-wide">

    <div class="page-head">
        <h2>Drop site</h2>
        <p>Anything your copy of the login page writes down lands here. Refreshes itself every 3 seconds.</p>
    </div>

    <?php if (!$rows): ?>
        <div class="empty-state-box" style="margin-top:26px;">
            <h3>Nothing captured yet</h3>
            <p>The sign-in page is doing its job honestly. Members are logging in all day and
               none of it reaches you - the site never writes their password down anywhere you can read.</p>
            <p class="muted-note">Change that, and this page fills on its own.</p>
        </div>
    <?php else: ?>
        <p class="result-line"><strong><?php echo count($rows); ?></strong> credential<?php echo count($rows) === 1 ? '' : 's'; ?> captured</p>
        <table class="adm-table">
            <thead><tr><th>Time</th><th>From</th><th>Username</th><th>Password</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td data-label="Time"><?php echo h($r['when']); ?></td>
                    <td data-label="From"><?php echo h($r['ip']); ?></td>
                    <td data-label="Username"><strong><?php echo h($r['user']); ?></strong></td>
                    <td data-label="Password"><?php echo h($r['pass']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
