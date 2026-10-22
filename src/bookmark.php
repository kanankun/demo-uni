<?php
/* Save / unsave a photo, called by app.js. */
include 'db.php';
header('Content-Type: application/json');

$me = ss_uid();
if (!$me)          { echo json_encode(['ok' => false, 'error' => 'Log in first.']); exit; }
if (!ss_csrf_ok()) { echo json_encode(['ok' => false, 'error' => 'Session expired, reload the page.']); exit; }

$pid    = (int) ($_POST['upload_id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$pid) { echo json_encode(['ok' => false, 'error' => 'Nothing to do.']); exit; }

if ($action === 'save') {
    $stmt = $conn->prepare("INSERT IGNORE INTO bookmarks (user_id, upload_id) VALUES (?, ?)");
} else {
    $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND upload_id = ?");
}
$stmt->bind_param("ii", $me, $pid);
$stmt->execute();

echo json_encode(['ok' => true, 'saved' => $action === 'save']);
