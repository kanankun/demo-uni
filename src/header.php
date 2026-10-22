<?php
/* Shared page shell.
 *   $page_title - "<title> | University A"
 *   $page_css   - extra stylesheets, relative to the web root
 * Include db.php first; this file closes nothing, each page ends with
 * </body></html> itself.
 */
require_once __DIR__ . '/lib.php';

$ss_title = (isset($page_title) && $page_title !== '') ? h($page_title) . ' | University A' : 'University A';
$ss_v = function ($f) { return @filemtime(__DIR__ . '/' . $f) ?: 1; };

/* The site footer is appended just before </body> on every page. */
ob_start(function ($html) {
    $pos = strripos($html, '</body>');
    if ($pos === false || strpos($html, 'class="site-footer"') !== false) return $html;
    $footer = '<footer class="site-footer">'
            . '<span>&copy; ' . date('Y') . ' University A</span>'
            . '<nav aria-label="Footer">'
            . '<a href="about.php">About</a><a href="schedule.php">Timetable</a>'
            . '<a href="terms.php">Terms</a><a href="privacy.php">Privacy</a>'
            . '<a href="contact.php">Contact</a>'
            . '</nav></footer>';
    return substr($html, 0, $pos) . $footer . substr($html, $pos);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="/">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000000">
<?php if (ss_uid()): ?>
    <meta name="csrf-token" content="<?php echo h(ss_csrf()); ?>">
<?php endif; ?>
    <title><?php echo $ss_title; ?></title>
    <link rel="icon" type="image/svg+xml" href="images/icon.svg">
    <link rel="stylesheet" href="style.css?v=<?php echo $ss_v('style.css'); ?>">
<?php foreach ((isset($page_css) ? (array) $page_css : []) as $ss_css): ?>
    <link rel="stylesheet" href="<?php echo h($ss_css); ?>?v=<?php echo $ss_v($ss_css); ?>">
<?php endforeach; ?>
    <script src="app.js?v=<?php echo $ss_v('app.js'); ?>"></script>
</head>
<body>
<?php include __DIR__ . '/guide_boot.php'; ?>
    <a class="skip-link" href="#main">Skip to content</a>
<?php include __DIR__ . '/nav.php'; ?>
<?php
/* Flash messages set on the previous request are handed to app.js as toasts. */
if (!empty($_SESSION['ss_flash'])) {
    echo '<script type="application/json" id="ss-flash">' . json_encode($_SESSION['ss_flash'], JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
    unset($_SESSION['ss_flash']);
}
?>
