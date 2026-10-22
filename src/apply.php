<?php
/* Photography class applications.
 * Members submit a camera + a sample shot; admins approve or reject. */
include 'db.php';
ss_require_login();

$me    = ss_uid();
$admin = ss_is_admin($conn);
$page_title = $admin ? 'Registrations' : 'Course registration';
$error = "";

/* submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['camera'])) {
    /* samples are filed per intake, e.g. ../uploads/apps/intake2/ */
    $intake = $_POST['intake'] ?? '1';
    if (!in_array($intake, ['1', '2', '3', '4'], true)) $intake = '1';
    $dir = "../uploads/apps/intake$intake/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $ext     = strtolower(pathinfo($_FILES['sample']['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        $error = "Only JPG, PNG or PDF samples are accepted.";
    } else {
        $name   = uniqid("app_") . "." . $ext;
        $stored = "intake$intake/$name";
        if (move_uploaded_file($_FILES['sample']['tmp_name'], $dir . $name)) {
            $stmt = $conn->prepare("INSERT INTO applications (user_id, camera_model, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $me, $_POST['camera'], $stored);
            $stmt->execute();
            ss_flash("Application submitted.");
            header("Location: apply.php");
            exit;
        }
        $error = "Upload failed.";
    }
}

/* admin decision */
foreach (['approve' => 'approved', 'reject' => 'rejected'] as $param => $status) {
    if (isset($_GET[$param])) {
        if (!$admin) { http_response_code(403); exit("Access denied."); }
        $app_id = (int) $_GET[$param];
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $app_id);
        $stmt->execute();
        ss_flash("Application $status.");
        header("Location: apply.php");
        exit;
    }
}

if ($admin) {
    $rows = $conn->query(
        "SELECT a.*, us.username FROM applications a JOIN users us ON a.user_id = us.id ORDER BY a.id DESC");
} else {
    $stmt = $conn->prepare(
        "SELECT a.*, us.username FROM applications a JOIN users us ON a.user_id = us.id
          WHERE a.user_id = ? ORDER BY a.id DESC");
    $stmt->bind_param("i", $me);
    $stmt->execute();
    $rows = $stmt->get_result();
}

$pending = [];
$past    = [];
while ($r = $rows->fetch_assoc()) {
    if ($r['status'] === 'pending') $pending[] = $r; else $past[] = $r;
}

include 'header.php';

function ss_app_card($r, $admin)
{
    $badge = ['pending' => 'badge-warn', 'approved' => 'badge-ok', 'rejected' => 'badge-bad'][$r['status']] ?? 'badge';
    ?>
    <div class="app-card">
        <div class="app-head">
            <div class="who">
                <strong><?php echo h(strtoupper($r['username'])); ?></strong>
                <span>Course: <?php echo h($r['camera_model']); ?></span>
            </div>
            <span class="badge <?php echo $badge; ?>"><?php echo h(strtoupper($r['status'])); ?></span>
        </div>

        <div class="app-body">
            <div class="detail-list" style="margin:0;">
                <div><span class="k">Reference</span><span class="v">#<?php echo (int) $r['id']; ?></span></div>
                <div><span class="k">Submitted</span><span class="v"><?php echo h($r['created_at']); ?></span></div>
                <div class="full">
                    <span class="k">Attachment</span>
                    <span class="v">
                        <a class="inline-link" href="<?php echo ss_app_src($r['id'], $r['file_path']); ?>" target="_blank"><?php echo h($r['file_path']); ?></a>
                    </span>
                </div>
            </div>

            <?php if ($r['status'] === 'rejected'): ?>
                <div class="app-note is-bad">
                    <strong>Admin note:</strong> your registration for <?php echo h($r['camera_model']); ?> was rejected.
                </div>
            <?php elseif ($r['status'] === 'approved'): ?>
                <div class="app-note is-ok">
                    <strong>Admin note:</strong> accepted. Email <strong>registrar@uam.edu.my</strong>
                    with reference #<?php echo (int) $r['id']; ?>.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($admin && $r['status'] === 'pending'): ?>
            <div class="app-actions">
                <span class="spacer">Reviewing <?php echo h($r['username']); ?>'s registration.</span>
                <a class="btn btn-sm" href="apply.php?approve=<?php echo (int) $r['id']; ?>">ACCEPT</a>
                <a class="btn btn-sm btn-danger" href="apply.php?reject=<?php echo (int) $r['id']; ?>">REJECT</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
<div class="content">
<div class="container container-mid">
    <h2><?php echo $admin ? 'Course registrations' : 'Course registration'; ?></h2>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <?php if (!$admin): ?>
        <form method="post" enctype="multipart/form-data" style="border-bottom:2px solid #000; padding-bottom:20px; margin-bottom:10px;">
            <div class="field">
                <label for="cam">Course code</label>
                <input type="text" id="cam" name="camera" required placeholder="e.g. CSC3203">
            </div>
            <div class="field">
                <label for="intake">Semester</label>
                <select id="intake" name="intake">
                    <option value="1">1 - Introduction to Networks</option>
                    <option value="2">2 - Database Systems Lab</option>
                    <option value="3">3 - Academic Writing Workshop</option>
                    <option value="4">4 - Industrial Training Briefing</option>
                </select>
            </div>

            <div class="field">
                <label for="s">Supporting document</label>
                <label class="dropzone" data-max-mb="8">
                    <span class="dz-icon" aria-hidden="true">&#8679;</span>
                    <span class="dz-name">Attach a document</span>
                    <span class="dz-sub">JPG, PNG or PDF &middot; max 8 MB</span>
                    <img class="dz-preview" alt="">
                    <input type="file" id="s" name="sample" required accept=".jpg,.jpeg,.png,.pdf">
                </label>
            </div>
            <button type="submit">Submit registration</button>
        </form>
    <?php endif; ?>

    <h3 class="section-title">Pending</h3>
    <?php if ($pending): foreach ($pending as $r) ss_app_card($r, $admin); else: ?>
        <div class="empty-state-box"><h3>Nothing pending</h3></div>
    <?php endif; ?>

    <?php if ($past): ?>
        <h3 class="section-title">Decided</h3>
        <?php foreach ($past as $r) ss_app_card($r, $admin); ?>
    <?php endif; ?>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
