<?php
/* The rest of the site, using the site.
 *
 * A stolen-password page that stays empty proves nothing. Real members log
 * in all day, so this does too: every few seconds it signs in as a random
 * member against each sandbox that exists, using that member's real
 * password from the seed.
 *
 * While the login page is honest, none of this is visible to anyone. The
 * moment a player replaces it with one that writes passwords down, the
 * same ordinary traffic starts landing in their drop site. Nothing about
 * the attack changes - only who is watching. */

const ROOT   = '/var/www/html/i';
const CYCLE  = 3;        // seconds between rounds
const ACTIVE = 7200;     // ignore sandboxes untouched for two hours
const BUSY   = 60;       // most sandboxes served in one round

/* Plausible origins, so the drop site reads like a campus and not like a
 * loopback interface. */
$NETS = ['10.21.4.', '10.21.5.', '172.20.8.', '192.168.42.'];

function members()
{
    $c = @new mysqli('127.0.0.1', 'root', 'root', 'uam_db');
    if (!$c || $c->connect_error) return [];
    $out = [];
    $r = $c->query("SELECT username, password FROM users
                     WHERE is_admin = 0 AND password <> '' AND username <> 'ahmad.faiz'");
    while ($r && $row = $r->fetch_assoc()) $out[] = $row;
    $c->close();
    return $out;
}

function sign_in($sid, $who, $ip)
{
    $body = http_build_query(['username' => $who['username'], 'password' => $who['password']]);
    $ctx  = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\n"
                         . "X-Forwarded-For: $ip\r\n"
                         . "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
        'content'       => $body,
        'timeout'       => 4,
        'ignore_errors' => true,

        /* A successful sign-in answers 302 to /index.php, and PHP's HTTP
         * wrapper follows redirects unless told not to. Following it means
         * a cookieless request to the front page, which mints a brand new
         * sandbox - which then gets its own login traffic, which mints
         * more. Do not follow it. */
        'follow_location' => 0,
        'max_redirects'   => 0,
    ]]);
    @file_get_contents("http://127.0.0.1/i/$sid/login.php", false, $ctx);
}

$pool = members();
$n = 0;

while (true) {
    if ($n++ % 60 === 0) {
        $fresh = members();
        if ($fresh) $pool = $fresh;
    }

    if ($pool) {
        $live = [];
        foreach (glob(ROOT . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (!is_file($dir . '/login.php'))           continue;
            if (time() - (int) @filemtime($dir) > ACTIVE) continue;
            $live[] = $dir;
        }

        /* A room is forty browsers. If something ever leaves far more than
         * that behind, serve a sample rather than falling so far behind
         * that the people actually watching see nothing arrive. */
        if (count($live) > BUSY) {
            shuffle($live);
            $live = array_slice($live, 0, BUSY);
        }

        foreach ($live as $dir) {
            $ip = $NETS[array_rand($NETS)] . random_int(2, 250);
            sign_in(basename($dir), $pool[array_rand($pool)], $ip);
        }
    }

    sleep(CYCLE);
}
