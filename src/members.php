<?php
/* Student directory: a simple, parameterised name search over the
 * official-looking student record list - matric number, programme,
 * status. No social features - no following, no like counts. */
include 'db.php';
require_once 'feed_card.php'; /* only for ss_feed_pager() */
ss_require_login();

const SS_MEMBERS_PAGE = 15;

$me   = ss_uid();
$q    = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$like = '%' . $q . '%';

$stmt = $conn->prepare("SELECT COUNT(*) FROM users u WHERE u.username LIKE ? OR u.full_name LIKE ?");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_row()[0];

$pages = max(1, (int) ceil($total / SS_MEMBERS_PAGE));
if ($page > $pages) $page = $pages;
$off = ($page - 1) * SS_MEMBERS_PAGE;
$lim = SS_MEMBERS_PAGE;

$stmt = $conn->prepare(
    "SELECT u.id, u.username, u.avatar, u.full_name, u.bio, u.created_at, u.is_admin
       FROM users u
      WHERE u.username LIKE ? OR u.full_name LIKE ?
      ORDER BY u.id ASC
      LIMIT ?, ?");
$stmt->bind_param("ssii", $like, $like, $off, $lim);
$stmt->execute();
$res = $stmt->get_result();

$members = [];
while ($r = $res->fetch_assoc()) $members[] = $r;

function ss_members_url($p, $q)
{
    $qs = [];
    if ($q !== '') $qs['q'] = $q;
    if ($p > 1)    $qs['page'] = $p;
    return 'members.php' . ($qs ? '?' . http_build_query($qs) : '');
}

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

    <form method="get" class="member-tools">
        <div class="field">
            <label for="q">Search by name</label>
            <input type="text" id="q" name="q" value="<?php echo h($q); ?>" placeholder="e.g. Sarah">
        </div>
        <div class="member-tools-actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <a class="btn" href="members.php">Reset</a>
        </div>
    </form>

    <p class="result-line"><?php echo $total; ?> record<?php echo $total === 1 ? '' : 's'; ?></p>

    <?php if (!$members): ?>
        <div class="empty-state-box"><h3>No records matched</h3><a class="btn" href="members.php">Clear search</a></div>
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
            <?php foreach ($members as $m): ?>
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
        <?php echo ss_feed_pager($page, $pages, function ($p) use ($q) { return ss_members_url($p, $q); }); ?>
    <?php endif; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
