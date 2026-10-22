<?php
/* Announcements board. Read-only for students; only the admin posts.
 *
 * Deliberately NOT part of the vulnerability chain - this is a static,
 * parameter-free listing query. The injectable search box lives on
 * students.php's directory search instead.
 *
 * Its own table, not the student photo feed. A notice board that borrows
 * the gallery's `uploads` rows ends up showing every student's photo
 * caption as a "university announcement" - this lists only what the
 * registrar's office actually posted. */
include 'db.php';
ss_require_login();

$admin = ss_is_admin($conn);
$page_title = 'Announcements';

$res = $conn->query("SELECT a.id, a.title, a.created_at, us.username FROM announcements a JOIN users us ON a.user_id = us.id ORDER BY a.created_at DESC");
$rows = [];
while ($res && $r = $res->fetch_assoc()) $rows[] = $r;

include 'header.php';
?>
<div class="content">
<div class="container container-mid">

    <div class="page-head">
        <div>
            <h2>Announcements</h2>
            <p>Notices from the registrar and faculty offices.</p>
        </div>
        <?php if ($admin): ?><a class="btn btn-primary" href="post_announcement.php">+ Post announcement</a><?php endif; ?>
    </div>

    <?php if (!$rows): ?>
        <div class="empty-state-box"><h3>No announcements yet</h3></div>
    <?php else: ?>
        <?php foreach ($rows as $n): ?>
            <a class="notice-row" href="announcement.php?id=<?php echo (int) $n['id']; ?>">
                <svg class="ni" viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5L14 3.5zM7 12h10v1.6H7V12zm0 3.6h10v1.6H7v-1.6zM7 8.4h5V10H7V8.4z"/></svg>
                <div class="nt">
                    <strong><?php echo h($n['title']); ?></strong>
                    <span><?php echo h($n['username']); ?> &bull; <?php echo h($n['created_at']); ?></span>
                </div>
                <svg class="nc" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
