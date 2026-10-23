<?php
/* Exam results.
 *
 * Deliberately NOT part of the vulnerability chain: the generated
 * transcript below is keyed to $owner_id, which for a student is always
 * their own id (never from request input). Staff can look up a given
 * student's transcript by id - gated on ss_is_admin(), a prepared
 * statement, nothing a payload can reach.
 *
 * The generated rows are a believable baseline, same as before - a
 * deterministic transcript nobody had to type in. `results` holds the
 * real ones: whatever staff have actually entered, on top of it. */
include 'db.php';
ss_require_login();

$me      = ss_uid();
$staff   = ss_is_admin($conn);
$id_req  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$owner_id = ($staff && $id_req) ? $id_req : $me;

$owner = ss_user($conn, $owner_id);
if (!$owner) { http_response_code(404); exit("Account not found."); }
if ($owner['is_admin'] && $owner_id !== $me) { http_response_code(404); exit("Staff accounts don't have a transcript."); }

$error = "";
if ($staff && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code    = trim($_POST['course_code'] ?? '');
    $title   = trim($_POST['course_title'] ?? '');
    $credits = (int) ($_POST['credits'] ?? 0);
    $grade   = trim($_POST['grade'] ?? '');

    if ($code === '' || $title === '' || $grade === '') {
        $error = "Fill in the course code, title and grade.";
    } elseif ($credits < 1 || $credits > 6) {
        $error = "Credits should be between 1 and 6.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO results (user_id, course_code, course_title, credits, grade, entered_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issisi", $owner_id, $code, $title, $credits, $grade, $me);
        $stmt->execute();
        ss_flash("Result added.");
        header("Location: results.php?id=$owner_id");
        exit;
    }
}

$page_title = 'Results';

/* Deterministic, believable-looking transcript - same user always sees the
 * same rows, nothing here is attacker-influenced. */
$courses = [
    ['code' => 'CSC3203', 'name' => 'Database Systems',            'credits' => 3],
    ['code' => 'CSC3303', 'name' => 'Computer Networks',           'credits' => 3],
    ['code' => 'CSC3403', 'name' => 'Software Engineering',        'credits' => 4],
    ['code' => 'ITT3013', 'name' => 'Information Security',        'credits' => 3],
    ['code' => 'ULR2012', 'name' => 'Academic Writing',            'credits' => 2],
    ['code' => 'CSC3503', 'name' => 'Industrial Training',         'credits' => 4],
];
$grades    = ['A', 'A-', 'B+', 'B', 'B-', 'C+'];
$gradeVal  = ['A' => 4.00, 'A-' => 3.67, 'B+' => 3.33, 'B' => 3.00, 'B-' => 2.67, 'C+' => 2.33];

mt_srand($owner_id * 2654435761 % 2147483647);
$totalPoints = 0;
$totalCredits = 0;
foreach ($courses as &$c) {
    $c['grade'] = $grades[mt_rand(0, count($grades) - 1)];
    $totalPoints  += $gradeVal[$c['grade']] * $c['credits'];
    $totalCredits += $c['credits'];
}
unset($c);

/* Results staff actually entered, on top of the generated baseline. */
$stmt = $conn->prepare("SELECT r.*, us.username AS entered_by_name FROM results r LEFT JOIN users us ON r.entered_by = us.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$res = $stmt->get_result();
$entered = [];
while ($r = $res->fetch_assoc()) {
    $entered[] = $r;
    $totalPoints  += ($gradeVal[$r['grade']] ?? 0) * (int) $r['credits'];
    $totalCredits += (int) $r['credits'];
}

$gpa = $totalCredits ? round($totalPoints / $totalCredits, 2) : 0;

include 'header.php';
?>
<div class="content">
<div class="container container-mid">
    <div class="page-head">
        <div>
            <h2>Examination results</h2>
            <p>Semester results for <?php echo h($owner['full_name'] ?: $owner['username']); ?>.</p>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?php echo h($error); ?></div><?php endif; ?>

    <table class="detail-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:2px solid var(--ink); padding:8px 6px;">Code</th>
                <th style="text-align:left; border-bottom:2px solid var(--ink); padding:8px 6px;">Course</th>
                <th style="text-align:center; border-bottom:2px solid var(--ink); padding:8px 6px;">Credits</th>
                <th style="text-align:center; border-bottom:2px solid var(--ink); padding:8px 6px;">Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $c): ?>
            <tr>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line);"><?php echo h($c['code']); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line);"><?php echo h($c['name']); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:center;"><?php echo (int) $c['credits']; ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:center;"><strong><?php echo h($c['grade']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <?php foreach ($entered as $r): ?>
            <tr>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line);"><?php echo h($r['course_code']); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line);">
                    <?php echo h($r['course_title']); ?>
                    <span class="hint" style="display:block;">entered by <?php echo h($r['entered_by_name'] ?: 'staff'); ?>, <?php echo h($r['created_at']); ?></span>
                </td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:center;"><?php echo (int) $r['credits']; ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:center;"><strong><?php echo h($r['grade']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="detail-list" style="margin-top:20px;">
        <div><span class="k">Credits attempted</span><span class="v"><?php echo (int) $totalCredits; ?></span></div>
        <div><span class="k">Cumulative GPA</span><span class="v"><strong><?php echo h((string) $gpa); ?></strong></span></div>
    </div>

    <?php if (!$staff): ?>
        <p class="hint" style="margin-top:18px;">This is a sandbox transcript generated for the Week 1 demo - it is not connected to any real academic record.</p>
    <?php else: ?>
        <h3 class="section-title">Add a result</h3>
        <form method="post" style="max-width:520px;">
            <div class="field">
                <label for="rc">Course code</label>
                <input type="text" id="rc" name="course_code" required maxlength="20" placeholder="e.g. CSC4101">
            </div>
            <div class="field">
                <label for="rt">Course title</label>
                <input type="text" id="rt" name="course_title" required maxlength="120" placeholder="e.g. Mobile App Development">
            </div>
            <div class="field">
                <label for="rcr">Credits</label>
                <input type="number" id="rcr" name="credits" required min="1" max="6" value="3">
            </div>
            <div class="field">
                <label for="rg">Grade</label>
                <select id="rg" name="grade" required>
                    <?php foreach (['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'] as $g): ?>
                        <option value="<?php echo h($g); ?>"><?php echo h($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Add result</button>
        </form>
    <?php endif; ?>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
