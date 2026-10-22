<?php
/* A single notice. Read-only - no likes, no comments. An announcement is
 * something the registrar's office posted, not a social post students
 * react to. */
include 'db.php';
ss_require_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT a.id, a.title, a.body, a.created_at, us.username
                           FROM announcements a JOIN users us ON a.user_id = us.id
                          WHERE a.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();

if (!$notice) {
    http_response_code(404);
    $page_title = 'Not found';
    include 'header.php';
    echo '<div class="content"><div class="container container-mid"><div class="empty-state-box"><h3>Announcement not found</h3></div></div></div></body></html>';
    exit;
}

$page_title = $notice['title'];
include 'header.php';
?>
<div class="content">
<div class="container container-mid">

    <p><a class="inline-link" href="announcements.php">&larr; BACK TO ANNOUNCEMENTS</a></p>

    <div class="page-head">
        <div>
            <h2><?php echo h($notice['title']); ?></h2>
            <p><?php echo h($notice['username']); ?> &bull; <?php echo h($notice['created_at']); ?></p>
        </div>
    </div>

    <div class="notice-body"><?php echo nl2br(h($notice['body'])); ?></div>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
