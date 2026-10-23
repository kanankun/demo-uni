<?php
/* Student directory: search by name over the official-looking student
 * record list - matric number, programme, status. No social features -
 * no following, no like counts.
 *
 * [VULN - UNION-based SQL injection]
 * The search field is concatenated straight into the query below. The
 * query is written on one line so a trailing comment really does
 * swallow the ORDER BY, and the driver error is printed when something
 * goes wrong. Reachable by any signed-in account, student or staff -
 * there is nothing admin-only about reaching this page, only about what
 * a search here can be made to print. */
include 'db.php';
ss_require_login();

$me = ss_uid();
$q  = trim($_GET['q'] ?? '');

$students = [];
$db_error = "";

if ($q !== '') {
    $search_sql = "AND u.username LIKE '%$q%'";
    $sql = "SELECT u.id, u.username, u.avatar, u.full_name, u.bio, u.created_at, u.is_admin FROM users u WHERE 1=1 $search_sql AND u.is_admin = 0 ORDER BY u.id ASC";
    $res = $conn->query($sql);
    $db_error = $res ? "" : $conn->error;
    while ($res && $r = $res->fetch_assoc()) $students[] = $r;
} else {
    $res = $conn->query("SELECT u.id, u.username, u.avatar, u.full_name, u.bio, u.created_at, u.is_admin FROM users u WHERE u.is_admin = 0 ORDER BY u.id ASC");
    while ($res && $r = $res->fetch_assoc()) $students[] = $r;
}

$total = count($students);

$page_title = 'Student directory';
include 'header.php';
?>
<div class="content">
<div class="container container-wide">

    <div class="page-head">
        <div>
            <h2>Student directory</h2>
            <p>Official record list. Search by name to find a matric number or confirm a programme.</p>
        </div>
    </div>

    <form method="get" class="student-tools">
        <div class="field">
            <label for="q">Search by name</label>
            <input type="text" id="q" name="q" value="<?php echo h($q); ?>" placeholder="e.g. Sarah">
        </div>
        <div class="student-tools-actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <a class="btn" href="students.php">Reset</a>
        </div>
    </form>

    <?php if ($db_error): ?>
        <div class="alert alert-error" style="margin-top:14px;"><strong>SQL error:</strong> <?php echo h($db_error); ?></div>
    <?php else: ?>
        <p class="result-line"><?php echo $total; ?> record<?php echo $total === 1 ? '' : 's'; ?></p>

        <?php if (!$students): ?>
            <div class="empty-state-box"><h3>No records matched</h3><a class="btn" href="students.php">Clear search</a></div>
        <?php else: ?>
            <div class="directory-table-wrap">
            <table class="adm-table directory-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Matric no.</th>
                        <th>Programme</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $m): ?>
                    <tr>
                        <td class="dir-avatar-cell">
                            <img class="avatar" src="<?php echo h(ss_avatar_src($m['id'], $m['avatar'])); ?>"
                                 alt="" data-fallback="images/avatar-default.png" loading="lazy">
                        </td>
                        <td data-label="Name">
                            <a href="<?php echo h(ss_profile_url($m['id'])); ?>"><?php echo h($m['full_name'] ?: $m['username']); ?></a>
                        </td>
                        <td data-label="Matric no." class="mono"><?php echo h(ss_matric($m['id'], $m['created_at'], $m['is_admin'])); ?></td>
                        <td data-label="Programme"><?php echo h($m['bio'] ?: '-'); ?></td>
                        <td data-label="Status">
                            <span class="badge <?php echo $m['is_admin'] ? 'badge-dark' : 'badge-ok'; ?>">
                                <?php echo $m['is_admin'] ? 'STAFF' : 'ACTIVE'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
