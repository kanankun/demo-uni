<?php
/* Student/staff dashboard. Landing page after sign-in. */
include 'db.php';
ss_require_login();

$me    = ss_uid();
$user  = ss_user($conn, $me);
$admin = ss_is_admin($conn);

$page_title = 'Dashboard';

$notices = [];
$res = $conn->query("SELECT u.id, u.title, u.created_at, us.username FROM uploads u JOIN users us ON u.user_id = us.id ORDER BY u.created_at DESC LIMIT 3");
while ($res && $r = $res->fetch_assoc()) $notices[] = $r;

include 'header.php';
?>
<div class="content">
<div class="container container-wide">

    <div class="dash-welcome">
        <h2>Welcome, <?php echo h($user['full_name'] ?: $user['username']); ?></h2>
        <p class="mono" style="color:#e6d6b0;">@<?php echo h($user['username']); ?> &bull; <?php echo h(ss_matric($me, $user['created_at'], $admin)); ?><?php if (!$admin && $user['bio']): ?> &bull; <?php echo h($user['bio']); ?><?php endif; ?></p>
        <p><?php echo $admin ? 'You are signed in with administrator access.' : 'Here is what is happening on your portal today.'; ?></p>
    </div>

    <div class="quick-grid">
        <a class="quick-card" href="announcements.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5L14 3.5zM7 12h10v1.6H7V12zm0 3.6h10v1.6H7v-1.6zM7 8.4h5V10H7V8.4z"/></svg>
            <strong>Announcements</strong>
            <span>Notices from the registrar</span>
        </a>
        <a class="quick-card" href="apply.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M4 4h16v2H4zm0 7h16v2H4zm0 7h10v2H4z"/></svg>
            <strong>Course registration</strong>
            <span>Apply for a course</span>
        </a>
        <a class="quick-card" href="schedule.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm12 8v10H5V10z"/></svg>
            <strong>Timetable</strong>
            <span>Your class sessions</span>
        </a>
        <a class="quick-card" href="results.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M3 3h18v2H3zm2 4h14v14H5zm2 2v10h10V9z"/></svg>
            <strong>Results</strong>
            <span>Semester transcript</span>
        </a>
        <a class="quick-card" href="fees.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm.75 15.5h-1.5v-1h1.5zm0-2.3h-1.5c0-2.6 2.5-2.4 2.5-4.2 0-1-.8-1.5-1.75-1.5-.8 0-1.4.4-1.7 1l-1.3-.9c.6-1.1 1.8-1.8 3-1.8 1.8 0 3.25 1.2 3.25 3 0 2.3-2.5 2.2-2.5 4.4z"/></svg>
            <strong>Fees</strong>
            <span>Billing &amp; statements</span>
        </a>
        <a class="quick-card" href="members.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5z"/></svg>
            <strong>Student directory</strong>
            <span>Find a classmate</span>
        </a>
        <a class="quick-card" href="settings.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5z"/></svg>
            <strong>My profile</strong>
            <span>Edit details &amp; photo</span>
        </a>
        <?php if ($admin): ?>
        <a class="quick-card" href="admin.php">
            <svg class="qi" viewBox="0 0 24 24"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/></svg>
            <strong>Admin panel</strong>
            <span>Accounts &amp; registrations</span>
        </a>
        <?php endif; ?>
    </div>

    <h3 class="section-title">Latest announcements</h3>
    <?php if (!$notices): ?>
        <div class="empty-state-box"><h3>No announcements yet</h3></div>
    <?php else: ?>
        <div class="container" style="box-shadow:none; padding:4px 16px;">
        <?php foreach ($notices as $n): ?>
            <a class="notice-row" href="view.php?id=<?php echo (int) $n['id']; ?>">
                <svg class="ni" viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5L14 3.5zM7 12h10v1.6H7V12zm0 3.6h10v1.6H7v-1.6zM7 8.4h5V10H7V8.4z"/></svg>
                <div class="nt">
                    <strong><?php echo h($n['title']); ?></strong>
                    <span><?php echo h($n['username']); ?> &bull; <?php echo h($n['created_at']); ?></span>
                </div>
                <svg class="nc" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        <?php endforeach; ?>
        </div>
        <p style="margin-top:12px;"><a class="inline-link" href="announcements.php">View all announcements</a></p>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
