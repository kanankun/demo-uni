<?php
/* This build prints created_at exactly as the query returned it, so a
 * UNION-selected value lands on the card.
 *
 * Gallery-card helpers shared by index.php, tag.php, saved.php and
 * profile.php. Ids are cast to int before they reach a query; everything
 * printed goes through h(). */

/* Owner, like count, comment count and tags for a list of upload ids. */
function ss_feed_extras($conn, array $ids)
{
    $x = ['owner' => [], 'avatar' => [], 'likes' => [], 'comments' => [], 'tags' => [], 'saved' => []];
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) return $x;
    $in = implode(',', $ids);

    $q = $conn->query("SELECT u.id, u.user_id, us.avatar FROM uploads u JOIN users us ON u.user_id = us.id WHERE u.id IN ($in)");
    while ($q && $r = $q->fetch_assoc()) {
        $x['owner'][(int) $r['id']]  = (int) $r['user_id'];
        $x['avatar'][(int) $r['id']] = $r['avatar'];
    }

    $q = $conn->query("SELECT upload_id, COUNT(*) c FROM likes WHERE upload_id IN ($in) GROUP BY upload_id");
    while ($q && $r = $q->fetch_assoc()) $x['likes'][(int) $r['upload_id']] = (int) $r['c'];

    $q = $conn->query("SELECT upload_id, COUNT(*) c FROM comments WHERE upload_id IN ($in) GROUP BY upload_id");
    while ($q && $r = $q->fetch_assoc()) $x['comments'][(int) $r['upload_id']] = (int) $r['c'];

    $q = $conn->query("SELECT upload_id, tag FROM upload_tags WHERE upload_id IN ($in) ORDER BY id");
    while ($q && $r = $q->fetch_assoc()) $x['tags'][(int) $r['upload_id']][] = $r['tag'];

    if ($me = ss_uid()) {
        $q = $conn->query("SELECT upload_id FROM bookmarks WHERE user_id = " . (int) $me . " AND upload_id IN ($in)");
        while ($q && $r = $q->fetch_assoc()) $x['saved'][(int) $r['upload_id']] = true;
    }
    return $x;
}

/* One card. $row needs id, title, filename, created_at, username. */
function ss_feed_card(array $row, array $extras)
{
    $pid      = (int) $row['id'];
    $likes    = $extras['likes'][$pid] ?? 0;
    $comments = $extras['comments'][$pid] ?? 0;
    $owner    = $extras['owner'][$pid] ?? 0;
    $avatar   = $extras['avatar'][$pid] ?? null;
    $tags     = array_slice($extras['tags'][$pid] ?? [], 0, 2);
    ob_start(); ?>
    <article class="gallery-item notice-card">
        <a class="thumb notice-icon" href="view.php?id=<?php echo $pid; ?>" aria-label="Open <?php echo h($row['title']); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5L14 3.5zM7 12h10v1.6H7V12zm0 3.6h10v1.6H7v-1.6zM7 8.4h5V10H7V8.4z"/></svg>
            <span class="notice-badge">NOTICE</span>
            <?php if ($tags): ?>
                <span class="thumb-tags">
                    <?php foreach ($tags as $t): ?><span>#<?php echo h($t); ?></span><?php endforeach; ?>
                </span>
            <?php endif; ?>
        </a>

        <a class="title-link" href="view.php?id=<?php echo $pid; ?>"><h3><?php echo h($row['title']); ?></h3></a>

        <p class="meta">
            <?php echo ss_avatar_img($owner, $avatar, 'avatar avatar-xs'); ?>
            Posted by <a href="<?php echo h(ss_profile_url($owner)); ?>"><?php echo h($row['username']); ?></a>
            &bull;
            <?php echo h($row['created_at']); ?>
        </p>
    </article>
    <?php
    return ob_get_clean();
}

/* Previous / next pager. $url($p) builds the href for page $p. */
function ss_feed_pager($page, $pages, callable $url)
{
    if ($pages <= 1) return '';
    $prev = $page > 1;
    $next = $page < $pages;
    return '<nav class="pagination" aria-label="Pagination">'
        . '<a class="btn" href="' . h($url(max(1, $page - 1))) . '" ' . ($prev ? 'rel="prev"' : 'aria-disabled="true" tabindex="-1"') . '>&larr; Previous</a>'
        . '<span class="page-info">PAGE ' . (int) $page . ' / ' . (int) $pages . '</span>'
        . '<a class="btn" href="' . h($url(min($pages, $page + 1))) . '" ' . ($next ? 'rel="next"' : 'aria-disabled="true" tabindex="-1"') . '>Next &rarr;</a>'
        . '</nav>';
}

/* The most used tags, as [[tag, count], ...]. */
function ss_popular_tags($conn, $limit = 10)
{
    $limit = max(1, (int) $limit);
    $out = [];
    $q = @$conn->query("SELECT tag, COUNT(*) n FROM upload_tags GROUP BY tag ORDER BY n DESC, tag ASC LIMIT $limit");
    while ($q && $r = $q->fetch_assoc()) $out[] = [$r['tag'], (int) $r['n']];
    return $out;
}

function ss_tag_chips(array $tags, $active = '')
{
    if (!$tags) return '';
    $html = '<nav class="tag-chips" aria-label="Popular tags">';
    foreach ($tags as $t) {
        $cls = 'tag-chip' . ($t[0] === $active ? ' active' : '');
        $html .= '<a class="' . $cls . '" href="tag.php?t=' . h(urlencode($t[0])) . '">#' . h($t[0])
               . ' <span>' . (int) $t[1] . '</span></a>';
    }
    return $html . '</nav>';
}
