<?php
$msg = "";
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $fname = trim($_POST['fname'] ?? 'bigtest.txt');
    $lines = max(1, intval($_POST['lines'] ?? 10000));
    $prefix = trim($_POST['prefix'] ?? 'This is line');
    $f = fopen($fname, "w");
    fwrite($f, "Big file test for gist automation.\n");
    for ($i=1; $i<=$lines; $i++) fwrite($f, "$prefix $i\n");
    fclose($f);
    $msg = "<b style='color:#43e254'>✅ $fname created with $lines lines.</b>";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Big Test File Generator UI</title>
<style>
body{font-family:sans-serif;background:#181a20;color:#e7e7e7;}
.panel{max-width:430px;margin:60px auto;background:#23242b;padding:24px 26px 18px 26px;border-radius:13px;}
input,button{font-size:16px;margin:8px 0;width:100%;border-radius:7px;padding:9px;border:1px solid #444;background:#15161a;color:#fff;}
button{background:#55e2b4;color:#222;font-weight:bold;}
label{margin:12px 0 3px 0;display:block;}
.result{margin:18px 0 0 0;background:#263228;padding:10px;border-radius:7px;}
</style>
</head>
<body>
<div class="panel">
<h2>Big Test File Generator</h2>
<form method="post">
<label>Filename</label>
<input type="text" name="fname" value="bigtest.txt" required>
<label>Number of lines</label>
<input type="number" name="lines" value="10000" min="1" max="1000000" required>
<label>Line prefix</label>
<input type="text" name="prefix" value="This is line" required>
<button type="submit">Generate File</button>
</form>
<?php if($msg): ?><div class="result"><?=$msg?></div><?php endif; ?>
<?php if(isset($fname) && file_exists($fname)): ?>
    <div class="result" style="color:#8ef;font-size:14px;">
        <b><?=htmlspecialchars($fname)?></b> size: <?=number_format(filesize($fname))?> bytes
    </div>
<?php endif; ?>
</div>
</body>
</html>