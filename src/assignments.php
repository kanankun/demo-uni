<?php
/* Coursework submission. Replaces upload.php's file-handling logic with
 * something a student portal actually needs an upload form for: a
 * lecturer posts an assignment and a due date, a student uploads one
 * file for it. Staff see who has and hasn't submitted; students see
 * their own submission only. */
include 'db.php';
ss_require_login();

$me    = ss_uid();
$admin = ss_is_admin($conn);
$lecturer_scoped = $admin && ss_is_lecturer($conn);
$page_title = 'Assignments';
$error = "";

/* submit (or replace) a submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id']) && !$admin) {
    $aid = (int) $_POST['assignment_id'];

    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];
    $ext     = strtolower(pathinfo($_FILES['file']['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        $error = "Only PDF, DOC/DOCX, JPG, PNG or ZIP files are accepted.";
    } else {
        $dir = "../uploads/assignments/$aid/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name   = uniqid("sub_") . "." . $ext;
        $stored = "$aid/$name";
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . $name)) {
            $stmt = $conn->prepare(
                "INSERT INTO assignment_submissions (assignment_id, user_id, file_path) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submitted_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("iis", $aid, $me, $stored);
            $stmt->execute();
            ss_flash("Submission uploaded.");
            header("Location: assignments.php");
            exit;
        }
        $error = "Upload failed.";
    }
}

$assignments = [];
if ($lecturer_scoped) {
    $stmt = $conn->prepare("SELECT a.*, us.username AS poster FROM assignments a JOIN users us ON a.created_by = us.id WHERE a.created_by = ? ORDER BY a.due_at ASC");
    $stmt->bind_param("i", $me);
    $stmt->execute();
    $res = $stmt->get_result();
} elseif ($admin) {
    $res = $conn->query("SELECT a.*, us.username AS poster FROM assignments a JOIN users us ON a.created_by = us.id ORDER BY a.due_at ASC");
} else {
    /* students need every assignment on offer, not just one lecturer's */
    $res = $conn->query("SELECT a.*, us.username AS poster FROM assignments a JOIN users us ON a.created_by = us.id ORDER BY a.due_at ASC");
}
while ($res && $r = $res->fetch_assoc()) $assignments[] = $r;

if ($admin) {
    /* every submission to one of the assignments above, grouped by assignment -
       already scoped to just this lecturer's own when $lecturer_scoped, because
       $assignments itself was */
    $subs_by_assignment = [];
    $ids = array_column($assignments, 'id');
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT s.id, s.assignment_id, s.submitted_at, u.username FROM assignment_submissions s
                                   JOIN users u ON s.user_id = u.id WHERE s.assignment_id IN ($in) ORDER BY s.submitted_at DESC");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $subs_by_assignment[$r['assignment_id']][] = $r;
    }
} else {
    /* just this student's own submissions, keyed by assignment */
    $my_subs = [];
    $stmt = $conn->prepare("SELECT * FROM assignment_submissions WHERE user_id = ?");
    $stmt->bind_param("i", $me);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $my_subs[$r['assignment_id']] = $r;
}

include 'header.php';
?>
<div class="content">
<div class="container container-mid">

    <div class="page-head">
        <div>
            <h2>Assignments</h2>
            <p>
                <?php if ($lecturer_scoped): ?>
                    The assignments you've posted, and who has submitted.
                <?php elseif ($admin): ?>
                    Every assignment posted this semester, and who has submitted.
                <?php else: ?>
                    Coursework from your lecturers, with due dates and submissions.
                <?php endif; ?>
            </p>
        </div>
        <?php if ($admin): ?><a class="btn btn-primary" href="post_assignment.php">+ Post assignment</a><?php endif; ?>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <?php if (!$assignments): ?>
        <div class="empty-state-box"><h3>No assignments yet</h3></div>
    <?php else: ?>
        <?php foreach ($assignments as $a):
            $overdue = strtotime($a['due_at']) < strtotime('today');
            $mine    = $admin ? null : ($my_subs[$a['id']] ?? null);
        ?>
            <div class="app-card">
                <div class="app-head">
                    <div class="who">
                        <strong><?php echo h($a['title']); ?></strong>
                        <span>Posted by <?php echo h($a['poster']); ?></span>
                    </div>
                    <span class="badge <?php echo $overdue ? 'badge-bad' : 'badge-warn'; ?>">
                        DUE <?php echo h($a['due_at']); ?>
                    </span>
                </div>

                <div class="app-body">
                    <p style="margin:0;"><?php echo nl2br(h($a['instructions'])); ?></p>

                    <?php if ($admin): ?>
                        <?php $subs = $subs_by_assignment[$a['id']] ?? []; ?>
                        <div class="detail-list" style="margin:0;">
                            <div class="full">
                                <span class="k">Submitted</span>
                                <span class="v"><?php echo count($subs); ?> student(s)</span>
                            </div>
                        </div>
                        <?php if ($subs): ?>
                            <div class="detail-list" style="margin:0;">
                                <?php foreach ($subs as $s): ?>
                                    <div class="full">
                                        <span class="k"><?php echo h($s['username']); ?></span>
                                        <span class="v">
                                            <a class="inline-link" href="<?php echo ss_submission_src($s['id']); ?>" target="_blank">
                                                Download &bull; <?php echo h($s['submitted_at']); ?>
                                            </a>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($mine): ?>
                        <div class="app-note is-ok">
                            <strong>Submitted</strong> on <?php echo h($mine['submitted_at']); ?>.
                            <a class="inline-link" href="<?php echo ss_submission_src($mine['id']); ?>" target="_blank">View your file</a>
                        </div>
                        <?php if (!$overdue): ?>
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="assignment_id" value="<?php echo (int) $a['id']; ?>">
                                <div class="field">
                                    <label class="dropzone" data-max-mb="8">
                                        <span class="dz-icon" aria-hidden="true">&#8679;</span>
                                        <span class="dz-name">Replace your submission</span>
                                        <span class="dz-sub">PDF, DOC/DOCX, JPG, PNG or ZIP &middot; max 8 MB</span>
                                        <img class="dz-preview" alt="">
                                        <input type="file" name="file" required>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-sm" data-loading-text="Uploading...">Replace submission</button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($overdue): ?>
                        <div class="app-note is-bad"><strong>Overdue</strong> - no submission on record.</div>

                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="assignment_id" value="<?php echo (int) $a['id']; ?>">
                            <div class="field">
                                <label class="dropzone" data-max-mb="8">
                                    <span class="dz-icon" aria-hidden="true">&#8679;</span>
                                    <span class="dz-name">Drop your file here, or click to choose</span>
                                    <span class="dz-sub">PDF, DOC/DOCX, JPG, PNG or ZIP &middot; max 8 MB</span>
                                    <img class="dz-preview" alt="">
                                    <input type="file" name="file" required>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-sm" data-loading-text="Uploading...">Submit</button>
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
