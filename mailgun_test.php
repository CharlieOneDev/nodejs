<?php
// Mailgun 发送测试脚本
// 用法：部署后访问 /mailgun_test.php?to=you@domain 或设置 env MAILGUN_TEST_TO 并访问脚本

function loadEnvIfExists($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        $_ENV[$k] = $v; $_SERVER[$k] = $v; putenv(sprintf('%s=%s', $k, $v));
    }
}

loadEnvIfExists(__DIR__ . '/.env');

function out($s) { echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . "<br>\n"; error_log($s); }

$api_key = getenv('MAILGUN_API_KEY');
$domain = getenv('MAILGUN_DOMAIN');
$api_base = getenv('MAILGUN_API_BASE') ?: 'api.mailgun.net';
$to = isset($_GET['to']) ? trim($_GET['to']) : getenv('MAILGUN_TEST_TO');

out('Mailgun test starting: ' . date('c'));
out('Domain: ' . ($domain ?: '[not set]'));
out('API base: ' . $api_base);
out('To: ' . ($to ?: '[not set]'));

if (empty($api_key) || empty($domain) || empty($to)) {
    out('ERROR: Missing MAILGUN_API_KEY, MAILGUN_DOMAIN or test recipient (pass ?to= or set MAILGUN_TEST_TO).');
    if (php_sapi_name() === 'cli') exit(1);
}

$url = 'https://' . $api_base . '/v3/' . $domain . '/messages';
$post = [
    'from' => 'Mailgun Test <postmaster@' . $domain . '>',
    'to' => $to,
    'subject' => 'Mailgun test message',
    'text' => 'This is a Mailgun test sent at ' . date('c'),
];

out('Sending request to ' . $url);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $api_key);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$resp = curl_exec($ch);
$errno = curl_errno($ch);
$err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0) {
    out('Curl error: ' . $err . ' (errno=' . $errno . ')');
} else {
    out('HTTP status: ' . $http_code);
    out('Response: ' . ($resp ?: '[empty]'));
    if ($http_code >= 200 && $http_code < 300) {
        out('Mailgun API accepted the message (success).');
    } else {
        out('Mailgun API returned non-success code.');
    }
}

if (php_sapi_name() === 'cli') exit(0);

?>
