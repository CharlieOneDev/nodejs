<?php
// SMTP 功能测试脚本 — 会尝试使用 PHPMailer 通过 SMTP 发送一封测试邮件
// 使用方法：部署后在浏览器打开此脚本，或在有 PHP CLI 的环境运行 `php smtp_test.php`

// 简单的 .env loader（如果你在 Railway 上，建议在 Dashboard 设置环境变量）
function loadEnvWithPutenvLocal($path) {
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

// 尝试加载本地 .env（如果存在）
loadEnvWithPutenvLocal(__DIR__ . '/.env');

require __DIR__ . '/PHPMailer_src/Exception.php';
require __DIR__ . '/PHPMailer_src/PHPMailer.php';
require __DIR__ . '/PHPMailer_src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$host = getenv('SMTP_HOST') ?: 'smtp.zoho.com';
$user = getenv('SMTP_USERNAME') ?: '[not set]';
$pass = getenv('SMTP_PASSWORD') ?: '';
$secure_raw = strtolower(getenv('SMTP_SECURE') ?: 'tls');
$port = (int) (getenv('SMTP_PORT') ?: ($secure_raw === 'ssl' ? 465 : 587));

function out($s) { echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8') . "<br>\n"; error_log($s); }

out("SMTP test starting: " . date('c'));
out("Host: $host");
out("Port: $port");
out("Secure: $secure_raw");
out("User: " . ($user === '[not set]' ? '[not set]' : preg_replace('/(.).+(.+)@/', '$1***$2@', $user)));

if ($user === '[not set]' || empty($pass)) {
    out('ERROR: SMTP_USERNAME or SMTP_PASSWORD is not set. Set env variables or .env and retry.');
    if (php_sapi_name() === 'cli') exit(1);
}

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = (int) (getenv('SMTP_DEBUG') ?: 2);
    $mail->Debugoutput = 'error_log';
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $pass;
    $mail->SMTPSecure = ($secure_raw === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $port;
    // Relax TLS checks for diagnostics only
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom($user, 'SMTP Diagnostic');
    $mail->addAddress($user);
    $mail->Subject = 'SMTP diagnostic test';
    $mail->Body = "This is a test message generated at " . date('c');

    out('Attempting to send test message...');
    $res = $mail->send();
    if ($res) {
        out('OK: message sent or accepted by SMTP server.');
    } else {
        out('FAILED: mail->send() returned false. Check error_log for PHPMailer debug.');
    }
} catch (Exception $e) {
    out('Exception while sending: ' . $mail->ErrorInfo);
}

if (php_sapi_name() === 'cli') exit(0);

?>
