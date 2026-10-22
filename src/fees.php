<?php
/* Fee statement. Read-only display page for the campus-portal reskin.
 *
 * Same note as results.php: nothing here is built from request input, so
 * there is no new injection surface - it is flavour for the portal theme,
 * not part of the vulnerability chain. */
include 'db.php';
ss_require_login();

$me  = ss_uid();
$row = ss_user($conn, $me);

$page_title = 'Fees';

$items = [
    ['label' => 'Tuition fee',      'amount' => 2400.00],
    ['label' => 'Library fee',      'amount' => 60.00],
    ['label' => 'Laboratory fee',   'amount' => 180.00],
    ['label' => 'Examination fee',  'amount' => 90.00],
    ['label' => 'Student activity fee', 'amount' => 40.00],
];

mt_srand($me * 40503 % 2147483647);
$paidUpTo = mt_rand(0, count($items));
$total = 0;
$paid  = 0;
foreach ($items as $i => $it) {
    $total += $it['amount'];
    if ($i < $paidUpTo) $paid += $it['amount'];
}
$outstanding = $total - $paid;

include 'header.php';
?>
<div class="content">
<div class="container container-mid">
    <div class="page-head">
        <div>
            <h2>Fee statement</h2>
            <p>Billed to <?php echo h($row['full_name'] ?: $row['username']); ?>, current semester.</p>
        </div>
    </div>

    <table class="detail-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom:2px solid var(--ink); padding:8px 6px;">Item</th>
                <th style="text-align:right; border-bottom:2px solid var(--ink); padding:8px 6px;">Amount (RM)</th>
                <th style="text-align:center; border-bottom:2px solid var(--ink); padding:8px 6px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $it): $isPaid = $i < $paidUpTo; ?>
            <tr>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line);"><?php echo h($it['label']); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:right;"><?php echo number_format($it['amount'], 2); ?></td>
                <td style="padding:8px 6px; border-bottom:1px solid var(--line); text-align:center;">
                    <span class="badge <?php echo $isPaid ? 'badge-ok' : 'badge-warn'; ?>"><?php echo $isPaid ? 'PAID' : 'OUTSTANDING'; ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="detail-list" style="margin-top:20px;">
        <div><span class="k">Total billed</span><span class="v">RM <?php echo number_format($total, 2); ?></span></div>
        <div><span class="k">Paid</span><span class="v">RM <?php echo number_format($paid, 2); ?></span></div>
        <div><span class="k">Outstanding</span><span class="v"><strong>RM <?php echo number_format($outstanding, 2); ?></strong></span></div>
    </div>

    <p class="hint" style="margin-top:18px;">This is a sandbox fee statement generated for the Week 1 demo - no real billing is attached to it.</p>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
