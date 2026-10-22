<?php
/* UAM Student Portal - sign-in page.
 *
 * This is the only thing a guest can reach: a sign-in form, and a public
 * "Check Application Status" lookup next to it - exactly the kind of
 * no-login utility a real admissions site publishes. There is no
 * self-registration; accounts are issued by the registrar.
 *
 * [VULN - UNION-based SQL injection]
 * The status-check name field is concatenated straight into the query
 * below. The query is written on one line so a trailing comment really
 * does swallow the ORDER BY, and the driver error is printed when
 * something goes wrong. This is reachable with no account at all. */
include 'db.php';

/* If this player has defaced the portal, that is what everyone sees -
 * including the player, until they restore it. */
if (is_file(ss_deface_file())) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(ss_deface_file());
    echo '<div style="position:fixed;right:12px;bottom:12px;z-index:99999;'
       . 'font:12px/1.4 ui-monospace,Menlo,Consolas,monospace">'
       . '<a href="restore.php" style="background:#111;color:#fff;padding:7px 12px;'
       . 'border-radius:6px;text-decoration:none;opacity:.75">RESTORE SIGN-IN PAGE</a></div>';
    exit;
}

/* Already signed in? There is nothing else for you on this page. */
if (ss_uid()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Sign In';

/* --- the public status-check lookup -------------------------------- */
$q = trim($_GET['name'] ?? '');
$rows = [];
$db_error = "";
if ($q !== '') {
    $search_sql = "AND us.username LIKE '%$q%'";
    $sql = "SELECT a.id, us.username, a.camera_model, a.status, a.created_at FROM applications a JOIN users us ON a.user_id = us.id WHERE 1=1 $search_sql ORDER BY a.id DESC";
    $res = $conn->query($sql);
    $db_error = $res ? "" : $conn->error;
    while ($res && $r = $res->fetch_assoc()) $rows[] = $r;
}

include 'header.php';
?>
<div class="content">
<div class="auth-shell">
    <div class="auth-hero">
        <div class="crest"></div>
        <h1>UAM Student Portal</h1>
        <p>Universiti Anggerik Malaysia &mdash; sign in to access your dashboard, course registration, timetable, results and fees.</p>
        <p style="font-size:.8rem; opacity:.8;">No account? Registration is handled by the Registrar's Office, not from this page.</p>
    </div>

    <div class="auth-panels">
        <div>
            <h2>Sign in</h2>
            <p class="sub">Students and staff use the same form.</p>

            <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'reset'): ?>
                <div class="alert alert-info">Sandbox wiped - you are starting clean.</div>
            <?php elseif (!empty($_GET['msg']) && $_GET['msg'] === 'restored'): ?>
                <div class="alert alert-success">Sign-in page restored.</div>
            <?php elseif (!empty($_GET['msg']) && $_GET['msg'] === 'loggedout'): ?>
                <div class="alert alert-info">Signed out.</div>
            <?php endif; ?>

            <form method="post" action="<?php echo h('/' . ss_login_url()); ?>">
                <div class="field">
                    <label for="u">Username</label>
                    <input type="text" id="u" name="username" required autocomplete="username" autofocus>
                </div>
                <div class="field">
                    <label for="p">Password</label>
                    <input type="password" id="p" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block" data-loading-text="Checking...">Sign in</button>
            </form>
        </div>

        <hr class="status-divider">

        <div>
            <h2>Check application status</h2>
            <p class="sub">No account needed. Enter the applicant's name as submitted.</p>

            <form method="get" action="index.php">
                <div class="field">
                    <label for="name">Applicant name</label>
                    <input type="text" id="name" name="name" value="<?php echo h($q); ?>" placeholder="e.g. ali" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-block" data-loading-text="Checking...">Check status</button>
            </form>

            <?php if ($db_error): ?>
                <div class="alert alert-error" style="margin-top:14px;"><strong>SQL error:</strong> <?php echo h($db_error); ?></div>
            <?php elseif ($q !== ''): ?>
                <div class="status-results">
                    <?php if (!$rows): ?>
                        <p class="sub">No application matched "<?php echo h($q); ?>".</p>
                    <?php else: ?>
                        <table>
                            <thead><tr><th>Applicant</th><th>Programme</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?php echo h($r['username']); ?></td>
                                    <td><?php echo h($r['camera_model']); ?></td>
                                    <td><?php echo h($r['status']); ?></td>
                                    <td><?php echo h($r['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
