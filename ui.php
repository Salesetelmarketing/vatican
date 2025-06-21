<?php
/*REDACTED*/ // Your token hardcoded here
$default_repo = "Salesetelmarketing/test";
$default_branch = "main";
$result = "";
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $desc = $_POST['desc'] ?? 'Test push';
    $fname = trim($_POST['fname'] ?? '');
    $content = $_POST['content'] ?? '';
    $public = isset($_POST['public']);
    $to_repo = isset($_POST['to_repo']);
    $repo = $_POST['repo'] ?? $default_repo;
    $branch = $_POST['branch'] ?? $default_branch;
    $commit_message = $_POST['commit'] ?? "Push from push UI";

    // Auto-assign filename if blank
    if (!$fname) {
        $line = '';
        foreach (explode("\n",$content) as $l) {
            if (trim($l)) { $line = trim($l); break; }
        }
        if (preg_match('/<\?php/', $line)) $fname = 'snippet_'.date('Ymd_His').'.php';
        elseif (preg_match('/<html|<div|<span|<h\d/i', $line)) $fname = 'snippet_'.date('Ymd_His').'.html';
        elseif (preg_match('/^#include|int main|printf|scanf/', $line)) $fname = 'snippet_'.date('Ymd_His').'.c';
        elseif (preg_match('/^\s*def |^\s*print\(/', $line)) $fname = 'snippet_'.date('Ymd_His').'.py';
        elseif (preg_match('/^\s*function |console\.log|let |var |const /', $line)) $fname = 'snippet_'.date('Ymd_His').'.js';
        else $fname = 'snippet_'.date('Ymd_His').'.txt';
    }

    if (!$token) {
        $result = "<b style='color:#f44'>❌ No GitHub token set.</b>";
    } elseif ($content) {
        if ($to_repo) {
            // Push to GitHub repo
            $b64 = base64_encode($content);
            $url = "https://api.github.com/repos/$repo/contents/$fname?ref=$branch";
            $h = [
                "Authorization: token $token",
                "User-Agent: push-ui",
                "Accept: application/vnd.github.v3+json"
            ];
            $meta = @file_get_contents($url, false, stream_context_create(["http"=>["header"=>$h]]));
            $sha = "";
            if ($meta) {
                $j = json_decode($meta,true);
                if (isset($j['sha'])) $sha = $j['sha'];
            }
            $body = [
                "message" => $commit_message,
                "branch" => $branch,
                "content" => $b64
            ];
            if ($sha) $body["sha"] = $sha;
            $ch = curl_init("https://api.github.com/repos/$repo/contents/$fname");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code>=200 && $code<300) {
                $rj = json_decode($response,true);
                $url = $rj["content"]["html_url"] ?? "";
                $result = "✅ <b>File pushed to GitHub:</b> <a href='$url' target='_blank'>$fname</a>";
            } else {
                $result = "❌ Failed to push to repo.<br><pre>".htmlspecialchars($response)."</pre>";
            }
        } else {
            // Push to Gist
            $body = json_encode([
                "description"=>$desc,
                "public"=>$public,
                "files"=>[$fname=>["content"=>$content]]
            ]);
            $ch = curl_init("https://api.github.com/gists");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: token $token",
                "User-Agent: push-ui",
                "Accept: application/vnd.github.v3+json"
            ]);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code>=200 && $code<300) {
                $res = json_decode($response,true);
                $url = $res["html_url"] ?? "";
                $result = "✅ <b>Gist created as <u>$fname</u>:</b> <a href='$url' target='_blank'>$url</a>";
            } else {
                $result = "❌ Failed to push Gist.<br><pre>".htmlspecialchars($response)."</pre>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Push UI (Gist or Repo)</title>
<style>
body{font-family:sans-serif;background:#181a20;color:#e7e7e7;}
.panel{max-width:500px;margin:50px auto;background:#23242b;padding:24px 26px 18px 26px;border-radius:13px;}
input,textarea,button{font-size:16px;margin:8px 0;width:100%;border-radius:7px;padding:9px;border:1px solid #444;background:#15161a;color:#fff;}
button{background:#55e2b4;color:#222;font-weight:bold;}
label{margin:12px 0 3px 0;display:block;}
.result{margin:18px 0 0 0;background:#263228;padding:10px;border-radius:7px;}
</style>
</head>
<body>
<div class="panel">
<h2>Push UI<br><span style="font-size:15px;opacity:0.7;">Gist or Repo (Salesetelmarketing/test)</span></h2>
<form method="post">
<label>Filename (leave blank for auto)</label>
<input type="text" name="fname" placeholder="Auto if blank">
<label>Description / Commit Message</label>
<input type="text" name="desc" value="Test push" required>
<label>Code / Content</label>
<textarea name="content" rows="8" required>Hello, GitHub! This is a test.</textarea>
<label><input type="checkbox" name="public" checked> Public Gist</label>
<label><input type="checkbox" name="to_repo"> Push to GitHub Repo instead of Gist</label>
<div id="repoopts" style="display:none;">
    <label>Repo (owner/repo)</label>
    <input type="text" name="repo" value="<?=htmlspecialchars($default_repo)?>">
    <label>Branch</label>
    <input type="text" name="branch" value="<?=htmlspecialchars($default_branch)?>">
    <label>Commit Message</label>
    <input type="text" name="commit" value="Push from push UI">
</div>
<button type="submit">Push</button>
</form>
<?php if($result): ?><div class="result"><?=$result?></div><?php endif; ?>
</div>
<script>
document.querySelector('input[name="to_repo"]').onchange = function(){
    document.getElementById('repoopts').style.display = this.checked ? '' : 'none';
};
</script>
</body>
</html>