<?php
// --- CONFIGURATION ---
$base = __DIR__;
$ignore = ['.git', '.', '..', basename(__FILE__), 'vendor', 'node_modules', 'storage', 'uploads'];

// --- Extensions to scan ---
$exts = ['php', 'json', 'env', 'yml', 'yaml', 'ini', 'conf', 'js'];

// --- Secret patterns (add more as needed) ---
$patterns = [
    // GitHub tokens
    '/ghp_[A-Za-z0-9]{30,}/',
    // AWS Access Key ID
    '/AKIA[0-9A-Z]{16}/',
    // AWS Secret Key (base64 40+ chars)
    '/[A-Za-z0-9\/+=]{40,}/',
    // Generic key, token, secret in key-value (php, json, yml, env, etc)
    '/(?<=\b(token|api[_-]?key|secret|aws[_-]?secret|access[_-]?token|auth[_-]?token|password|client[_-]?secret)["\'\s:=]*[\'"]?([A-Za-z0-9_\-\/+=]{24,})[\'"]?/i',
    // Any long string that looks like a key
    '/[\'"][A-Za-z0-9_\-\/+=]{32,}[\'"]/',
];

// --- Recursively find all target files ---
function find_all_files($dir, $ignore, $exts) {
    $out = [];
    foreach (scandir($dir) as $f) {
        if (in_array($f, $ignore)) continue;
        $full = "$dir/$f";
        if (is_dir($full)) {
            $out = array_merge($out, find_all_files($full, $ignore, $exts));
        } else {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $exts)) $out[] = $full;
        }
    }
    return $out;
}

$all_files = find_all_files($base, $ignore, $exts);

echo "<pre>\n";
foreach ($all_files as $file) {
    $orig = file_get_contents($file);
    $lines = explode("\n", $orig);
    $changed = false;
    foreach ($lines as $i => $line) {
        $orig_line = $line;
        foreach ($patterns as $p) {
            if (preg_match($p, $line)) {
                $line = preg_replace($p, '/*REDACTED*/', $line);
            }
        }
        if ($line !== $orig_line) {
            $changed = true;
            echo "Redacted in $file, line ".($i+1).":\n   $orig_line\n";
        }
        $lines[$i] = $line;
    }
    if ($changed) {
        copy($file, $file.".bak");
        file_put_contents($file, implode("\n", $lines));
        echo "=> $file sanitized and backup saved as $file.bak\n";
    }
}
echo "\nDone.\n</pre>";
?>