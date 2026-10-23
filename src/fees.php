<?php
/* Fee statement.
 *
 * Same note as results.php: the generated statement is keyed to
 * $owner_id, which for a student is always their own id. Staff can look
 * up a given student's statement by id and mark an item paid - gated on
 * ss_is_admin(), a prepared statement either way.
 *
 * The generated items are a believable baseline, same as before. `fees`
 * only ever records a PAID override - there's nothing to un-generate, so
 * there's nothing else for it to say. */
include 'db.php';
ss_require_login();

$me      = ss_uid();
$staff   = ss_is_admin($conn);
$id_req  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$owner_id = ($staff && $id_req) ? $id_req : $me;

$owner = ss_user($conn, $owner_id);
if (!$owner) { http_response_code(404); exit("Account not found."); }
if ($owner['is_admin'] && $owner_id !== $me) { http_response_code(404); exit("Staff accounts don't have a fee statement."); }

if ($staff && isset($_GET['mark_paid'])) {
    $label = trim($_GET['mark_paid']);
    $stmt = $conn->prepare(
        "INSERT INTO fees (user_id, label, status, updated_by) VALUES (?, ?, 'paid', ?)
         ON DUPLICATE KEY UPDATE status = 'paid', updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param("isi", $owner_id, $label, $me);
    $stmt->execute();
    ss_flash("Marked paid.");
    header("Location: fees.php?id=$owner_id");
    exit;
}

$page_title = 'Fees';

$items = [
    ['label' => 'Tuition fee',      'amount' => 2400.00],
    ['label' => 'Library fee',      'amount' => 60.00],
    ['label' => 'Laboratory fee',   'amount' => 180.00],
    ['label' => 'Examination fee',  'amount' => 90.00],
    ['label' => 'Student activity fee', 'amount' => 40.00],
];

mt_srand($owner_id * 40503 % 2147483647);
$paidUpTo = mt_rand(0, count($items));

$stmt = $conn->prepare("SELECT label FROM fees WHERE user_id = ? AND status = 'paid'");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$res = $stmt->get_result();
$overridden_paid = [];
while ($r = $res->fetch_assoc()) $overridden_paid[$r['label']] = true;

$total = 0;
$paid  = 0;
foreach ($items as $i => $it) {
    $total += $it['amount'];
    $isPaid = ($i < $paidUpTo) || isset($overridden_paid[$it['label']]);
    if ($isPaid) $paid += $it['amount'];
}
$outstanding = $total - $paid;

include 'header.php';
?>
<div class="content">
<div class="container container-mid">
    <div class="page-head">
        <div>
            <h2>Fee statement</h2>
            <p>Billed to <?php echo h($owner['full_name'] ?: $owner['username']); ?>, current semester.</p>
        </div>
    </div>

    <table class="detail-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:2px solid var(--ink); padding:8px 6px;">Item</th>
                <th style="text-align:right; border-bottom:2px solid var(--ink); padding:8px 6px;">Amount (RM)</th>
                <th style="text-align:center; border-bottom:2px solid var(--ink); padding:8px 6px;">Status</th>
                <?php if ($staff): ?><th style="width:110px;"></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $it): $isPaid = ($i < $paidUpTo) || isset($overridden_paid[$it['label']]); ?>
            <tr>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line);"><?php echo h($it['label']); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:right;"><?php echo number_format($it['amount'], 2); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:center;">
                    <span class="badge <?php echo $isPaid ? 'badge-ok' : 'badge-warn'; ?>"><?php echo $isPaid ? 'PAID' : 'OUTSTANDING'; ?></span>
                </td>
                <?php if ($staff): ?>
                    <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:right;">
                        <?php if (!$isPaid): ?>
                            <a class="btn btn-sm" href="fees.php?id=<?php echo (int) $owner_id; ?>&mark_paid=<?php echo h(urlencode($it['label'])); ?>">Mark paid</a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="detail-list" style="margin-top:20px;">
        <div><span class="k">Total billed</span><span class="v">RM <?php echo number_format($total, 2); ?></span></div>
        <div><span class="k">Paid</span><span class="v">RM <?php echo number_format($paid, 2); ?></span></div>
        <div><span class="k">Outstanding</span><span class="v"><strong>RM <?php echo number_format($outstanding, 2); ?></strong></span></div>
    </div>

    <?php if (!$staff): ?>
        <p class="hint" style="margin-top:18px;">This is a sandbox fee statement generated for the Week 1 demo - no real billing is attached to it.</p>
    <?php endif; ?>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
