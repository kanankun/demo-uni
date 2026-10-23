<?php
/* Lab banner + site header + navigation. $LAB comes from db.php. */
global $conn, $LAB;

$ss_cur   = basename($_SERVER['SCRIPT_NAME']);
$ss_admin = isset($conn) ? ss_is_admin($conn) : false;

$ss_link = function ($href, $label, $files = null, $class = '') use ($ss_cur) {
    $files  = $files ?: [$href];
    $active = in_array($ss_cur, $files, true);
    $cls    = trim($class . ($active ? ' active' : ''));
    return '<a href="' . $href . '"' . ($cls !== '' ? ' class="' . $cls . '"' : '')
         . ($active ? ' aria-current="page"' : '') . '>' . $label . '</a>';
};
?>
<?php if (file_exists(__DIR__ . '/lab-solve.js')): ?>
    <script src="lab-solve.js?v=<?php echo @filemtime(__DIR__ . '/lab-solve.js') ?: 1; ?>"></script>
<?php endif; ?>
<?php if (empty($ss_banner_done)) include __DIR__ . '/lab_banner.php'; ?>
<?php include __DIR__ . '/guide.php'; ?>
    <header>
        <a href="<?php echo ss_uid() ? 'dashboard.php' : 'index.php'; ?>" class="brand" aria-label="University A Student Portal home">
            <h1>UA</h1>
        </a>

        <button type="button" class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="site-nav">
            <span></span><span></span><span></span>
        </button>

        <nav id="site-nav" aria-label="Main">
            <?php if (ss_uid()): ?>
                <?php echo $ss_link('dashboard.php', 'DASHBOARD'); ?>
                <?php echo $ss_link('announcements.php', 'ANNOUNCEMENTS', ['announcements.php', 'announcement.php', 'post_announcement.php']); ?>
                <?php echo $ss_link('assignments.php', 'ASSIGNMENTS', ['assignments.php', 'post_assignment.php']); ?>
                <?php if ($ss_admin): ?>
                    <?php echo $ss_link('apply.php', 'CLASSES'); ?>
                <?php endif; ?>
                <?php echo $ss_link('schedule.php', 'TIMETABLE'); ?>
                <?php if (!$ss_admin): ?>
                    <?php echo $ss_link('results.php', 'RESULTS'); ?>
                    <?php echo $ss_link('fees.php', 'FEES'); ?>
                <?php endif; ?>
                <?php echo $ss_link('students.php', 'STUDENTS'); ?>
                <?php echo $ss_link('profile.php', 'PROFILE', ['profile.php', 'settings.php']); ?>
                <?php if ($ss_admin): ?>
                    <?php echo $ss_link('admin.php', 'ADMIN'); ?>
                <?php endif; ?>
                <a href="logout.php" data-no-progress>LOGOUT</a>
            <?php endif; ?>
        </nav>
    </header>
