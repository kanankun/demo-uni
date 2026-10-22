<?php
/* The strip across the top of every page: what this box is, and the one
 * control a player needs - how to start over. */
global $LAB;
if (empty($LAB)) return;
?>
    <div class="lab-banner">
        <strong>Week 1 Demo</strong>
        <i class="lvl lvl-hard">LIVE</i>
        <span><?php echo h($LAB['goal']); ?></span>
        <a href="reset.php" data-no-progress>RESET</a>
    </div>
