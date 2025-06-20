<?php
// --- CONFIG: set your root directory for security ---
$base_dir = __DIR__;

// --- AJAX: Directory tree, Gist fetch, Upload ---
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'tree') {
        function list_dir($dir, $rel = "") {
            $out = [];
            foreach (scandir($dir) as $f) {
                if ($f=='.'||$f=='..') continue;
                $path = "$dir/$f";
                $relpath = $rel=="" ? $f : "$rel/$f";
                if (is_dir($path)) {
                    $out[] = ['type'=>'dir', 'name'=>$f, 'rel'=>$relpath, 'children'=>list_dir($path, $relpath)];
                } else {
                    $out[] = ['type'=>'file', 'name'=>$f, 'rel'=>$relpath];
                }
            }
            return $out;
        }
        echo json_encode(list_dir($base_dir, ""));
        exit;
    }
    if ($_POST['action']==='gistfetch' && isset($_POST['gistid'])) {
        $gistid = preg_replace('~[^a-zA-Z0-9]+~','',$_POST['gistid']);
        $url = "https://api.github.com/gists/$gistid";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: GistToTreeAuto",
            "Accept: application/vnd.github.v3+json"
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($resp,1);
        $files = [];
        if (isset($data['files'])) {
            foreach ($data['files'] as $fname=>$finfo) {
                $files[$fname] = $finfo['content'];
            }
        }
        echo json_encode($files);
        exit;
    }
    if ($_POST['action']==='upload' && isset($_POST['files'])) {
        $filelist = json_decode($_POST['files'],1);
        $created = [];
        foreach ($filelist as $name=>$content) {
            $fullpath = $base_dir.'/'.$name;
            $dir = dirname($fullpath);
            if (!is_dir($dir)) mkdir($dir,0777,true);
            file_put_contents($fullpath, $content);
            $created[] = $fullpath;
        }
        echo json_encode(['ok'=>count($created),'files'=>$created]);
        exit;
    }
    exit;
}

// --- Download handler ---
if (isset($_GET['dl']) && $_GET['dl']) {
    $f = realpath($base_dir.'/'.$_GET['dl']);
    if (!$f || strpos($f, realpath($base_dir)) !== 0 || !is_file($f)) die('File not found.');
    header("Content-Disposition: attachment; filename=\"".basename($f)."\"");
    header("Content-Type: application/octet-stream");
    readfile($f);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Auto Gist-to-Server Tree Sync</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { background:#181a20; color:#e7e7e7; font-family:sans-serif; }
.flex {display:flex;gap:38px;}
@media (max-width:900px){.flex{flex-direction:column;gap:14px;}}
.panel {background:#23242b;max-width:540px;min-width:290px;margin:24px 0;padding:22px 24px 18px 24px;border-radius:13px;}
#tree { margin:18px 0 0 0; }
.folder { cursor:pointer; font-weight:bold; color:#8ef;}
.folder.collapsed:before { content:"▶ "; color:#bbb;}
.folder.expanded:before { content:"▼ "; color:#bbb;}
.file { margin-left:18px; color:#fff; cursor:pointer;}
a.dl { color:#b2e; text-decoration:underline;}
.result{margin:20px 0 0 0; background:#263228;padding:10px;border-radius:7px;}
ul {list-style:none;padding-left:12px;}
ul ul{margin-left:14px;}
.gistfiletree{margin:12px 0 18px 0;background:#16191d;padding:13px;border-radius:8px;}
</style>
</head>
<body>
<div class="flex">
<div class="panel" style="flex:1;">
<h2>Auto Gist-to-Server Tree Sync</h2>
<form onsubmit="return false;">
<label>Gist URL or ID:</label>
<input type="text" id="gistid" placeholder="e.g. https://gist.github.com/username/abcd1234..." required>
<button onclick="fetchGist()">Fetch Gist & Show Files/Tree</button>
</form>
<div id="gistfiles"></div>
<div id="uploadresult"></div>
</div>
<div class="panel" style="flex:1;">
<h2>Server File Tree</h2>
<div id="tree"></div>
<div id="fileview" style="margin:20px 0 0 0;"></div>
</div>
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
                    let d = item.rel.replace(/\/+$/,'');
                    fetch("",{method:"POST",body:new URLSearchParams({action:'tree', node:d})})
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
                <a href="?dl=${encodeURIComponent(item.rel)}" class="dl" title="Download">[Download]</a>`;
            li.querySelector('.file').onclick = function(){
                fetch("?dl="+encodeURIComponent(item.rel))
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
    fetch("",{method:"POST",body:new URLSearchParams({action:'tree'})})
    .then(r=>r.json())
    .then(js=>{
        let t = renderTree(js, null);
        let div = document.getElementById('tree');
        div.innerHTML = "";
        div.appendChild(t);
    });
}
function renderGistFileTree(files) {
    // Builds a folder/file preview from flat gist file list
    let tree = {};
    Object.keys(files).forEach(fname=>{
        let parts = fname.split('/');
        let node = tree;
        for (let i=0; i<parts.length; i++) {
            let part = parts[i];
            if (i == parts.length-1) {
                if (!node.files) node.files = [];
                node.files.push(part);
            } else {
                if (!node.dirs) node.dirs = {};
                if (!node.dirs[part]) node.dirs[part] = {};
                node = node.dirs[part];
            }
        }
    });
    function recur(t, prefix) {
        let out = "";
        if (t.dirs) for (let d in t.dirs) out += `<li><b>${escapeHTML(d)}/</b><ul>${recur(t.dirs[d], prefix+d+'/')}</ul></li>`;
        if (t.files) for (let f of t.files) out += `<li>${escapeHTML(f)}</li>`;
        return out;
    }
    return `<div class="gistfiletree"><b>Will be created:</b><ul>${recur(tree,"")}</ul></div>`;
}
function fetchGist() {
    let g = document.getElementById('gistid').value.trim();
    let id = g.match(/[a-f0-9]{8,}/i) ? g.match(/[a-f0-9]{8,}/i)[0] : "";
    if(!id) { alert("Invalid Gist URL or ID."); return false; }
    document.getElementById("gistfiles").innerHTML = "Fetching Gist...";
    fetch("",{method:"POST", body:new URLSearchParams({action:'gistfetch', gistid:id})})
    .then(r=>r.json())
    .then(js=>{
        let files = Object.keys(js);
        if(!files.length) { document.getElementById("gistfiles").innerHTML = "No files in gist!"; return; }
        let treeview = renderGistFileTree(js);
        document.getElementById("gistfiles").innerHTML =
            `${treeview}
            <button onclick="uploadAll()">Upload all files (folders auto-match)</button>`;
        window.gistFiles = js;
    });
}
function uploadAll() {
    fetch("", {
        method:"POST",
        body:new URLSearchParams({action:'upload', files:JSON.stringify(window.gistFiles)})
    })
    .then(r=>r.json())
    .then(js=>{
        if(js.ok) document.getElementById('uploadresult').innerHTML =
            "✅ Uploaded "+js.ok+" files. The server file tree is now in sync!";
        else document.getElementById('uploadresult').innerHTML = "❌ "+(js.err||'Unknown error');
        loadRoot();
    });
}
loadRoot();
</script>
</body>
</html>