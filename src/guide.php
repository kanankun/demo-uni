<?php
/* Guided walkthrough drawer. Lab harness, not part of UAM.
 *
 * A lab ships overrides/src/guide_data.php, which sets $GUIDE. This file
 * renders it as a panel pinned to the right of every page: an intro, then
 * numbered steps a player opens one at a time. Each step can carry
 * something to paste and a note on what it should produce, so the player
 * sees the payload being built rather than handed over whole.
 *
 * The steps are <details> elements, so they open and close without
 * JavaScript. With it, they behave as an accordion: one step open at a
 * time, and the open one is remembered across page loads, because every
 * payload here is submitted through a form that reloads the page. */
if (!file_exists(__DIR__ . '/guide_data.php')) return;

$GUIDE = null;
include __DIR__ . '/guide_data.php';
if (empty($GUIDE['steps'])) return;

/* The walkthroughs are written against http://localhost:81xx because that is
 * where they are authored. A player reaching this lab on a droplet, a LAN
 * address or behind a platform's port mapping needs the address they are
 * actually using, so rewrite it to this request's own origin. */
/* TLS terminates at the ingress in front, so $_SERVER['HTTPS'] is empty
   even when the player is on https; the proxy says so in a header instead.
   Trusting it is safe here because the container is bound to loopback and
   the only route to it is through that proxy. */
$ss_scheme = 'http';
if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
    $ss_scheme = 'https';
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $ss_scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https'
               ? 'https' : 'http';
}
$ss_hostport = $_SERVER['HTTP_HOST'] ?? 'localhost';
$ss_origin   = $ss_scheme . '://' . $ss_hostport;

$ss_host = function ($text) use ($ss_origin, $ss_hostport) {
    /* The port is what tells the two apart. Every address a player types
       carries one (localhost:8122); the only port-less localhost in these
       walkthroughs is a URL the server fetches from inside its own
       container, which must be left exactly as it is. */
    $text = preg_replace('#https?://localhost:\d+#i', $ss_origin, (string) $text);

    /* A raw request carries the address in a Host header, where there is no
       scheme to match on. */
    return preg_replace('#^(Host:[ \t]*)localhost(:\d+)?$#mi', '${1}' . $ss_hostport, $text);
};
?>
<button type="button" class="guide-tab" id="guideTab" aria-controls="guidePanel" aria-expanded="false">
    <span class="guide-tab-text">GUIDE</span>
</button>

