<?php
// SMTP 连接诊断脚本（增强版）
// 现在会同时测试端口 587 (STARTTLS) 和 465 (SSL)，并输出到页面与 error_log。

// 环境读取（如果需要，请先在 form.php 中确保 .env 已导入到 getenv）
$host = getenv('SMTP_HOST') ?: 'smtp.zoho.com';
$user = getenv('SMTP_USERNAME') ?: '[not set]';
$secure_env = strtolower(getenv('SMTP_SECURE') ?: '');

function println($s) {
    echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . "\n";
    error_log($s);
}

println("SMTP diagnostic starting at " . date('c'));
println("Host: $host");
println("Secure (env): $secure_env");
println("User: " . ($user === '[not set]' ? '[not set]' : preg_replace('/(.).+(.+)@/', '$1***$2@', $user)));

// DNS resolution
$ip = gethostbyname($host);
if ($ip === $host) {
    println("DNS lookup: failed or returned same as host ($ip)");
} else {
    println("DNS lookup: $host -> $ip");
}

$timeout = 10; // seconds
$ctx = stream_context_create([]);

// ports to test (prefer 587 then 465)
$ports = [587, 465];

foreach ($ports as $port) {
    println("\n===== Testing port $port =====");

    // 1) fsockopen
    println("1) fsockopen TCP test to $host:$port");
    $errno = 0; $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        println("fsockopen: connected to $host:$port");
        $banner = @fgets($fp, 512);
        if ($banner) println("banner: " . trim($banner));
        fclose($fp);
    } else {
        println("fsockopen: failed to connect to $host:$port  (errno=$errno) $errstr");
    }

    // 2) stream_socket_client tcp://
    println("\n2) stream_socket_client tcp:// connect to $host:$port");
    $remote = sprintf('tcp://%s:%d', $host, $port);
    $s = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if ($s) {
        println("stream_socket_client tcp: connected to $remote");
        stream_set_timeout($s, 2);
        $line = @fgets($s);
        if ($line) println("banner: " . trim($line));
        fclose($s);
    } else {
        println("stream_socket_client tcp: failed to connect to $remote (errno=$errno) $errstr");
    }

    // 3) Try ssl:// for this port (useful for 465; for 587 this will usually fail but we test)
    println("\n3) stream_socket_client ssl:// connect to $host:$port");
    $remote_ssl = sprintf('ssl://%s:%d', $host, $port);
    $s2 = @stream_socket_client($remote_ssl, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if ($s2) {
        println("ssl connect: connected to $remote_ssl");
        $line = @fgets($s2);
        if ($line) println("banner: " . trim($line));
        fclose($s2);
    } else {
        println("ssl connect: failed to connect to $remote_ssl (errno=$errno) $errstr");
    }
}

println("\nDiagnostic finished.");

// CLI exit
if (php_sapi_name() === 'cli') exit(0);

?>
