<?php
/* Edit a post. Only the owner (or an admin) may touch it. */
include 'db.php';
ss_require_login();

$post_id = (int) ($_GET['id'] ?? 0);
$me      = ss_uid();
$error   = "";

$stmt = $conn->prepare("SELECT * FROM uploads WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { http_response_code(404); exit("Post not found."); }

if ($post['user_id'] != $me && !ss_is_admin($conn)) {
    http_response_code(403);
    exit("Access denied: this is not your post.");
}

$page_title = 'Edit post';

$tags = [];
$stmt = $conn->prepare("SELECT tag FROM upload_tags WHERE upload_id = ? ORDER BY id");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_row()) $tags[] = $r[0];

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM uploads WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    header("Location: index.php?msg=deleted");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $name  = $post['filename'];

    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true) || getimagesize($_FILES['image']['tmp_name']) === false) {
            $error = "Only real JPG, PNG or GIF images are accepted.";
        } else {
            $name = uniqid("img_") . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/gallery/" . $name);
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE uploads SET title = ?, filename = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $name, $post_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM upload_tags WHERE upload_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        foreach (ss_parse_tags($_POST['tags'] ?? '') as $tag) {
            $stmt = $conn->prepare("INSERT INTO upload_tags (upload_id, tag) VALUES (?, ?)");
            $stmt->bind_param("is", $post_id, $tag);
            $stmt->execute();
        }

        header("Location: view.php?id=$post_id&msg=edited");
        exit;
    }
}

include 'header.php';
?>
<div class="content">
<div class="container" style="max-width:640px;">
    <h2>Edit post</h2>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <div style="text-align:center; margin-bottom:22px;">
        <img src="<?php echo h(ss_photo_src($post['id'], $post['filename'])); ?>" alt="" style="max-height:180px; border:2px solid #000;">
    </div>

    <form method="post" enctype="multipart/form-data">
        <div class="field">
            <label for="t">Title</label>
            <input type="text" id="t" name="title" required maxlength="150" data-counter value="<?php echo h($post['title']); ?>">
        </div>

        <div class="field">
            <label for="tg">Tags</label>
            <input type="text" id="tg" name="tags" maxlength="120" value="<?php echo h(implode(', ', $tags)); ?>">
        </div>

        <div class="field">
            <label for="f">Replace image (optional)</label>
            <label class="dropzone" data-max-mb="8">
                <span class="dz-icon" aria-hidden="true">&#8679;</span>
                <span class="dz-name">Choose a replacement</span>
                <span class="dz-sub">JPG, PNG or GIF &middot; max 8 MB</span>
                <img class="dz-preview" alt="">
                <input type="file" id="f" name="image" accept=".jpg,.jpeg,.png,.gif">
            </label>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Update post</button>
            <a href="view.php?id=<?php echo $post_id; ?>" class="btn">Cancel</a>
        </div>
    </form>

    <div class="danger-zone">
        <p>Danger zone</p>
        <a class="btn btn-danger" href="edit.php?id=<?php echo $post_id; ?>&delete=1"
           data-confirm="This cannot be undone. The shadow will be lost forever."
           data-confirm-title="Delete this post?" data-confirm-ok="Delete" data-danger>
            Delete this post permanently
        </a>
    </div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
