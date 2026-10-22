<?php
/* Follow / unfollow, called by app.js. */
include 'db.php';
header('Content-Type: application/json');

$me = ss_uid();
if (!$me)          { echo json_encode(['ok' => false, 'error' => 'Log in first.']); exit; }
if (!ss_csrf_ok()) { echo json_encode(['ok' => false, 'error' => 'Session expired, reload the page.']); exit; }

$them   = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$them || $them === $me) { echo json_encode(['ok' => false, 'error' => 'Nothing to do.']); exit; }

if ($action === 'follow') {
    $stmt = $conn->prepare("INSERT IGNORE INTO follows (follower_id, followee_id) VALUES (?, ?)");
} else {
    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND followee_id = ?");
}
$stmt->bind_param("ii", $me, $them);
$stmt->execute();

$stmt = $conn->prepare("SELECT COUNT(*) FROM follows WHERE followee_id = ?");
$stmt->bind_param("i", $them);
$stmt->execute();
$followers = (int) $stmt->get_result()->fetch_row()[0];

echo json_encode([
    'ok'        => true,
    'following' => $action === 'follow',
    'followers' => $followers,
]);
