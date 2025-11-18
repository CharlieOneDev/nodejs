<?php
// SMTP 连接诊断脚本
// 用法：在部署后通过浏览器访问该文件，或在有 PHP CLI 的环境中运行 `php diagnose_smtp.php`。

// 尝试从环境或 .env 读取（如果使用 .env，请确保 loadEnvWithPutenv 已在 form.php 中运行）
$host = getenv('SMTP_HOST') ?: 'smtp.zoho.com';
$user = getenv('SMTP_USERNAME') ?: '[not set]';
$port = (int) (getenv('SMTP_PORT') ?: 0);
$secure = strtolower(getenv('SMTP_SECURE') ?: '');
if ($port === 0) {
    $port = ($secure === 'ssl') ? 465 : 587;
}

function println($s) {
    echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . "\n";
    error_log($s);
}

println("SMTP diagnostic starting at " . date('c'));
println("Host: $host");
println("Port: $port");
println("Secure: $secure");
println("User: " . ($user === '[not set]' ? '[not set]' : preg_replace('/(.).+(.+)@/', '$1***$2@', $user)));

// DNS resolution
$ip = gethostbyname($host);
if ($ip === $host) {
    println("DNS lookup: failed or returned same as host ($ip)");
} else {
    println("DNS lookup: $host -> $ip");
}

$timeout = 10; // seconds

// 1) Basic TCP connect test
println("\n1) Basic TCP connection test (fsockopen)");
$errno = 0; $errstr = '';
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) {
    println("fsockopen: connected to $host:$port");
    $banner = fgets($fp, 512);
    if ($banner) println("banner: " . trim($banner));
    fclose($fp);
} else {
    println("fsockopen: failed to connect to $host:$port  (errno=$errno) $errstr");
}

// 2) stream_socket_client (tcp)
println("\n2) stream_socket_client tcp:// connect");
$remote = sprintf('tcp://%s:%d', $host, $port);
$ctx = stream_context_create([]);
$s = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
if ($s) {
    println("stream_socket_client: connected to $remote");
    stream_set_timeout($s, 2);
    $line = fgets($s);
    if ($line) println("banner: " . trim($line));
    fclose($s);
} else {
    println("stream_socket_client tcp: failed to connect to $remote (errno=$errno) $errstr");
}

// 3) If secure=ssl, try ssl://
if ($secure === 'ssl') {
    println("\n3) stream_socket_client ssl:// connect (for port 465)");
    $remote_ssl = sprintf('ssl://%s:%d', $host, $port);
    $s2 = @stream_socket_client($remote_ssl, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if ($s2) {
        println("ssl connect: connected to $remote_ssl");
        $line = fgets($s2);
        if ($line) println("banner: " . trim($line));
        fclose($s2);
    } else {
        println("ssl connect: failed to connect to $remote_ssl (errno=$errno) $errstr");
    }
}

println("\nDiagnostic finished.");

// Keep the script quiet when called by CLI — exit appropriately
if (php_sapi_name() === 'cli') exit(0);

?>
