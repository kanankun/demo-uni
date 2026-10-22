<?php
include 'db.php';
$page_title = 'Contact';
$sent = isset($_GET['sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') { header("Location: contact.php?sent=1"); exit; }
include 'header.php';
?>
<div class="content">
<div class="container" style="max-width:560px;">
    <h2>Contact</h2>
    <?php if ($sent): ?>
        <div class="alert alert-success">Thanks. Someone from the Registrar's Office will get back to you.</div>
    <?php endif; ?>
    <p>Questions about course registration, results, fees, or your account.</p>
    <form method="post">
        <div class="field"><label for="n">Your name</label><input type="text" id="n" name="name" required></div>
        <div class="field"><label for="e">Email</label><input type="email" id="e" name="email" required></div>
        <div class="field"><label for="m">Message</label><textarea id="m" name="message" rows="5" required maxlength="1000" data-counter></textarea></div>
        <button type="submit">Send message</button>
    </form>
    <div class="form-links"><p>Or email <strong>registrar@uam.edu.my</strong>.</p></div>
</div>
</div>
<div class="wave-footer"></div>
</body>
</html>
