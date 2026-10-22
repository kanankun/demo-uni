<?php
/* The photos you have saved. */
include 'db.php';
require_once 'feed_card.php';
ss_require_login();

$me = ss_uid();
$page_title = 'Saved';

$stmt = $conn->prepare(
    "SELECT u.id, u.title, u.filename, u.created_at, us.username
       FROM bookmarks b
       JOIN uploads u  ON u.id = b.upload_id
       JOIN users   us ON us.id = u.user_id
      WHERE b.user_id = ?
      ORDER BY b.created_at DESC");
$stmt->bind_param("i", $me);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$extras = ss_feed_extras($conn, array_column($rows, 'id'));

include 'header.php';
?>
<div class="content">
<div class="container container-wide">

    <div class="page-head">
        <div>
            <h2>Saved</h2>
            <p><?php echo count($rows); ?> photo<?php echo count($rows) === 1 ? '' : 's'; ?> you kept for later.</p>
        </div>
        <a class="inline-link" href="index.php">&larr; BACK TO GALLERY</a>
    </div>

    <nav class="tabs feed-tabs" aria-label="Gallery views">
        <a class="tab" href="index.php">All</a>
        <a class="tab" href="index.php?feed=following">Following</a>
        <a class="tab active" href="saved.php">Saved</a>
    </nav>

    <?php if ($rows): ?>
        <div class="gallery-grid">
            <?php foreach ($rows as $row) echo ss_feed_card($row, $extras); ?>
        </div>
    <?php else: ?>
        <div class="empty-state-box">
            <h3>Nothing saved yet</h3>
            <p>Open a photo and press Save to keep it here.</p>
            <a class="btn btn-primary" href="index.php">Browse the gallery</a>
        </div>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