<aside class="guide-panel" id="guidePanel" aria-label="Guided walkthrough">
    <div class="guide-head">
        <div>
            <strong>Guided walkthrough</strong>
            <span><?php echo h($LAB['name'] . ' - ' . strtoupper($LAB['level'])); ?></span>
        </div>
        <button type="button" class="guide-close" id="guideClose" aria-label="Close the guide">&#10005;</button>
    </div>

    <div class="guide-body">
        <?php if (!empty($GUIDE['intro'])): ?>
            <p class="guide-intro"><?php echo $ss_host($GUIDE['intro']); ?></p>
        <?php endif; ?>

        <?php /* Only the labs that need an account a member cannot register
                 declare these. Repeating them here saves a trip to the
                 objective page in the middle of a walkthrough. */ ?>
        <?php if (!empty($LAB['creds'])): ?>
            <div class="guide-creds">
                <strong>Sign in first</strong>
                <?php foreach ($LAB['creds'] as $who => $pair): ?>
                    <?php
                    /* lab.php states these as "user / pass" on one line, which
                       is right for a table and wrong to read mid-walkthrough. */
                    $ss_pair = array_map('trim', explode('/', (string) $pair, 2));
                    $ss_user = $ss_pair[0] ?? '';
                    $ss_pass = $ss_pair[1] ?? '';
                    ?>
                    <?php if (count($LAB['creds']) > 1): ?>
                        <em><?php echo h($who); ?></em>
                    <?php endif; ?>
                    <?php if ($ss_pass === ''): ?>
                        <p class="guide-cred-raw"><?php echo h($pair); ?></p>
                    <?php else: ?>
                        <dl class="guide-cred">
                            <dt>username</dt><dd><?php echo h($ss_user); ?></dd>
                            <dt>password</dt><dd><?php echo h($ss_pass); ?></dd>
                        </dl>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php foreach ($GUIDE['steps'] as $i => $st): ?>
            <details class="guide-step" name="guide-step" data-step="<?php echo (int) $i; ?>"<?php echo $i === 0 ? ' open' : ''; ?>>
                <summary>
                    <i><?php echo $i + 1; ?></i>
                    <span><?php echo h($st['t']); ?></span>
                </summary>
                <div class="guide-step-body">
                    <?php /* Say so before the payload when the browser is not
                             enough, rather than letting a player paste a raw
                             request body into a form field and conclude the
                             lab is broken. */ ?>
                    <?php if (!empty($st['tool'])): ?>
                        <p class="guide-tool"><span>Needs</span><?php echo $ss_host($st['tool']); ?></p>
                    <?php endif; ?>

                    <?php echo $ss_host($st['b']); ?>

                    <?php foreach ((array) ($st['try'] ?? []) as $label => $payload): $payload = $ss_host($payload); ?>
                        <?php /* A label starting with -- is a note to read between two
                                 commands, not something to copy: "open the editor",
                                 then what to do in it, then "run it". Keys must differ,
                                 so --1, --2 and so on all qualify. */ ?>
                        <?php if (is_string($label) && strpos($label, '--') === 0): ?>
                            <p class="guide-mid"><?php echo $payload; ?></p>
                        <?php else: ?>
                            <div class="guide-try">
                                <?php if (!is_int($label)): ?><em><?php echo h($label); ?></em><?php endif; ?>
                                <pre><code><?php echo h($payload); ?></code></pre>
                                <button type="button" class="guide-copy" data-copy="<?php echo h($payload); ?>">COPY</button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php /* One action, more than one way to send it. Only the
                             chosen one is on screen, so a step does not grow a
                             raw request and a curl command stacked on top of
                             each other. The choice is global and remembered. */ ?>
                    <?php if (!empty($st['send'])): $ss_first = true; ?>
                        <div class="guide-send">
                            <div class="guide-send-tabs">
                                <?php foreach (array_keys($st['send']) as $ss_i => $ss_name): ?>
                                    <button type="button" class="guide-send-tab<?php echo $ss_i === 0 ? ' is-on' : ''; ?>"
                                            data-send="<?php echo h($ss_name); ?>"><?php echo h($ss_name); ?></button>
                                <?php endforeach; ?>
                            </div>
                            <?php foreach ($st['send'] as $ss_name => $ss_payload): $ss_payload = $ss_host($ss_payload); ?>
                                <div class="guide-send-pane<?php echo $ss_first ? ' is-on' : ''; ?>"
                                     data-send="<?php echo h($ss_name); ?>">
                                    <pre><code><?php echo h($ss_payload); ?></code></pre>
                                    <button type="button" class="guide-copy" data-copy="<?php echo h($ss_payload); ?>">COPY</button>
                                </div>
                            <?php $ss_first = false; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($st['see'])): ?>
                        <p class="guide-see"><strong>What you should see</strong><?php echo $ss_host($st['see']); ?></p>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>

        <?php if (!empty($GUIDE['after'])): ?>
            <div class="guide-after">
                <strong>Why it worked</strong>
                <?php echo $ss_host($GUIDE['after']); ?>
            </div>
        <?php endif; ?>
    </div>
</aside>

<script>
/* Same reasoning as the drawer class in header.php: the markup opens step
   one, so restoring the remembered step from DOMContentLoaded would show
   step one first and snap. Done here, inline and immediately after the
   panel, it is settled before anything is painted.

   name= is dropped at the same time. It is the no-JavaScript accordion,
   and while it is set the browser closes the other steps itself, with no
   animation - which is what app.js is here to do smoothly. */
(function () {
    var steps = document.querySelectorAll('.guide-step');
    if (!steps.length) return;
    var want = null;
    try { want = localStorage.getItem('ss_guide_step'); } catch (e) { return; }
    var i = want === null ? 0 : parseInt(want, 10);
    for (var n = 0; n < steps.length; n++) {
        steps[n].removeAttribute('name');
        steps[n].open = (n === i);
    }

    /* and which of Burp / curl is on screen, for the same reason: chosen on
       the last page, so it must not visibly switch on this one */
    var client = null;
    try { client = localStorage.getItem('ss_guide_client'); } catch (e) { return; }
    if (!client) return;
    var groups = document.querySelectorAll('.guide-send');
    for (var g = 0; g < groups.length; g++) {
        var want = groups[g].querySelector('.guide-send-pane[data-send="' + client + '"]');
        if (!want) continue;           /* this step does not offer that one */
        var els = groups[g].querySelectorAll('[data-send]');
        for (var e2 = 0; e2 < els.length; e2++) {
            els[e2].classList.toggle('is-on', els[e2].getAttribute('data-send') === client);
        }
    }
})();
</script>
