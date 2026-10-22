<?php
/* Photo page. Same shape as the main site: the gallery blurred out behind a
 * dark backdrop, with the photo and its sidebar on top. This page writes its
 * own <head> rather than including header.php, because of that background. */
include 'db.php';
require_once 'feed_card.php';

$id    = (int) ($_GET['id'] ?? 0);
$me    = ss_uid();
$admin = ss_is_admin($conn);

$stmt = $conn->prepare(
    "SELECT u.*, us.username, us.avatar, us.bio
       FROM uploads u JOIN users us ON u.user_id = us.id
      WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { http_response_code(404); exit("Photo not found."); }

$owner_id = (int) $post['user_id'];
$is_owner = ($me && $me === $owner_id);

/* new comment */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && $me) {
    $text = trim($_POST['comment']);
    if ($text !== '') {
        $stmt = $conn->prepare("INSERT INTO comments (upload_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id, $me, $text);
        $stmt->execute();
    }
    header("Location: view.php?id=$id");
    exit;
}

/* like / unlike */
if (isset($_GET['like']) && $me) {
    $stmt = $conn->prepare("SELECT id FROM likes WHERE upload_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $me);
    $stmt->execute();
    $stmt = $stmt->get_result()->fetch_row()
        ? $conn->prepare("DELETE FROM likes WHERE upload_id = ? AND user_id = ?")
        : $conn->prepare("INSERT INTO likes (upload_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $id, $me);
    $stmt->execute();
    header("Location: view.php?id=$id");
    exit;
}

/* delete a post or a comment */
if (isset($_GET['delete'])) {
    $target = (int) $_GET['delete'];
    $what   = $_GET['type'] ?? '';

    if ($what === 'post') {
        if (!$is_owner && !$admin) { http_response_code(403); exit("Access denied."); }
        $stmt = $conn->prepare("DELETE FROM uploads WHERE id = ?");
        $stmt->bind_param("i", $target);
        $stmt->execute();
        header("Location: index.php?msg=deleted");
        exit;
    }
    if ($what === 'comment') {
        /* your own comments, or anybody's if you moderate */
        $stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->bind_param("i", $target);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || (!$admin && (int) $row['user_id'] !== $me)) {
            http_response_code(403);
            exit("Access denied.");
        }
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $target);
        $stmt->execute();
        header("Location: view.php?id=$id");
        exit;
    }
}

/* comments */
$stmt = $conn->prepare(
    "SELECT c.*, us.username, us.avatar FROM comments c JOIN users us ON c.user_id = us.id
      WHERE c.upload_id = ? ORDER BY c.created_at ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$cres = $stmt->get_result();
$comments = [];
while ($r = $cres->fetch_assoc()) $comments[] = $r;

/* likes, saves, tags */
$stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE upload_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$likes = (int) $stmt->get_result()->fetch_row()[0];

$liked = $saved = false;
if ($me) {
    $stmt = $conn->prepare("SELECT 1 FROM likes WHERE upload_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $me);
    $stmt->execute();
    $liked = (bool) $stmt->get_result()->fetch_row();

    $stmt = $conn->prepare("SELECT 1 FROM bookmarks WHERE upload_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $me);
    $stmt->execute();
    $saved = (bool) $stmt->get_result()->fetch_row();
}

$tags = [];
$stmt = $conn->prepare("SELECT tag FROM upload_tags WHERE upload_id = ? ORDER BY id");
$stmt->bind_param("i", $id);
$stmt->execute();
$tres = $stmt->get_result();
while ($r = $tres->fetch_row()) $tags[] = $r[0];

/* newer / older */
$stmt = $conn->prepare("SELECT id FROM uploads WHERE created_at > ? ORDER BY created_at ASC LIMIT 1");
$stmt->bind_param("s", $post['created_at']);
$stmt->execute();
$newer = $stmt->get_result()->fetch_row()[0] ?? null;

$stmt = $conn->prepare("SELECT id FROM uploads WHERE created_at < ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("s", $post['created_at']);
$stmt->execute();
$older = $stmt->get_result()->fetch_row()[0] ?? null;

/* the blurred gallery behind the modal */
$bg = $conn->query("SELECT id, filename FROM uploads ORDER BY created_at DESC LIMIT 6");

$details = ss_photo_details($post);

$src = ss_photo_src($post['id'], $post['filename']);

/* Where the close button goes: the page you arrived from, which keeps the
 * listing, page number and filter you had. Local paths only. */
$back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
$back = ltrim(preg_replace('#^https?://[^/]+#i', '', $back), '/');

/* a local page only, and never back onto a photo */
if ($back === '' || str_contains($back, '//') || str_contains($back, ':')
    || str_starts_with($back, 'view.php')) {
    $back = 'index.php';
}
$ss_v = function ($f) { return @filemtime(__DIR__ . '/' . $f) ?: 1; };
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000000">
<?php if ($me): ?>
    <meta name="csrf-token" content="<?php echo h(ss_csrf()); ?>">
<?php endif; ?>
    <title><?php echo h($post['title']); ?> | University A</title>
    <link rel="icon" type="image/svg+xml" href="images/icon.svg">
    <link rel="stylesheet" href="style.css?v=<?php echo $ss_v('style.css'); ?>">
    <script src="app.js?v=<?php echo $ss_v('app.js'); ?>"></script>
    <style>
        body { background: #fff; overflow: hidden; }
        /* the lab strip is not part of the blurred page behind the photo */
        body > .lab-banner { position: relative; z-index: 6; }
        .background-layer { top: 0; }
        .background-layer { position: fixed; inset: 0; z-index: 1; filter: blur(8px); overflow: hidden; }
        .bg-content { padding: 50px 16px; display: flex; justify-content: center; }
        .overlay-layer { position: fixed; inset: 0; z-index: 2; background: rgba(0, 0, 0, .5); }
        .modal-wrapper {
            position: fixed; inset: 0; z-index: 3; display: flex; justify-content: center; align-items: center;
            padding: 56px 0 24px; overscroll-behavior: contain;
        }
        .modal-container {
            width: 90%; max-width: 1100px; height: 100%; max-height: 85vh;
            background: #fff; display: flex; border: 3px solid #000; box-shadow: 15px 15px 0 #000;
            position: relative; animation: ss-pop .25s ease both;
        }
        .modal-image {
            flex: 1.5; min-width: 0; background: #000; position: relative;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; border-right: 3px solid #000;
        }
        /* thumbnails are monochrome until you hover them; opening a photo
           shows it in colour, same as that hover state */
        .modal-image img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .modal-sidebar { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #fff; }

        .modal-header {
            padding: 16px 20px; border-bottom: 2px solid #000;
            display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
        }
        .author { display: flex; gap: 12px; min-width: 0; }
        .author .avatar { width: 44px; height: 44px; }
        .author-meta { min-width: 0; }
        .author-name { font-weight: 900; font-size: 1.05rem; color: #000; text-decoration: none; overflow-wrap: anywhere; }
        .author-name:hover { text-decoration: underline; }
        .post-caption { display: block; margin-top: 4px; font-size: .9rem; color: #333; overflow-wrap: anywhere; }
        .post-time { display: block; margin-top: 4px; font-size: .72rem; color: #666; }

        .modal-comments { flex: 1; overflow-y: auto; padding: 16px 20px; min-height: 0; }
        .modal-actions { padding: 16px 20px; border-top: 2px solid #000; background: #fff; }

        .comment-bubble { display: flex; gap: 10px; margin-bottom: 16px; font-size: .9rem; }
        .comment-bubble .avatar { width: 28px; height: 28px; border-width: 1px; margin-top: 2px; }
        .comment-main { min-width: 0; flex: 1; }
        .comment-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .comment-head a { color: #000; font-weight: 900; text-decoration: none; }
        .comment-head a:hover { text-decoration: underline; }
        .comment-head time { font-size: .7rem; color: #777; }
        .comment-text { overflow-wrap: anywhere; margin-top: 2px; }
        .cmt-del {
            margin-left: auto; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid #000; color: #000; text-decoration: none; font-size: .7rem; font-weight: 900; line-height: 1;
        }
        .cmt-del:hover, .cmt-del:focus-visible { background: #000; border-color: #000; color: #fff; }
        .comments-empty { color: #666; text-align: center; margin: 40px 0; font-size: .9rem; }

        .close-button {
            position: absolute; top: -20px; right: -20px; z-index: 7;
            width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;
            background: #fff; color: #000; border: 3px solid #000; box-shadow: 4px 4px 0 #000;
            font-size: 1.2rem; font-weight: 900; text-decoration: none;
        }
        .close-button:hover { background: #000; color: #fff; box-shadow: none; }

        .menu-container { position: relative; flex-shrink: 0; }
        .menu-btn {
            width: 40px; height: 40px; padding: 0; border: 2px solid transparent; background: none;
            font-size: 1.6rem; font-weight: bold; line-height: 1; letter-spacing: 0; color: #000;
        }
        .menu-btn:hover, .menu-btn[aria-expanded=true] { background: #000; color: #fff; box-shadow: none; transform: none; }
        .menu-content {
            display: none; position: absolute; right: 0; top: 44px; background: #fff; min-width: 170px;
            border: 2px solid #000; box-shadow: 5px 5px 0 #000; z-index: 10;
        }
        .menu-active .menu-content { display: block; }
        .menu-content a {
            display: block; width: 100%; text-align: left; background: #fff; color: #000;
            border-bottom: 1px solid #ddd; padding: 12px 16px; text-decoration: none;
            font-weight: bold; font-size: .8rem; text-transform: uppercase; letter-spacing: 1px;
        }
        .menu-content > :last-child { border-bottom: none; }
        .menu-content a:hover, .menu-content a:focus-visible { background: #000; color: #fff; }
        .menu-content .menu-danger { font-style: italic; }
        .menu-content a.menu-danger:hover { background: #000; color: #fff; }

        .like-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
        .like-btn {
            width: 40px; height: 40px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.7rem; line-height: 1; color: #000; text-decoration: none;
            border: 0; background: none; border-radius: 50%;
            transition: transform .15s var(--ease), color .15s;
        }
        .like-btn:hover { transform: scale(1.15); }
        .like-btn:focus-visible { outline: 2px solid #000; outline-offset: 2px; }
        .like-btn.is-liked { color: #000; }
        .like-stats { font-weight: 900; font-size: .9rem; }
        .like-stats .sep { color: #999; margin: 0 6px; font-weight: 400; }

        .comment-form { display: flex; gap: 10px; align-items: flex-start; }
        .comment-form .cf-input { flex: 1; min-width: 0; }
        .comment-form input[type=text] { margin: 0; padding: 10px; border-width: 0 0 2px; border-bottom: 2px solid #ddd; }
        .comment-form input[type=text]:focus { border-bottom-color: #000; transform: none; box-shadow: none; }
        .comment-form .counter { margin: 2px 0 0; }
        .comment-form button { padding: 0 16px; height: 42px; font-size: .8rem; width: auto; }
        .guest-note { margin: 0; font-size: .9rem; }
        .guest-note a { color: #000; font-weight: 900; }

        @media (max-width: 800px) {
            body { overflow: auto; }
            .modal-wrapper { align-items: flex-start; overflow-y: auto; padding: 60px 10px 24px; }
            .modal-container { flex-direction: column; width: 100%; height: auto; max-height: none; box-shadow: 6px 6px 0 #000; }
            .modal-image { flex: none; border-right: 0; border-bottom: 3px solid #000; max-height: 45vh; min-height: 180px; }
            .close-button { top: 8px; right: 8px; }
            .modal-comments { max-height: 40vh; }
        }
    </style>
</head>
<body>
<?php include 'guide_boot.php'; ?>
    <a class="skip-link" href="#main">Skip to photo</a>

    <?php /* the lab strip stays sharp and on top; the site behind it blurs */
    include 'lab_banner.php';
    $ss_banner_done = true; ?>

    <div class="background-layer" aria-hidden="true" inert>
        <?php include 'nav.php'; ?>
        <div class="bg-content">
            <div class="container container-wide">
                <div class="gallery-grid">
                    <?php while ($b = $bg->fetch_assoc()): ?>
                        <div class="gallery-item"><span class="thumb"><img src="<?php echo h(ss_photo_src($b['id'], $b['filename'])); ?>" alt=""></span></div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <div class="wave-footer" style="position:absolute; bottom:0;"></div>
    </div>

    <div class="overlay-layer"></div>

    <div class="modal-wrapper" id="backdrop">
        <div class="modal-container" id="main" role="dialog" aria-label="Photo: <?php echo h($post['title']); ?>">
            <a href="<?php echo h($back); ?>" class="close-button" aria-label="Close photo" title="Close (Esc)">&#10005;</a>

            <div class="modal-image">
                <img src="<?php echo h($src); ?>" alt="<?php echo h($post['title']); ?>">
                <?php if ($newer): ?>
                    <a class="photo-nav photo-nav-prev" href="view.php?id=<?php echo (int) $newer ; ?>" aria-label="Newer photo" title="Newer photo">&#8592;</a>
                <?php endif; ?>
                <?php if ($older): ?>
                    <a class="photo-nav photo-nav-next" href="view.php?id=<?php echo (int) $older ; ?>" aria-label="Older photo" title="Older photo">&#8594;</a>
                <?php endif; ?>
            </div>

            <div class="modal-sidebar">
                <div class="modal-header">
                    <div class="author">
                        <?php echo ss_avatar_img($owner_id, $post['avatar']); ?>
                        <div class="author-meta">
                            <a class="author-name" href="<?php echo h(ss_profile_url($owner_id)); ?>"><?php echo h($post['username']); ?></a>
                            <span class="post-caption"><?php echo h($post['title']); ?></span>
                            <time class="post-time" datetime="<?php echo h($post['created_at']); ?>" data-rel><?php echo h(date('M j, Y', strtotime($post['created_at']))); ?></time>
                        </div>
                    </div>

                    <div class="menu-container" id="dropdownMenu">
                        <button type="button" class="menu-btn" id="menuBtn" aria-haspopup="menu" aria-expanded="false" aria-controls="menuList" aria-label="Post options">&#8942;</button>
                        <div class="menu-content" id="menuList" role="menu" aria-labelledby="menuBtn">
                            <a role="menuitem" href="<?php echo h($src); ?>" download target="_blank">Download</a>
                            <a role="menuitem" href="<?php echo h(ss_profile_url($owner_id)); ?>">View profile</a>
                            <?php if ($is_owner || $admin): ?>
                                <a role="menuitem" href="edit.php?id=<?php echo $id; ?>">Edit post</a>
                                <a role="menuitem" class="menu-danger" href="view.php?id=<?php echo $id; ?>&delete=<?php echo $id; ?>&type=post"
                                   data-confirm="This will permanently delete the photo and its comments. This cannot be undone."
                                   data-confirm-title="Delete this post?" data-confirm-ok="Delete" data-danger>Delete</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-comments" id="commentList" tabindex="0" aria-label="Comments">
                    <?php if (!$comments): ?>
                        <p class="comments-empty">No comments yet. Be the first to say something.</p>
                    <?php endif; ?>

                    <?php foreach ($comments as $c): ?>
                        <div class="comment-bubble">
                            <?php echo ss_avatar_img((int) $c['user_id'], $c['avatar']); ?>
                            <div class="comment-main">
                                <div class="comment-head">
                                    <a href="<?php echo h(ss_profile_url($c['user_id'])); ?>"><?php echo h($c['username']); ?></a>
                                    <time datetime="<?php echo h($c['created_at']); ?>" data-rel><?php echo h(date('M j, g:i a', strtotime($c['created_at']))); ?></time>
                                    <?php if ($admin || (int) $c['user_id'] === $me): ?>
                                        <a class="cmt-del" href="view.php?id=<?php echo $id; ?>&delete=<?php echo (int) $c['id']; ?>&type=comment"
                                           aria-label="Delete comment" title="Delete comment"
                                           data-confirm="This comment will be removed for everyone."
                                           data-confirm-title="Delete comment?" data-confirm-ok="Delete" data-danger>&#10005;</a>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-text"><?php echo h($c['comment']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="modal-actions">
                    <?php if ($tags): ?>
                        <nav class="tag-chips" aria-label="Tags">
                            <?php foreach ($tags as $t): ?>
                                <a class="tag-chip" href="tag.php?t=<?php echo h(urlencode($t)); ?>">#<?php echo h($t); ?></a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>

                    <details class="photo-details">
                        <summary>Photo details</summary>
                        <dl>
                            <?php foreach ($details as $k => $v): ?>
                                <dt><?php echo h($k); ?></dt><dd><?php echo h($v); ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    </details>

                    <div class="action-row">
                        <?php if ($me): ?>
                            <button type="button" class="btn btn-sm save-btn<?php echo $saved ? ' is-saved' : ''; ?>"
                                    data-save="<?php echo $id; ?>" data-saved="<?php echo $saved ? '1' : '0'; ?>"
                                    aria-pressed="<?php echo $saved ? 'true' : 'false'; ?>"><?php echo $saved ? '&#9733; Saved' : '&#9734; Save'; ?></button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm" data-copy="view.php?id=<?php echo $id; ?>">Share</button>
                    </div>

                    <div class="like-row">
                        <?php if ($me): ?>
                            <a class="like-btn<?php echo $liked ? ' is-liked' : ''; ?>" href="view.php?id=<?php echo $id; ?>&like=1"
                               aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
                               aria-label="<?php echo $liked ? 'Unlike this photo' : 'Like this photo'; ?>"><span aria-hidden="true"><?php echo $liked ? '&hearts;' : '&#9825;'; ?></span></a>
                        <?php else: ?>
                            <a class="like-btn" href="index.php" aria-label="Log in to like this photo"><span aria-hidden="true">&#9825;</span></a>
                        <?php endif; ?>
                        <span class="like-stats">
                            <?php echo $likes; ?> <?php echo $likes === 1 ? 'like' : 'likes'; ?><span class="sep">&middot;</span><?php echo count($comments); ?> <?php echo count($comments) === 1 ? 'comment' : 'comments'; ?>
                        </span>
                    </div>

                    <?php if ($me): ?>
                        <form method="post" action="" class="comment-form">
                            <div class="cf-input">
                                <label for="commentInput" class="sr-only">Add a comment</label>
                                <input type="text" id="commentInput" name="comment" placeholder="Add a comment..."
                                       required maxlength="500" autocomplete="off" enterkeyhint="send" data-counter>
                            </div>
                            <button type="submit" class="btn btn-primary" data-loading-text="Posting...">Post</button>
                        </form>
                    <?php else: ?>
                        <p class="guest-note">Log in to like and comment. <a href="index.php">Log in</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php
if (!empty($_SESSION['ss_flash'])) {
    echo '<script type="application/json" id="ss-flash">' . json_encode($_SESSION['ss_flash'], JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
    unset($_SESSION['ss_flash']);
}
?>
<script>
(function () {
    var BACK = <?php echo json_encode($back); ?>;

    /* three-dot menu */
    var menu = document.getElementById('dropdownMenu');
    var btn  = document.getElementById('menuBtn');
    function setMenu(open) {
        menu.classList.toggle('menu-active', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); setMenu(!menu.classList.contains('menu-active')); });
    document.addEventListener('click', function () { setMenu(false); });

    /* esc closes the photo, arrows move between photos */
    document.addEventListener('keydown', function (e) {
        if (e.target.matches('input, textarea')) return;
        if (e.key === 'Escape') {
            if (menu.classList.contains('menu-active')) { setMenu(false); return; }
            window.location.href = BACK;
        }
        var nav = e.key === 'ArrowLeft' ? document.querySelector('.photo-nav-prev')
                : e.key === 'ArrowRight' ? document.querySelector('.photo-nav-next') : null;
        if (nav) window.location.href = nav.href;
    });

    /* clicking the dark area closes too */
    document.getElementById('backdrop').addEventListener('mousedown', function (e) {
        if (e.target === this) window.location.href = BACK;
    });

    SS.flash('msg', { edited: 'Post updated.', uploaded: 'Photo shared. Nice shot!' });
})();
</script>
</body>
</html>
