<?php
/* A student/staff profile. ?id=N shows that account's profile; your own
 * adds the private verification block. No social features here. */
include 'db.php';
ss_require_login();

$me       = ss_uid();
$owner_id = isset($_GET['id']) ? (int) $_GET['id'] : $me;
$is_me    = ($owner_id === $me);
$viewer_is_staff = ss_is_admin($conn);

$user = ss_user($conn, $owner_id);
if (!$user) { http_response_code(404); exit("Account not found."); }

$page_title = $user['username'];

include 'header.php';
?>
<div class="content">
<div class="container container-mid">

    <div class="profile-header">
        <img class="profile-avatar" src="<?php echo h(ss_avatar_src($owner_id, $user['avatar'])); ?>"
             alt="" data-fallback="images/avatar-default.png">
        <div class="who">
            <h3><?php echo h($user['full_name'] ?: $user['username']); ?><?php if ($user['is_admin']): ?> <span class="badge badge-dark"><?php echo $user['is_lecturer'] ? 'LECTURER' : 'ADMIN'; ?></span><?php endif; ?></h3>
            <p class="member-since mono">@<?php echo h($user['username']); ?> &bull; <?php echo h(ss_matric($owner_id, $user['created_at'], $user['is_admin'])); ?></p>
            <?php if ($user['bio']): ?><p><?php echo h($user['bio']); ?></p><?php endif; ?>
            <p class="member-since">Account created <?php echo h(date('M Y', strtotime($user['created_at']))); ?></p>

            <?php if ($is_me): ?>
                <div class="btn-row profile-actions">
                    <a href="settings.php" class="btn btn-sm btn-primary">Edit profile</a>
                </div>
            <?php elseif ($viewer_is_staff && !$user['is_admin']): ?>
                <div class="btn-row profile-actions">
                    <a href="results.php?id=<?php echo (int) $owner_id; ?>" class="btn btn-sm">View results</a>
                    <a href="fees.php?id=<?php echo (int) $owner_id; ?>" class="btn btn-sm">View fees</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_me): ?>
        <details class="reveal">
            <summary>
                <span>Verification details (private)</span>
                <span class="reveal-tag"></span>
            </summary>
            <div class="detail-list">
                <div><span class="k">Full name</span><span class="v"><?php echo h($user['full_name'] ?: '-'); ?></span></div>
                <div><span class="k">Phone</span><span class="v"><?php echo h($user['phone'] ?: '-'); ?></span></div>
                <div><span class="k">National IC / ID</span><span class="v"><?php echo h($user['ic_number'] ?: '-'); ?></span></div>
                <div><span class="k">Email</span><span class="v"><?php echo h($user['email']); ?></span></div>
                <div class="full"><span class="k">Home address</span><span class="v"><?php echo h($user['address'] ?: '-'); ?></span></div>
            </div>
        </details>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
