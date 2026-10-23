<?php
/* Per-visitor sandbox.
 *
 * Forty students share one container. A webshell, a defaced homepage and a
 * log of stolen passwords are all state, and state one player can see is
 * state another player no longer gets to discover. So every browser gets a
 * directory of its own under i/, and everything a player creates lands in
 * it: their uploads, their shell, their defacement, their captures.
 *
 * None of this is the vulnerability. It is the classroom, not the lesson.
 */

const SS_SID_COOKIE = 'ss_sid';

/* This browser's sandbox id. Minted on first sight, kept for a week. */
function ss_sid()
{
    static $sid = null;
    if ($sid !== null) return $sid;

    /* A page that lives inside a sandbox belongs to that sandbox, whoever
     * is asking. The sign-in page is the one that matters: members arrive
     * at it without a cookie of their own, and their passwords must be
     * written down in the sandbox whose login page they used - not in a
     * new one minted for each visitor. */
    if (preg_match('#^/i/([a-f0-9]{10})/#', $_SERVER['SCRIPT_NAME'] ?? '', $m)) {
        return $sid = $m[1];
    }

    $raw = $_COOKIE[SS_SID_COOKIE] ?? '';
    if (preg_match('/^[a-f0-9]{10}$/', $raw)) {
        $sid = $raw;
    } else {
        $sid = bin2hex(random_bytes(5));
        setcookie(SS_SID_COOKIE, $sid, time() + 86400 * 7, '/');
        $_COOKIE[SS_SID_COOKIE] = $sid;
    }
    return $sid;
}

function ss_inst_dir() { return __DIR__ . '/i/' . ss_sid(); }
function ss_inst_url() { return 'i/' . ss_sid(); }

/* Build the sandbox on first sight and give it a working login page.
 *
 * login.php is a real file sitting in a writable directory, which is the
 * whole point: the upload form drops files into this same directory, so a
 * file called login.php replaces the page every login goes through. That is
 * the second half of the demo, and it is not a hook added for the lesson -
 * it is what happens when uploads land somewhere the application executes.
 */
function ss_inst_init()
{
    $dir = ss_inst_dir();
    if (is_dir($dir)) { @touch($dir); return; }

    @mkdir($dir, 0777, true);
    @copy(__DIR__ . '/login.tpl.php', $dir . '/login.php');
    @touch($dir . '/captured.log');
    @chmod($dir . '/captured.log', 0666);
}

/* Where this browser's login page lives. */
function ss_login_url() { return ss_inst_url() . '/login.php'; }

/* The defaced homepage, if anyone has written one. One shared file, not
 * per-sandbox: a real defacement is a single site everyone sees broken,
 * not forty private copies, so this deliberately breaks the per-visitor
 * isolation everything else in this file keeps. */
function ss_deface_file() { return __DIR__ . '/i/_global_home.html'; }

/* Throw the sandbox away and start clean. */
function ss_inst_reset()
{
    $dir = ss_inst_dir();
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . '/' . $f;
            is_dir($p) ? @ss_rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
    ss_inst_init();
}

/* Sweep up after crawlers and abandoned browsers. Runs rarely and only
 * removes sandboxes nothing has touched for a day. */
function ss_inst_reap()
{
    if (random_int(1, 60) !== 1) return;
    $cut = time() - 86400;
    foreach (glob(__DIR__ . '/i/*', GLOB_ONLYDIR) ?: [] as $dir) {
        if ((int) @filemtime($dir) < $cut) @ss_rrmdir($dir);
    }
}

function ss_rrmdir($dir)
{
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . '/' . $f;
        is_dir($p) ? ss_rrmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}
