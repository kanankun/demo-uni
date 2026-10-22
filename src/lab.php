<?php
/* Week 1 demo - the University A Student Portal, with two doors left open.
 *
 * The Student Directory's search box concatenates into SQL, and the
 * avatar form writes whatever it is given into a directory the server
 * will execute. One leads to the other: the first hands you the
 * administrator's password, the second is behind the administrator's
 * login. */
$LAB = [
    'n'     => 'DEMO',
    'name'  => 'SQL Injection to Remote Code Execution',
    'level' => 'demo',
    'goal'  => 'read the password table, sign in as the administrator, then get the server running your code',
    'creds' => ['Student (sign in with this first)' => 'ahmad.faiz / student123'],
    'notes' => [
        'There is no sign-up on this portal - sign in with the student account above first. The SQL injection lives in the Student Directory search, behind login, not on the sign-in page.',
        'The Directory search is reachable by any signed-in account, student or staff - nothing admin-only about getting to the search box.',
        'Database errors are printed on the page. Use them.',
        'This site stores passwords as plain text. Most real ones do not, which is a separate argument - see what it costs here first.',
    ],
];

/* Avatars this player uploaded live in their own sandbox and are reachable
 * by URL. The seeded ones still come from the store outside the web root.
 * Uploads being inside the web root is not the bug by itself - it is what
 * turns "no filename check" into code execution. */
function ss_avatar_src($user_id, $avatar)
{
    if (empty($avatar)) return 'images/avatar-default.png';
    if (is_file(ss_inst_dir() . '/' . basename($avatar))) {
        return ss_inst_url() . '/' . rawurlencode(basename($avatar));
    }
    return 'serve.php?type=avatar&id=' . (int) $user_id;
}
