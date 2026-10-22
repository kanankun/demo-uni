<?php
/* Post an announcement. Staff/admin only - students only read the board.
 *
 * Text, not a photo upload. An announcement is a notice, and requiring an
 * image attachment to post one is why the board kept ending up full of
 * photo captions instead of actual notices. */
include 'db.php';
ss_require_login();

if (!ss_is_admin($conn)) {
    http_response_code(403);
    exit("Only staff can post announcements.");
}

$page_title = 'Post announcement';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if ($title === '') {
        $error = "Give the announcement a title.";
    } elseif ($body === '') {
        $error = "Write the notice itself - the body can't be empty.";
    } else {
        $uid  = ss_uid();
        $stmt = $conn->prepare("INSERT INTO announcements (user_id, title, body) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $uid, $title, $body);
        $stmt->execute();

        header("Location: announcements.php");
        exit;
    }
}

include 'header.php';
?>
<div class="content">
<div class="container" style="max-width:640px;">
    <h2>Post announcement</h2>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <form method="post">
        <div class="field">
            <label for="t">Title <span class="req">*</span></label>
            <input type="text" id="t" name="title" required maxlength="150" data-counter placeholder="e.g. Mid-term exam schedule update" value="<?php echo h($_POST['title'] ?? ''); ?>">
        </div>

        <div class="field">
            <label for="b">Notice <span class="req">*</span></label>
            <textarea id="b" name="body" required rows="6" placeholder="What students need to know, and what to do about it."><?php echo h($_POST['body'] ?? ''); ?></textarea>
        </div>

        <button type="submit" data-loading-text="Posting...">Post announcement</button>
    </form>

    <div class="form-links"><p><a href="announcements.php" class="inline-link">BACK TO ANNOUNCEMENTS</a></p></div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
