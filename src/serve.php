<?php
/* Streams files that live OUTSIDE the web root (/var/www/uploads).
 * Nothing in that directory is reachable by URL, so every read goes
 * through here and the handler decides who may see what. */
include 'db.php';

$type = $_GET['type'] ?? '';
$id   = (int) ($_GET['id'] ?? 0);
$uid  = ss_uid();

if (!$id || !$type) { http_response_code(400); exit("Invalid request."); }

$path = "";

if ($type === 'avatar') {                       /* public */
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        if (!empty($row['avatar'])) $path = "../uploads/avatars/" . basename($row['avatar']);
    }

} elseif ($type === 'gallery') {                /* public */
    $stmt = $conn->prepare("SELECT filename FROM uploads WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $path = "../uploads/gallery/" . basename($row['filename']);
    }

} elseif ($type === 'app') {                    /* owner or admin only */
    if (!$uid) { http_response_code(403); exit("Access denied."); }

    $stmt = $conn->prepare("SELECT user_id, file_path FROM applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !(ss_is_admin($conn) || $row['user_id'] == $uid)) {
        http_response_code(403);
        exit("Access denied.");
    }
    $path = "../uploads/apps/" . $row['file_path'];   /* contained by the realpath check below */

} elseif ($type === 'submission') {             /* owner or admin only */
    if (!$uid) { http_response_code(403); exit("Access denied."); }

    $stmt = $conn->prepare("SELECT user_id, file_path FROM assignment_submissions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !(ss_is_admin($conn) || $row['user_id'] == $uid)) {
        http_response_code(403);
        exit("Access denied.");
    }
    $path = "../uploads/assignments/" . $row['file_path'];   /* contained by the realpath check below */
}

/* Final containment check: never serve anything from outside /var/www/uploads. */
$base = realpath(__DIR__ . "/../uploads");
$real = $path ? realpath($path) : false;

if ($real && $base && strpos($real, $base) === 0 && is_file($real)) {
    header("Content-Type: " . (mime_content_type($real) ?: 'application/octet-stream'));
    header("Content-Length: " . filesize($real));
    header("X-Content-Type-Options: nosniff");
    readfile($real);
    exit;
}

http_response_code(404);
echo "File not found.";
