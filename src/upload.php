<?php
/* Post an announcement. Staff/admin only - students only read the board.
 *
 * NOT the vulnerability chain's upload vector - this one checks the file
 * extension and actual image contents. The unrestricted upload is the
 * avatar form on settings.php. */
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

    $dir = "../uploads/gallery/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext     = strtolower(pathinfo($_FILES['image']['name'] ?? '', PATHINFO_EXTENSION));

    if ($title === '') {
        $error = "Give the announcement a title.";
    } elseif (!in_array($ext, $allowed, true) || getimagesize($_FILES['image']['tmp_name']) === false) {
        $error = "Only real JPG, PNG or GIF files are accepted.";
    } else {
        $name = uniqid("img_") . "_" . rand(1000, 9999) . "." . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $name)) {
            $uid  = ss_uid();
            $stmt = $conn->prepare("INSERT INTO uploads (user_id, title, filename) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $uid, $title, $name);
            $stmt->execute();

            header("Location: announcements.php");
            exit;
        }
        $error = "Upload failed.";
    }
}

include 'header.php';
?>
<div class="content">
<div class="container" style="max-width:640px;">
    <h2>Post announcement</h2>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="field">
            <label for="f">Attachment <span class="req">*</span></label>
            <label class="dropzone" data-max-mb="8">
                <span class="dz-icon" aria-hidden="true">&#8679;</span>
                <span class="dz-name">Drop a file here, or click to choose</span>
                <span class="dz-sub">JPG, PNG or GIF &middot; max 8 MB</span>
                <img class="dz-preview" alt="">
                <input type="file" id="f" name="image" required accept=".jpg,.jpeg,.png,.gif">
            </label>
        </div>

        <div class="field">
            <label for="t">Title <span class="req">*</span></label>
            <input type="text" id="t" name="title" required maxlength="150" data-counter placeholder="e.g. Mid-term exam schedule update">
        </div>

        <button type="submit" data-loading-text="Posting...">Post announcement</button>
    </form>

    <div class="form-links"><p><a href="announcements.php" class="inline-link">BACK TO ANNOUNCEMENTS</a></p></div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
