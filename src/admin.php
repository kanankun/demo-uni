<?php
/* Administration: member list + password reset. */
include 'db.php';
ss_require_login();

if (!ss_is_admin($conn)) {
    http_response_code(403);
    exit("ACCESS DENIED");
}

$page_title = 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_pass'])) {
    $hash = $_POST['new_pass'];   // [VULN] stored exactly as typed
    $tid  = (int) $_POST['user_id'];
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $tid);
    $stmt->execute();
    ss_flash("Password updated.");
    header("Location: admin.php");
    exit;
}

$q = trim($_GET['search'] ?? '');
if ($q !== '') {
    $stmt = $conn->prepare(
        "SELECT id, username, email, is_admin, full_name, avatar, created_at FROM users
          WHERE username LIKE ? OR email LIKE ? ORDER BY id");
    $like = '%' . $q . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT id, username, email, is_admin, full_name, avatar, created_at FROM users ORDER BY id");
}

include 'header.php';
?>
<div class="content">
<div class="container container-wide">

    <div class="adm-toolbar">
        <div>
            <h2>Admin panel</h2>
            <p style="margin:6px 0 0; color:#666; font-size:.9rem;">Students and staff accounts, and password recovery.</p>
        </div>
        <form method="get" class="search-form" role="search">
            <input type="text" name="search" class="search-input" placeholder="Search users..."
                   aria-label="Search users" value="<?php echo h($q); ?>">
            <button type="submit" class="search-btn">SEARCH</button>
        </form>
    </div>

    <table class="adm-table">
        <thead>
            <tr><th style="width:60px;">ID</th><th>Account</th><th style="width:110px;">Matric no.</th><th style="width:100px;">Role</th><th style="width:170px; text-align:right;">Action</th></tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td class="num" data-label="ID"><?php echo (int) $u['id']; ?></td>
                <td data-label="Account">
                    <div class="adm-user">
                        <img class="avatar" src="<?php echo ss_avatar_src($u['id'], $u['avatar']); ?>" alt="" data-fallback="images/avatar-default.png">
                        <div class="who">
                            <strong><?php echo h($u['username']); ?></strong>
                            <span><?php echo h($u['email']); ?></span>
                            <span><?php echo h($u['full_name']); ?></span>
                        </div>
                    </div>
                </td>
                <td data-label="Matric no." class="mono"><?php echo h(ss_matric($u['id'], $u['created_at'], $u['is_admin'])); ?></td>
                <td data-label="Role">
                    <span class="badge <?php echo $u['is_admin'] ? 'badge-dark' : ''; ?>"><?php echo $u['is_admin'] ? 'ADMIN' : 'USER'; ?></span>
                </td>
                <td data-label="Action">
                    <div class="adm-actions">
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
                            <input type="hidden" name="new_pass" value="">
                            <button type="submit" class="btn btn-sm"
                                    data-reset-user="<?php echo h($u['username']); ?>">RESET PASSWORD</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($users->num_rows === 0): ?>
        <div class="empty-state-box"><h3>No members matched</h3><a class="btn" href="admin.php">CLEAR SEARCH</a></div>
    <?php endif; ?>
</div>
</div>
<div class="wave-footer"></div>
<script>
/* "Reset password" asks for the new value in a dialog, then posts the form. */
document.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-reset-user]');
    if (!b) return;
    e.preventDefault();
    var form = b.closest('form');
    SS.dialog({
        title: 'Reset password',
        message: 'Set a new password for ' + b.getAttribute('data-reset-user') + '.',
        fields: [{ name: 'pw', label: 'New password', required: true }],
        confirmText: 'Update password'
    }).then(function (v) {
        if (!v) return;
        form.elements.new_pass.value = v.pw;
        form.submit();
    });
});
</script>
</body>
</html>
