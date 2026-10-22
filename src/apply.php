<?php
/* Course registration.
 *
 * Each course here is one of the real class_sessions rows the Timetable
 * already shows - this used to be a free-text "course code" field with
 * no relationship to that catalogue at all, so what a student typed
 * never matched what Timetable actually ran. Now a registration always
 * points at a real course_title: it's a dropdown-free click on the
 * course itself, one registration per student per course, shown
 * grouped with that course for both students and staff - same shape as
 * assignments.php's one-submission-per-assignment pattern. */
include 'db.php';
ss_require_login();

$me    = ss_uid();
$admin = ss_is_admin($conn);
$page_title = $admin ? 'Registrations' : 'Course registration';
$error = "";

/* submit, or replace while still pending */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_title']) && !$admin) {
    $course = trim($_POST['course_title'] ?? '');

    $stmt = $conn->prepare("SELECT seats, taken FROM class_sessions WHERE title = ?");
    $stmt->bind_param("s", $course);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $full    = $session && (int) $session['taken'] >= (int) $session['seats'];

    $stmt = $conn->prepare("SELECT status FROM applications WHERE user_id = ? AND course_title = ?");
    $stmt->bind_param("is", $me, $course);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $ext     = strtolower(pathinfo($_FILES['sample']['name'] ?? '', PATHINFO_EXTENSION));

    if (!$session) {
        $error = "Pick a course from the list.";
    } elseif ($existing && $existing['status'] !== 'pending') {
        $error = "That registration has already been decided - contact the registrar to change it.";
    } elseif (!$existing && $full) {
        /* a replacement upload on an already-held seat is fine even if the
           course has since filled up; only a brand new registration needs
           a free seat to hold */
        $error = "This course is full.";
    } elseif (!in_array($ext, $allowed, true)) {
        $error = "Only JPG, PNG or PDF supporting documents are accepted.";
    } else {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($course));
        $dir  = "../uploads/apps/$slug/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name   = uniqid("app_") . "." . $ext;
        $stored = "$slug/$name";
        if (move_uploaded_file($_FILES['sample']['tmp_name'], $dir . $name)) {
            $stmt = $conn->prepare(
                "INSERT INTO applications (user_id, course_title, file_path) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), created_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("iss", $me, $course, $stored);
            $stmt->execute();

            /* a brand new registration holds a seat immediately, pending or
               not - that's what stops the next student from registering
               into a course that's already full. Rejection frees it again. */
            if (!$existing) {
                $stmt = $conn->prepare("UPDATE class_sessions SET taken = taken + 1 WHERE title = ? AND taken < seats");
                $stmt->bind_param("s", $course);
                $stmt->execute();
            }

            ss_flash("Registration submitted.");
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

        $stmt = $conn->prepare("SELECT course_title, status FROM applications WHERE id = ?");
        $stmt->bind_param("i", $app_id);
        $stmt->execute();
        $app_row = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $app_id);
        $stmt->execute();

        /* approving keeps the seat this registration already holds;
           rejecting gives it back to the course */
        if ($app_row && $status === 'rejected' && $app_row['status'] === 'pending') {
            $stmt = $conn->prepare("UPDATE class_sessions SET taken = taken - 1 WHERE title = ? AND taken > 0");
            $stmt->bind_param("s", $app_row['course_title']);
            $stmt->execute();
        }

        ss_flash("Registration $status.");
        header("Location: apply.php");
        exit;
    }
}

$courses = [];
$res = $conn->query("SELECT * FROM class_sessions ORDER BY starts_at ASC");
while ($res && $r = $res->fetch_assoc()) $courses[] = $r;

if ($admin) {
    $apps_by_course = [];
    $res = $conn->query("SELECT a.*, us.username FROM applications a JOIN users us ON a.user_id = us.id ORDER BY a.id DESC");
    while ($res && $r = $res->fetch_assoc()) $apps_by_course[$r['course_title']][] = $r;
} else {
    $my_apps = [];
    $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->bind_param("i", $me);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $my_apps[$r['course_title']] = $r;
}

