<?php
/* University A Student Portal - sign-in page.
 *
 * This is the only thing a guest can reach: a sign-in form. There is no
 * self-registration; accounts are issued by the registrar. The SQL
 * injection used to live in a public "Check application status" box
 * here - it doesn't anymore. It's in the Student Directory search
 * instead, reachable only once signed in. See students.php. */
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

include 'header.php';
?>
<div class="content">
<div class="auth-shell">
    <div class="auth-hero">
        <div class="crest"></div>
        <h1>University A</h1>
        <p>Sign in to manage your courses, timetable, results and fees.</p>
    </div>

    <div class="auth-panels">
        <div>
            <h2>Sign in</h2>

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
    </div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
