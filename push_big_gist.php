<?php
/*REDACTED*/ // your token
$filename = "bigtest.txt"; // your big file
$description = "Big file test Gist for Salesetelmarketing";
$public = true;

if (!is_file($filename)) exit("❌ File not found: $filename");

$content = file_get_contents($filename);

// Gist API payload
$body = json_encode([
    "description" => $description,
    "public" => $public,
    "files" => [
        $filename => [ "content" => $content ]
    ]
]);

$ch = curl_init("https://api.github.com/gists");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: token $token",
    "User-Agent: big-gist-test",
    "Accept: application/vnd.github.v3+json"
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code>=200 && $code<300) {
    $res = json_decode($response,true);
    $url = $res["html_url"] ?? "";
    echo "✅ <b>Big test Gist created:</b> <a href='$url' target='_blank'>$url</a>";
} else {
    echo "❌ Failed to push Gist.<br><pre>".htmlspecialchars($response)."</pre>";
}
?>