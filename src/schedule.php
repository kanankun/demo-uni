<?php
/* The class timetable. Applications are handled on apply.php. */
include 'db.php';
ss_require_login();

$page_title = 'Timetable';

$sessions = [];
$res = $conn->query("SELECT * FROM class_sessions ORDER BY starts_at ASC");
while ($res && $r = $res->fetch_assoc()) $sessions[] = $r;

include 'header.php';
?>
<div class="content">
<div class="container container-mid">

    <div class="page-head">
        <div>
            <h2>Timetable</h2>
            <p>Four semesters a year. Register once, attend the sessions you are placed in.</p>
        </div>
        <a class="btn btn-primary" href="apply.php">Register for a course</a>
    </div>

    <?php foreach ($sessions as $s):
        $full = (int) $s['taken'] >= (int) $s['seats'];
        $pct  = (int) $s['seats'] > 0 ? min(100, round(100 * $s['taken'] / $s['seats'])) : 0;
        $ts   = strtotime($s['starts_at']);
    ?>
        <article class="class-card">
            <header class="class-card-header">
                <div class="class-cal">
                    <span class="m"><?php echo date('M', $ts); ?></span>
                    <span class="d"><?php echo date('j', $ts); ?></span>
                </div>
                <h3><?php echo h($s['title']); ?></h3>
                <div class="class-when">
                    <?php echo date('D, H:i', $ts); ?>
                    <span class="rel"><time datetime="<?php echo h($s['starts_at']); ?>" data-rel></time></span>
                </div>
            </header>
            <div class="class-details">
                <div class="detail"><span class="detail-label">Semester</span><?php echo (int) $s['intake']; ?></div>
                <div class="detail"><span class="detail-label">Where</span><?php echo h($s['location']); ?></div>
                <div class="detail">
                    <span class="detail-label">Seats</span>
                    <span class="seat-bar<?php echo $full ? ' is-full' : ''; ?>"><i style="width:<?php echo $pct; ?>%"></i></span>
                    <span class="seat-text"><?php echo (int) $s['taken']; ?> of <?php echo (int) $s['seats']; ?> taken</span>
                </div>
                <div class="detail"><span class="detail-label">Notes</span><?php echo h($s['notes']); ?></div>
            </div>
            <div class="class-badges">
                <?php if ($full): ?><span class="badge badge-bad">FULL</span><?php else: ?><span class="badge badge-ok">PLACES LEFT</span><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
