<?php
/* Applied before anything paints, so a drawer that was left open is simply
   already there on the next page instead of sliding in and shoving the
   layout sideways on every click. A CSS transition only runs against a
   previous value, and on first paint there is none.
 *
 * Its own file because two pages open a <body>: header.php and view.php,
 * which builds its own <head> for the blurred background. view.php was
 * missing this and was the one page that still pushed. */
?>
<script>
(function () {
    try {
        if (localStorage.getItem('ss_guide_open') === '1') {
            document.body.className += ' guide-open';
        }
    } catch (e) { /* storage blocked - drawer just starts shut */ }
})();
</script>
