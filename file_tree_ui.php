<?php
// --- CONFIG: Set your base/root directory for security ---
$base = __DIR__; // Or specify any subdir for security, e.g. __DIR__.'/myfolder'

// --- AJAX for tree ---
if (isset($_GET['tree'])) {
    $root = realpath($base);
    $node = isset($_GET['node']) ? $_GET['node'] : '';
    $dir = realpath($base . '/' . $node);
    if (!$dir || strpos($dir, $root) !== 0) exit('[]');
    $out = [];
    foreach (scandir($dir) as $f) {
        if ($f=='.'||$f=='..') continue;
        $full = $dir.'/'.$f;
        if (is_dir($full)) {
            $out[] = ['type'=>'dir','name'=>$f,'path'=>$node==''?$f:$node.'/'.$f];
        } else {
            $out[] = ['type'=>'file','name'=>$f,'path'=>$node==''?$f:$node.'/'.$f];
        }
    }
    echo json_encode($out);
    exit;
}

// --- File download ---
if (isset($_GET['dl']) && $_GET['dl']) {
    $f = realpath($base.'/'.$_GET['dl']);
    if (!$f || strpos($f, realpath($base)) !== 0 || !is_file($f)) die('File not found.');
    header("Content-Disposition: attachment; filename=\"".basename($f)."\"");
    header("Content-Type: application/octet-stream");
    readfile($f);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>File Manager Tree UI</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:sans-serif;background:#181a20;color:#e7e7e7;}
.panel{max-width:540px;margin:40px auto;background:#23242b;padding:24px 24px 18px 24px;border-radius:13px;}
#tree{margin:18px 0 0 0;}
.folder{cursor:pointer;font-weight:bold;color:#8ef;}
.folder.collapsed:before{content:"▶ ";color:#bbb;}
.folder.expanded:before{content:"▼ ";color:#bbb;}
.file{margin-left:18px;color:#fff;cursor:pointer;}
a.dl{color:#b2e;color:#7de;text-decoration:underline;}
</style>
</head>
<body>
<div class="panel">
<h2>File Management Tree</h2>
<div id="tree"></div>
<div id="fileview" style="margin:20px 0 0 0;"></div>
</div>
<script>
function escapeHTML(x){return x.replace(/[<>"'&]/g, c=>({'<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','&':'&amp;'}[c]));}
function renderTree(node, parent) {
    let ul = document.createElement('ul');
    ul.style.listStyle = "none";
    ul.style.paddingLeft = "16px";
    node.forEach(item=>{
        let li = document.createElement('li');
        if (item.type == 'dir') {
            li.innerHTML = `<span class="folder collapsed">${escapeHTML(item.name)}/</span>`;
            li.querySelector('.folder').onclick = function(ev){
                ev.stopPropagation();
                let span = this;
                if (span.classList.contains('collapsed')) {
                    span.classList.remove('collapsed'); span.classList.add('expanded');
                    fetch("?tree=1&node="+encodeURIComponent(item.path))
                    .then(r=>r.json()).then(children=>{
                        let tree = renderTree(children, li);
                        li.appendChild(tree);
                    });
                } else {
                    span.classList.remove('expanded'); span.classList.add('collapsed');
                    if (li.querySelector('ul')) li.removeChild(li.querySelector('ul'));
                }
            };
        } else {
            li.innerHTML = `<span class="file">${escapeHTML(item.name)}</span>
                <a href="?dl=${encodeURIComponent(item.path)}" class="dl" title="Download">[Download]</a>`;
            li.querySelector('.file').onclick = function(){
                fetch("?dl="+encodeURIComponent(item.path))
                .then(resp=>resp.text())
                .then(txt=>{
                    document.getElementById('fileview').innerHTML =
                        `<h3>${escapeHTML(item.name)}</h3><pre style="max-width:95vw;overflow-x:auto;background:#111;padding:14px;border-radius:8px;">`
                        + escapeHTML(txt) + "</pre>";
                });
            }
        }
        ul.appendChild(li);
    });
    return ul;
}
function loadRoot(){
    fetch("?tree=1")
    .then(r=>r.json())
    .then(js=>{
        let t = renderTree(js, null);
        let div = document.getElementById('tree');
        div.innerHTML = "";
        div.appendChild(t);
    });
}
loadRoot();
</script>
</body>
</html>