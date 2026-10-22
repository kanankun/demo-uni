<?php
/* Post an assignment. Staff/admin only - students only submit to it. */
include 'db.php';
ss_require_login();

if (!ss_is_admin($conn)) {
    http_response_code(403);
    exit("Only staff can post assignments.");
}

$page_title = 'Post assignment';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $due_at       = trim($_POST['due_at'] ?? '');

    if ($title === '') {
        $error = "Give the assignment a title.";
    } elseif ($instructions === '') {
        $error = "Write what students need to do.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_at)) {
        $error = "Pick a due date.";
    } else {
        $uid  = ss_uid();
        $stmt = $conn->prepare("INSERT INTO assignments (title, instructions, due_at, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $instructions, $due_at, $uid);
        $stmt->execute();

        header("Location: assignments.php");
        exit;
    }
}

include 'header.php';
?>
<div class="content">
<div class="container" style="max-width:640px;">
    <h2>Post assignment</h2>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <form method="post">
        <div class="field">
            <label for="t">Title <span class="req">*</span></label>
            <input type="text" id="t" name="title" required maxlength="150" placeholder="e.g. Database Systems Lab - ER diagram" value="<?php echo h($_POST['title'] ?? ''); ?>">
        </div>

        <div class="field">
            <label for="i">Instructions <span class="req">*</span></label>
            <textarea id="i" name="instructions" required rows="6" placeholder="What students need to submit, and in what format."><?php echo h($_POST['instructions'] ?? ''); ?></textarea>
        </div>

        <div class="field">
            <label for="d">Due date <span class="req">*</span></label>
            <input type="date" id="d" name="due_at" required value="<?php echo h($_POST['due_at'] ?? ''); ?>">
        </div>

        <button type="submit" data-loading-text="Posting...">Post assignment</button>
    </form>

    <div class="form-links"><p><a href="assignments.php" class="inline-link">BACK TO ASSIGNMENTS</a></p></div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
