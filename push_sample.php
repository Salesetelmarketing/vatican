<?php
/*REDACTED*/
$repo = "Salesetelmarketing/test";
$branch = "main";
$fname = "sample_test.txt";
$content = "This is a test file for the Salesetelmarketing/test repository.\n\nCreated via ChatGPT demo.\n\nDate: ".date("Y-m-d H:i:s")."\n\n---\nHello, GitHub! This is your sample test file.\n";
$b64 = base64_encode($content);

// Check if file exists in repo (to get SHA)
$u = "https://api.github.com/repos/$repo/contents/$fname?ref=$branch";
$h = [
    "Authorization: token $token",
    "User-Agent: push-file-ui"
];
$meta = @file_get_contents($u, false, stream_context_create(["http"=>["header"=>$h]]));
$sha = "";
if ($meta) {
    $j = json_decode($meta,true);
    if (isset($j['sha'])) $sha = $j['sha'];
}

$body = [
    "message" => "Add sample_test.txt",
    "branch" => $branch,
    "content" => $b64
];
if ($sha) $body["sha"] = $sha;

$ch = curl_init("https://api.github.com/repos/$repo/contents/$fname");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $token",
    "User-Agent: push-file-ui",
    "Accept: application/vnd.github.v3+json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code>=200 && $code<300) {
    echo "✅ $fname pushed to $repo/$branch!";
} else {
    echo "❌ Failed to push. Response:<br><pre>" . htmlspecialchars($response) . "</pre>";
}
?>