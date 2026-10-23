<?php
/* Shared helpers for every University A lab.
 *
 * db.php includes this file LAST, so a lab that wants a different
 * authorisation model just defines its own ss_is_admin() before the
 * include and the version below steps aside.
 */

if (!function_exists('ss_is_admin')) {
    /* Default: ask the database who the current user is. */
    function ss_is_admin($conn)
    {
        if (empty($_SESSION['user_id'])) return false;
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return !empty($row['is_admin']);
    }
}

/* A lecturer is an admin account for everything ss_is_admin() already
 * gates (posting, approving, the ADMIN tab) - this only narrows the one
 * thing that shouldn't extend to them: admin.php's password reset. */
function ss_is_lecturer($conn)
{
    if (empty($_SESSION['user_id'])) return false;
    $stmt = $conn->prepare("SELECT is_lecturer FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return !empty($row['is_lecturer']);
}

/* Current user id, or 0 for a guest. */
function ss_uid()
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

/* Send the visitor to the sign-in page unless they are signed in. */
function ss_require_login()
{
    if (!ss_uid()) {
        header("Location: index.php");
        exit;
    }
}

/* One row from users, or null. */
function ss_user($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/* Queue a toast for the next page load. */
function ss_flash($text, $type = 'success')
{
    if (!isset($_SESSION['ss_flash'])) $_SESSION['ss_flash'] = [];
    $_SESSION['ss_flash'][] = ['text' => $text, 'type' => $type];
}

/* Where images come from. A lab that stores uploads inside the web root
 * defines its own pair in lab.php and these step aside. */
if (!function_exists('ss_avatar_src')) {
    function ss_avatar_src($user_id, $avatar)
    {
        return empty($avatar)
            ? 'images/avatar-default.png'
            : 'serve.php?type=avatar&id=' . (int) $user_id;
    }
}

if (!function_exists('ss_app_src')) {
    function ss_app_src($app_id, $file_path = null)
    {
        return 'serve.php?type=app&id=' . (int) $app_id;
    }
}

if (!function_exists('ss_submission_src')) {
    function ss_submission_src($submission_id)
    {
        return 'serve.php?type=submission&id=' . (int) $submission_id;
    }
}

/* Shorthand for htmlspecialchars. Deliberately NOT used on the pages that
 * are supposed to be injectable - grep for it to see what is defended. */
function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/* ---------------------------------------------------------------
 * Social helpers. Everything here is parameterised and escaped: the
 * baseline app is meant to be sound, so that each lab's one deliberate
 * weakness is the only thing to find.
 * --------------------------------------------------------------- */

/* Token for the small JSON POST endpoints. */
function ss_csrf()
{
    if (empty($_SESSION['ss_csrf'])) {
        $_SESSION['ss_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['ss_csrf'];
}

/* Link to a member's profile. */
function ss_profile_url($user_id)
{
    return 'profile.php?id=' . (int) $user_id;
}

/* A matric/staff number, derived from the account's id and the year it was
 * created - never stored, so there is nothing new to keep in sync. Staff
 * (is_admin) get an "S" prefix, students an "A". */
function ss_matric($id, $created_at, $is_admin = false)
{
    $yr = $created_at ? date('y', strtotime((string) $created_at)) : '26';
    return ($is_admin ? 'S' : 'A') . $yr . sprintf('%04d', (int) $id);
}

