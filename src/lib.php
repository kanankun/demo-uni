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

if (!function_exists('ss_photo_src')) {
    function ss_photo_src($post_id, $filename = null)
    {
        return 'serve.php?type=gallery&id=' . (int) $post_id;
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

/* Token for the small JSON endpoints (follow.php, bookmark.php). */
function ss_csrf()
{
    if (empty($_SESSION['ss_csrf'])) {
        $_SESSION['ss_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['ss_csrf'];
}

function ss_csrf_ok()
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['ss_csrf']) && hash_equals($_SESSION['ss_csrf'], (string) $sent);
}

/* Link to a member's profile. */
function ss_profile_url($user_id)
{
    return 'profile.php?id=' . (int) $user_id;
}

/* One <img class="avatar"> for a member. */
function ss_avatar_img($user_id, $avatar, $class = 'avatar', $alt = '')
{
    return '<img class="' . h($class) . '" src="' . h(ss_avatar_src($user_id, $avatar))
         . '" alt="' . h($alt) . '" data-fallback="images/avatar-default.png" loading="lazy">';
}

/* Counts used all over the member pages. */
function ss_counts($conn, $user_id)
{
    $out = ['photos' => 0, 'followers' => 0, 'following' => 0, 'likes' => 0];
    $sql = "SELECT
              (SELECT COUNT(*) FROM uploads WHERE user_id = ?) AS photos,
              (SELECT COUNT(*) FROM follows WHERE followee_id = ?) AS followers,
              (SELECT COUNT(*) FROM follows WHERE follower_id = ?) AS following,
              (SELECT COUNT(*) FROM likes l JOIN uploads u ON l.upload_id = u.id WHERE u.user_id = ?) AS likes";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    if ($r = $stmt->get_result()->fetch_assoc()) $out = array_map('intval', $r);
    return $out;
}

/* Does $me follow $them? */
function ss_is_following($conn, $me, $them)
{
    if (!$me || $me == $them) return false;
    $stmt = $conn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followee_id = ?");
    $stmt->bind_param("ii", $me, $them);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_row();
}

/* A follow button, or nothing at all when it would point at yourself. */
function ss_follow_button($conn, $me, $them, $name = '')
{
    if (!$me || $me == $them) return '';
    $following = ss_is_following($conn, $me, $them);
    return '<button type="button" class="btn btn-sm follow-btn' . ($following ? ' is-following' : ' btn-primary')
         . '" data-follow="' . (int) $them . '" data-following="' . ($following ? '1' : '0')
         . '" data-name="' . h($name) . '" aria-pressed="' . ($following ? 'true' : 'false') . '">'
         . ($following ? 'Following' : 'Follow') . '</button>';
}

/* "3 days ago" fallback for when JavaScript is off. */
function ss_when($ts)
{
    $t = strtotime((string) $ts);
    return $t ? date('M j', $t) : '';
}

/* A matric/staff number, derived from the account's id and the year it was
 * created - never stored, so there is nothing new to keep in sync. Staff
 * (is_admin) get an "S" prefix, students an "A". */
function ss_matric($id, $created_at, $is_admin = false)
{
    $yr = $created_at ? date('y', strtotime((string) $created_at)) : '26';
    return ($is_admin ? 'S' : 'A') . $yr . sprintf('%04d', (int) $id);
}

/* "surf, Dawn , long exposure" -> ['surf','dawn','longexposure'], max six. */
function ss_parse_tags($raw)
{
    $out = [];
    foreach (explode(',', (string) $raw) as $t) {
        $t = strtolower(preg_replace('/[^a-z0-9]/i', '', trim($t)));
        if ($t !== '' && strlen($t) <= 30 && !in_array($t, $out, true)) $out[] = $t;
        if (count($out) >= 6) break;
    }
    return $out;
}

/* What the "Photo details" panel shows. Read off the file itself - size,
 * dimensions, format and any EXIF the camera left behind - exactly like the
 * main site does, so there is nothing to keep in sync in the database. */
function ss_photo_details($post)
{
    $d = ['Uploaded' => !empty($post['created_at'])
        ? date('M j, Y g:i a', strtotime($post['created_at'])) . ' UTC' : 'Unknown'];

    $path = ss_photo_path($post['filename'] ?? '');
    if (!$path || !is_file($path)) return $d;

    $bytes = @filesize($path);
    if ($bytes !== false) {
        $d['File size'] = $bytes >= 1048576
            ? round($bytes / 1048576, 2) . ' MB'
            : max(1, round($bytes / 1024)) . ' KB';
    }

    $info = @getimagesize($path);
    if (!$info) return $d;
    $d['Dimensions'] = $info[0] . ' x ' . $info[1] . ' px';
    $d['Format'] = !empty($info['mime']) ? strtoupper(preg_replace('#^image/#', '', $info['mime'])) : 'Unknown';

    if (function_exists('exif_read_data') && $info[2] === IMAGETYPE_JPEG) {
        try { $ex = @exif_read_data($path, 'ANY_TAG', false); } catch (Throwable $e) { $ex = false; }
        if (is_array($ex)) {
            $frac = function ($v) {
                if (is_string($v) && strpos($v, '/') !== false) {
                    list($a, $b) = explode('/', $v, 2);
                    return (float) $b ? (float) $a / (float) $b : null;
                }
                return is_numeric($v) ? (float) $v : null;
            };
            $cam = trim(($ex['Make'] ?? '') . ' ' . ($ex['Model'] ?? ''));
            if ($cam !== '') $d['Camera'] = $cam;
            if (isset($ex['ExposureTime'])) $d['Exposure'] = $ex['ExposureTime'] . ' s';
            if (isset($ex['FNumber']) && ($f = $frac($ex['FNumber']))) $d['Aperture'] = 'f/' . round($f, 1);
            if (isset($ex['ISOSpeedRatings'])) {
                $d['ISO'] = is_array($ex['ISOSpeedRatings']) ? $ex['ISOSpeedRatings'][0] : $ex['ISOSpeedRatings'];
            }
            if (isset($ex['FocalLength']) && ($f = $frac($ex['FocalLength']))) $d['Focal length'] = round($f) . ' mm';
        }
    }
    return $d;
}

/* Where a gallery file lives on disk. A lab that stores uploads inside the
 * web root defines its own in lab.php. */
if (!function_exists('ss_photo_path')) {
    function ss_photo_path($filename)
    {
        $filename = basename((string) $filename);
        return $filename === '' ? null : __DIR__ . '/../uploads/gallery/' . $filename;
    }
}

/* Solve state for the labs that ship the XSS harness. Absent elsewhere. */
if (file_exists(__DIR__ . '/solve.php')) {
    function ss_solved()
    {
        $raw = @file_get_contents('/var/www/captured/solved.json');
        $d = $raw ? json_decode($raw, true) : null;
        return is_array($d) ? $d + ['solved' => false, 'at' => null, 'payload' => null]
                            : ['solved' => false, 'at' => null, 'payload' => null];
    }
}
