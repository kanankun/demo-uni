<?php
/* Edit your own profile + avatar.
 *
 * [VULN - no validation at all]
 * The avatar handler keeps the name the browser sent and drops the file
 * into this player's own directory, inside the web root. No extension check, no
 * content check, no rename. Whatever you send is reachable at
 * /avatars/<your filename>, and the server will run it if the name says
 * it is a script. */
include 'db.php';
ss_require_login();

$me = ss_uid();
$page_title = 'Edit profile';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $stmt = $conn->prepare("UPDATE users SET bio=?, full_name=?, phone=?, ic_number=?, address=? WHERE id=?");
    $stmt->bind_param("sssssi",
        $_POST['bio'], $_POST['full_name'], $_POST['phone'],
        $_POST['ic_number'], $_POST['address'], $me);
    $stmt->execute();
    ss_flash("Profile saved.");
    header("Location: settings.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['avatar']['name'])) {
    $dir = ss_inst_dir() . "/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $name = basename($_FILES['avatar']['name']);

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $name)) {
        $stmt = $conn->prepare("UPDATE users SET avatar=? WHERE id=?");
        $stmt->bind_param("si", $name, $me);
        $stmt->execute();
        ss_flash("Saved to " . ss_inst_url() . "/" . $name);
        header("Location: settings.php");
        exit;
    }
    $error = "Upload failed.";
}

$user = ss_user($conn, $me);
include 'header.php';
?>
<div class="content">
<div class="container" style="max-width:620px;">

    <div class="page-head">
        <h2>Edit profile</h2>
        <a href="profile.php" class="inline-link">&larr; BACK TO PROFILE</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <div style="text-align:center; margin-bottom:28px;">
        <?php /* Link straight to the raw uploaded file, not through
                 serve.php - a click opens it in a new tab in this same
                 browser, so whatever you uploaded runs with this tab's
                 own cookies instead of a URL copied somewhere else. */ ?>
        <?php if (!empty($user['avatar'])): ?>
        <a href="<?php echo h(ss_inst_url() . '/' . $user['avatar']); ?>" target="_blank" rel="noopener">
        <?php endif; ?>
        <img class="profile-avatar" src="<?php echo ss_avatar_src($me, $user['avatar']); ?>"
             alt="" data-fallback="images/avatar-default.png">
        <?php if (!empty($user['avatar'])): ?>
        </a>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" style="margin-top:14px;">
            <label class="dropzone">
                <span class="dz-icon" aria-hidden="true">&#8679;</span>
                <span class="dz-name">Choose a new avatar</span>
                <span class="dz-sub">Profile picture</span>
                <img class="dz-preview" alt="">
                <input type="file" name="avatar">
            </label>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px;">Upload avatar</button>
        </form>
    </div>

    <form method="post">
        <input type="hidden" name="update_info" value="1">

        <div class="field">
            <label for="bio">Bio</label>
            <input type="text" id="bio" name="bio" maxlength="255" data-counter value="<?php echo h($user['bio']); ?>">
        </div>
        <div class="field">
            <label for="fn">Full name</label>
            <input type="text" id="fn" name="full_name" value="<?php echo h($user['full_name']); ?>">
        </div>
        <div class="field">
            <label for="ph">Phone number</label>
            <input type="text" id="ph" name="phone" value="<?php echo h($user['phone']); ?>">
        </div>
        <div class="field">
            <label for="ic">National IC / ID</label>
            <input type="text" id="ic" name="ic_number" value="<?php echo h($user['ic_number']); ?>">
        </div>
        <div class="field">
            <label for="ad">Home address</label>
            <textarea id="ad" name="address" rows="3"><?php echo h($user['address']); ?></textarea>
        </div>

        <button type="submit">Save changes</button>
    </form>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