include 'header.php';
?>
<div class="content">
<div class="container container-mid">

    <div class="page-head">
        <div>
            <h2><?php echo $admin ? 'Course registrations' : 'Course registration'; ?></h2>
            <p><?php echo $admin ? 'Who has registered for each course this semester.' : 'Pick a course from the timetable and attach your supporting document.'; ?></p>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <?php if (!$courses): ?>
        <div class="empty-state-box"><h3>No courses open for registration</h3></div>
    <?php else: ?>
        <?php foreach ($courses as $c):
            $full = (int) $c['taken'] >= (int) $c['seats'];
            $mine = $admin ? null : ($my_apps[$c['title']] ?? null);
        ?>
            <div class="app-card">
                <div class="app-head">
                    <div class="who">
                        <strong><?php echo h($c['title']); ?></strong>
                        <span><?php echo h($c['location']); ?> &bull; <?php echo date('D, j M', strtotime($c['starts_at'])); ?></span>
                    </div>
                    <span class="badge <?php echo $full ? 'badge-bad' : 'badge-ok'; ?>">
                        <?php echo (int) $c['taken']; ?>/<?php echo (int) $c['seats']; ?> SEATS
                    </span>
                </div>

                <div class="app-body">
                    <?php if ($admin): ?>
                        <?php $apps = $apps_by_course[$c['title']] ?? []; ?>
                        <div class="detail-list" style="margin:0;">
                            <div class="full"><span class="k">Registrations</span><span class="v"><?php echo count($apps); ?> student(s)</span></div>
                        </div>
                        <?php if (!$apps): ?>
                            <p class="sub" style="margin:0;">No registrations yet.</p>
                        <?php else: foreach ($apps as $r): ?>
                            <div class="app-note<?php echo $r['status'] === 'approved' ? ' is-ok' : ($r['status'] === 'rejected' ? ' is-bad' : ''); ?>"
                                 style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
                                <span>
                                    <strong><?php echo h($r['username']); ?></strong> &bull;
                                    <a class="inline-link" href="<?php echo ss_app_src($r['id']); ?>" target="_blank">attachment</a>
                                    &bull; <?php echo h(strtoupper($r['status'])); ?>
                                </span>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <span>
                                        <a class="btn btn-sm" href="apply.php?approve=<?php echo (int) $r['id']; ?>">ACCEPT</a>
                                        <a class="btn btn-sm btn-danger" href="apply.php?reject=<?php echo (int) $r['id']; ?>">REJECT</a>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; endif; ?>

                    <?php elseif ($mine): ?>
                        <div class="app-note<?php echo $mine['status'] === 'approved' ? ' is-ok' : ($mine['status'] === 'rejected' ? ' is-bad' : ''); ?>">
                            <strong><?php echo h(strtoupper($mine['status'])); ?></strong> -
                            <?php if ($mine['status'] === 'approved'): ?>
                                email <strong>registrar@universitya.edu.my</strong> with reference #<?php echo (int) $mine['id']; ?>.
                            <?php elseif ($mine['status'] === 'rejected'): ?>
                                this registration was rejected.
                            <?php else: ?>
                                submitted <?php echo h($mine['created_at']); ?>, awaiting a decision.
                            <?php endif; ?>
                            <a class="inline-link" href="<?php echo ss_app_src($mine['id']); ?>" target="_blank">View your document</a>
                        </div>
                        <?php if ($mine['status'] === 'pending'): ?>
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="course_title" value="<?php echo h($c['title']); ?>">
                                <div class="field">
                                    <label class="dropzone" data-max-mb="8">
                                        <span class="dz-icon" aria-hidden="true">&#8679;</span>
                                        <span class="dz-name">Replace supporting document</span>
                                        <span class="dz-sub">JPG, PNG or PDF &middot; max 8 MB</span>
                                        <img class="dz-preview" alt="">
                                        <input type="file" name="sample" required accept=".jpg,.jpeg,.png,.pdf">
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-sm" data-loading-text="Uploading...">Replace document</button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($full): ?>
                        <div class="app-note is-bad">This course is full.</div>

                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="course_title" value="<?php echo h($c['title']); ?>">
                            <div class="field">
                                <label class="dropzone" data-max-mb="8">
                                    <span class="dz-icon" aria-hidden="true">&#8679;</span>
                                    <span class="dz-name">Attach a supporting document</span>
                                    <span class="dz-sub">JPG, PNG or PDF &middot; max 8 MB</span>
                                    <img class="dz-preview" alt="">
                                    <input type="file" name="sample" required accept=".jpg,.jpeg,.png,.pdf">
                                </label>
                            </div>
                            <button type="submit" class="btn btn-sm" data-loading-text="Registering...">Register</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
