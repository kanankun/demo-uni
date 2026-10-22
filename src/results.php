<?php
/* Exam results. Read-only display page for the campus-portal reskin.
 *
 * Deliberately NOT part of the vulnerability chain: everything shown here
 * is generated from the logged-in user's own id (never from request input),
 * so there is nothing here for a payload to reach. The one query is a
 * prepared statement keyed to $me, same as every other "my own data" page
 * in this app. */
include 'db.php';
ss_require_login();

$me  = ss_uid();
$row = ss_user($conn, $me);

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

mt_srand($me * 2654435761 % 2147483647);
$totalPoints = 0;
$totalCredits = 0;
foreach ($courses as &$c) {
    $c['grade'] = $grades[mt_rand(0, count($grades) - 1)];
    $totalPoints  += $gradeVal[$c['grade']] * $c['credits'];
    $totalCredits += $c['credits'];
}
unset($c);
$gpa = $totalCredits ? round($totalPoints / $totalCredits, 2) : 0;

include 'header.php';
?>
<div class="content">
<div class="container container-mid">
    <div class="page-head">
        <div>
            <h2>Examination results</h2>
            <p>Semester results for <?php echo h($row['full_name'] ?: $row['username']); ?>.</p>
        </div>
    </div>

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
        </tbody>
    </table>

    <div class="detail-list" style="margin-top:20px;">
        <div><span class="k">Credits attempted</span><span class="v"><?php echo (int) $totalCredits; ?></span></div>
        <div><span class="k">Cumulative GPA</span><span class="v"><strong><?php echo h((string) $gpa); ?></strong></span></div>
    </div>

    <p class="hint" style="margin-top:18px;">This is a sandbox transcript generated for the Week 1 demo - it is not connected to any real academic record.</p>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
