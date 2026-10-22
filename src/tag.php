<?php
/* Every photo carrying one tag. */
include 'db.php';
require_once 'feed_card.php';

const SS_TAG_PAGE = 12;

$t    = trim($_GET['t'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$page_title = $t !== '' ? '#' . $t : 'Tags';

$total = 0;
$rows  = [];

if ($t !== '') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM upload_tags WHERE tag = ?");
    $stmt->bind_param("s", $t);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_row()[0];

    $pages = max(1, (int) ceil($total / SS_TAG_PAGE));
    if ($page > $pages) $page = $pages;
    $off = ($page - 1) * SS_TAG_PAGE;
    $lim = SS_TAG_PAGE;

    $stmt = $conn->prepare(
        "SELECT u.id, u.title, u.filename, u.created_at, us.username
           FROM upload_tags tg
           JOIN uploads u  ON u.id = tg.upload_id
           JOIN users   us ON us.id = u.user_id
          WHERE tg.tag = ?
          ORDER BY u.created_at DESC
          LIMIT ?, ?");
    $stmt->bind_param("sii", $t, $off, $lim);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
} else {
    $pages = 1;
}

$extras = ss_feed_extras($conn, array_column($rows, 'id'));
$chips  = ss_popular_tags($conn, 16);

include 'header.php';
?>
<div class="content">
<div class="container container-wide">

    <div class="page-head">
        <div>
            <h2><?php echo $t !== '' ? '#' . h($t) : 'Browse by tag'; ?></h2>
            <p><?php echo $t !== '' ? $total . ' photo' . ($total === 1 ? '' : 's') : 'Pick a tag to see what the community has shot.'; ?></p>
        </div>
        <a class="inline-link" href="index.php">&larr; BACK TO GALLERY</a>
    </div>

    <p class="chips-label">All tags</p>
    <?php echo ss_tag_chips($chips, $t); ?>

    <?php if ($t !== '' && $rows): ?>
        <div class="gallery-grid">
            <?php foreach ($rows as $row) echo ss_feed_card($row, $extras); ?>
        </div>
        <?php echo ss_feed_pager($page, $pages, function ($p) use ($t) {
            return 'tag.php?t=' . urlencode($t) . ($p > 1 ? '&page=' . $p : '');
        }); ?>
    <?php elseif ($t !== ''): ?>
        <div class="empty-state-box"><h3>Nothing tagged #<?php echo h($t); ?></h3></div>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
