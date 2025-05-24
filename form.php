<?php
// 使用 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 根据您的安装方式引入 PHPMailer
// 方式一: Composer
// require 'vendor/autoload.php'; // 确保路径正确

// 方式二: 手动下载 (假设 PHPMailer 文件在 'PHPMailer_src' 目录下)
require 'PHPMailer_src/Exception.php';
require 'PHPMailer_src/PHPMailer.php';
require 'PHPMailer_src/SMTP.php';

error_reporting (E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED);

// ======== SMTP 服务器配置 (新添加) ========
define("SMTP_HOST", 'smtp.gmail.com');       // 您的 SMTP 服务器地址
define("SMTP_USERNAME", 'sukaboronet@gmail.com');     // 您的 SMTP 用户名
define("SMTP_PASSWORD", 'qufd zdra ipfy rlww');     // 您的 SMTP 密码
define("SMTP_SECURE", PHPMailer::ENCRYPTION_SMTPS); // 加密方式: PHPMailer::ENCRYPTION_STARTTLS (TLS) 或 PHPMailer::ENCRYPTION_SMTPS (SSL)
define("SMTP_PORT", 465);                           // SMTP 端口: 587 (TLS) 或 465 (SSL)
// ======== END SMTP 服务器配置 ========

//
$mail_sys = "customer@daion.co.jp"; // 管理者のメールアドレス
$from_name = "大恩家具株式会社";     // メール送信者の表示
$from_mail = "custsukaboronet@gmail.com";     // メール送信者のメールアドレス（SMTP服务商可能要求与认证用户一致或已验证）
$user_mail = "item2";   // 利用者にメールを送る場合のメールアドレス項目
//---
$title = "お問い合わせフォーム";
$subject = "お問い合わせ有難うございます\n"; // \n 在 PHPMailer 中对于纯文本邮件是换行，HTML邮件中是 <br>
$body = "お問い合わせ有難うございます。\n以下の内容で承りました。\n\n";
$subject_sys = "お問い合わせがありました\n";
$body_sys = "ウェブサイトから新しいお問い合わせがありました。\n\n"; // 修改一下，更清晰
$footer = "\n------------\n大恩家具株式会社\nhttps://www.daion.co.jp/\n------------\n";
//------------------------------------------------

/*
 * PHPフォーム処理
 *
 * 複数添付ファイル対応
 * XHTML対応（XML宣言の処理）
 * 入力のエスケープ処理 20190411
 * キャプチャ機能追加
 *
 * 2011-2022 (c) Crytus
 */

ini_set("short_open_tag", "0");
// ini_set("magic_quotes_gpc", "0"); // magic_quotes_gpc 在 PHP 5.4+ 已移除，不需要设置
ini_set("mbstring.internal_encoding", "UTF-8"); // 推荐设置内部编码为UTF-8
ini_set("mbstring.encoding_translation", "0");

// HTMLやプログラムの漢字コード（SJISにする場合は、HTMLを含めコードの変更が必要です）
define("SCRIPT_ENCODING", "UTF-8"); // 脚本编码保持UTF-8
// メール自体の漢字コードの指定です（PHPMailer会处理，通常设置为UTF-8）
// define("MAIL_ENCODING", "JIS"); // 此定义在PHPMailer中不再直接使用，PHPMailer有自己的CharSet属性
// define("MAIL_ENCODING", "UTF8");

// セッションを使用します（セッションが有効で無いとキャプチャは動作しません）
if (session_status() == PHP_SESSION_NONE) { // 确保session只启动一次
    session_start();
}

// キャプチャ用画像処理
if (isset($_REQUEST["act"]) && ($_REQUEST["act"] == "captcha")) {
    captcha();
}

// キャプチャ画像出力URL
$ary = explode("/", $_SERVER["REQUEST_URI"]);
$ary[count($ary)-1] = basename(__FILE__);
$captcha_path = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . implode("/", $ary) . "?act=captcha"; // 变量名修改，避免与函数重名

if (empty($form_html)) { // 使用 empty 检查更佳
    $form_html = "form.html";      // 入力フォームのファイル名
}
if (empty($confirm_html)) {
    $confirm_html = "confirm.html";    // 確認画面のファイル名
}
if (empty($finish_html)) {
    $finish_html = "finish.html";      // 終了画面のファイル名
}
// ユーザー向け
if (empty($subject)) {
    $subject = "お問い合わせ有難うございます\n";
}
if (empty($body)) {
    $body = "お問い合わせ有難うございます。\n以下の内容で承りました。\n\n";
}
// 管理者向け
if (empty($subject_sys)) {
    $subject_sys = "お問い合わせがありました\n";
}
if (empty($body_sys)) {
    $body_sys = "ウェブサイトから新しいお問い合わせがありました。\n\n";
}
// メール本文後ろ（改行に注意）
if (empty($footer)) {
    $footer = "\n------------\n大恩家具株式会社\nhttps://www.daion.co.jp/\n------------\n";
}
//  都道府県
$pref_list = array( 
    "1" => "北海道", "2" => "青森県", "3" => "岩手県", "4" => "宮城県", "5" => "秋田県",
    "6" => "山形県", "7" => "福島県", "8" => "茨城県", "9" => "栃木県", "10" => "群馬県",
    "11" => "埼玉県", "12" => "千葉県", "13" => "東京都", "14" => "神奈川県", "15" => "新潟県",
    "16" => "富山県", "17" => "石川県", "18" => "福井県", "19" => "山梨県", "20" => "長野県",
    "21" => "岐阜県", "22" => "静岡県", "23" => "愛知県", "24" => "三重県", "25" => "滋賀県",
    "26" => "京都府", "27" => "大阪府", "28" => "兵庫県", "29" => "奈良県", "30" => "和歌山県",
    "31" => "鳥取県", "32" => "島根県", "33" => "岡山県", "34" => "広島県", "35" => "山口県",
    "36" => "徳島県", "37" => "香川県", "38" => "愛媛県", "39" => "高知県", "40" => "福岡県",
    "41" => "佐賀県", "42" => "長崎県", "43" => "熊本県", "44" => "大分県", "45" => "宮崎県",
    "46" => "鹿児島県", "47" => "沖縄県", "48" => "海外", "99" => "非公開",
);
//------------------------------------------------
//
$form_input = array(
    "item1" => array("title" => "お名前", "name" => "item1", "func" => "2", "require" => "1", "check" => "1",),
    "item2" => array("title" => "メールアドレス", "name" => "item2", "func" => "2", "require" => "1", "check" => "3",), // 确保 item2 是用户邮箱字段
    "item3" => array("title" => "お問い合わせ内容", "name" => "item3", "func" => "7", "require" => "1", "check" => "1",),
);
// 入力値の取得
$msg = array();
// $mail = array(); // 这个变量在原代码中似乎没有被有效使用，可以考虑移除
$mail_field = array();
$form = array(); // 初始化 $form 数组

foreach ($form_input as $val) {
    $value_present = false; // 用于判断字段是否有值
    if ($val["func"] == 6) { // 複数個1行テキスト入力 (Checkbox group or similar that might return array)
        if (isset($_REQUEST[$val["name"]]) && is_array($_REQUEST[$val["name"]])) {
            foreach ($_REQUEST[$val["name"]] as $k => $v) {
                $form[$val["name"]][$k] = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
                if (trim($v) != "") {
                    $value_present = true;
                }
            }
        } elseif (isset($_REQUEST[$val["name"]])) { // Should not happen for func 6 if expecting array, but as a fallback
            $form[$val["name"]] = htmlspecialchars(trim($_REQUEST[$val["name"]]), ENT_QUOTES, 'UTF-8');
            if (trim($_REQUEST[$val["name"]]) != "") {
                 $value_present = true;
            }
        }
    } else if ($val["func"] == 10) { // 都道府県 + 住所
        $form[$val["name"] . "_pref"] = isset($_REQUEST[$val["name"] . "_pref"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_pref"]), ENT_QUOTES, 'UTF-8') : '';
        $form[$val["name"] . "_address"] = isset($_REQUEST[$val["name"] . "_address"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_address"]), ENT_QUOTES, 'UTF-8') : '';
        if ($form[$val["name"] . "_pref"] && $form[$val["name"] . "_address"]) {
            $value_present = true;
        }
    } else if ($val["func"] == 11) { // 年月日
        $form[$val["name"] . "_year"] = isset($_REQUEST[$val["name"] . "_year"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_year"]), ENT_QUOTES, 'UTF-8') : '';
        $form[$val["name"] . "_month"] = isset($_REQUEST[$val["name"] . "_month"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_month"]), ENT_QUOTES, 'UTF-8') : '';
        $form[$val["name"] . "_day"] = isset($_REQUEST[$val["name"] . "_day"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_day"]), ENT_QUOTES, 'UTF-8') : '';
        if ($form[$val["name"] . "_year"] && $form[$val["name"] . "_month"] && $form[$val["name"] . "_day"]) {
            $value_present = true;
        }
    } else if ($val["func"] == 12) { // 月日
        $form[$val["name"] . "_month"] = isset($_REQUEST[$val["name"] . "_month"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_month"]), ENT_QUOTES, 'UTF-8') : '';
        $form[$val["name"] . "_day"] = isset($_REQUEST[$val["name"] . "_day"]) ? htmlspecialchars(trim($_REQUEST[$val["name"] . "_day"]), ENT_QUOTES, 'UTF-8') : '';
        if ($form[$val["name"] . "_month"] && $form[$val["name"] . "_day"]) {
            $value_present = true;
        }
    } else if ($val["func"] == 13) {  // File
        if (isset($_FILES[$val["name"]]) && $_FILES[$val["name"]]["error"] == UPLOAD_ERR_OK) {
            // 添付ファイル処理 (PHPMailer可以直接处理临时文件路径，或如原样base64)
            // 为了与原逻辑兼容，我们继续使用base64编码数据传递
            $handle = fopen($_FILES[$val["name"]]["tmp_name"], 'r');
            $attachFile = fread($handle, filesize($_FILES[$val["name"]]["tmp_name"]));
            fclose($handle);
            $form[$val["name"] . "_value"] = base64_encode($attachFile); // Base64 编码的文件内容
            $form[$val["name"] . "_file"] = $_FILES[$val["name"]]["name"]; // 文件名
            $form[$val["name"] . "_type"] = $_FILES[$val["name"]]["type"]; // Mime Type
            $value_present = true;
        } else if (isset($_REQUEST[$val["name"] . "_value"]) && !empty($_REQUEST[$val["name"] . "_value"])) { // 从 confirm 页面回传
            $form[$val["name"] . "_value"] = $_REQUEST[$val["name"] . "_value"];
            $form[$val["name"] . "_file"] = $_REQUEST[$val["name"] . "_file"];
            $form[$val["name"] . "_type"] = $_REQUEST[$val["name"] . "_type"];
            $value_present = true;
        }
    } else { // Default text input, textarea, select etc.
        if (isset($_REQUEST[$val["name"]]) && is_array($_REQUEST[$val["name"]])) {
            foreach ($_REQUEST[$val["name"]] as $k => $v) {
                $form[$val["name"]][$k] = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
                if (trim($v) != "") {
                    $value_present = true;
                }
            }
        } elseif (isset($_REQUEST[$val["name"]])) {
            $form[$val["name"]] = htmlspecialchars(trim($_REQUEST[$val["name"]]), ENT_QUOTES, 'UTF-8');
            if (trim($form[$val["name"]]) != "") {
                $value_present = true;
            }
        }
    }

    // 入力のチェック
    if (isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "form") { // 通常是在提交form时检查
        if (!empty($val["require"]) && !$value_present) {
            $msg[$val["name"]] = $val["title"] . "が入力されていません。";
        }
        if (!empty($val["check"]) && $value_present) {
            if ($val["check"] == 2) {   // 電話
                if (!preg_match("/^[0-9\-]+$/", $form[$val["name"]])) {
                    $msg[$val["name"]] = $val["title"] . "が正しくありません";
                }
            }
            if (($val["check"] == 3)||($val["check"] == 4)) {   // メール
                if (!filter_var($form[$val["name"]], FILTER_VALIDATE_EMAIL)) { // 使用filter_var验证邮箱更佳
                    $msg[$val["name"]] = $val["title"] . "が正しくありません";
                } else {
                    $mail_value[] = $form[$val["name"]];
                    $mail_field[] = $val["name"];
                    $mail_title[] = $val["title"];
                }
            }
            if ($val["check"] == 5) {   // URL
                if (!filter_var($form[$val["name"]], FILTER_VALIDATE_URL)) { // 使用filter_var验证URL
                    $msg[$val["name"]] = $val["title"] . "が正しくありません";
                }
            }
            if ($val["check"] == 6) {   // キャプチャ
                if (empty($form[$val["name"]]) || empty($_SESSION["captcha"]) || $form[$val["name"]] != $_SESSION["captcha"]) {
                    $msg[$val["name"]] = $val["title"] . "が正しくありません";
                    $form[$val["name"]] = "";
                }
            }
        }
    }
}

// メール一致 (通常用于邮箱和邮箱确认字段)
if (isset($mail_value) && count($mail_value) == 2) {
    if ($mail_value[0] != $mail_value[1]) {
        if(isset($mail_field[0])) $msg[$mail_field[0]] = (isset($mail_title[0]) ? $mail_title[0] : 'メールアドレス') . "が一致していません";
        if(isset($mail_field[1])) $msg[$mail_field[1]] = (isset($mail_title[0]) ? $mail_title[0] : 'メールアドレス') . "が一致していません"; // 通常第二个字段没有title
    }
}

$current_mode = ""; // mode 变量名修改，避免冲突
if (!isset($_REQUEST["mode"])) {
    $current_mode = "form";
} else if ($_REQUEST["mode"] == "reinput") {
    $current_mode = "form";
} else if ($_REQUEST["mode"] != "confirm") { // This means it's the initial form submission
    if (!empty($msg)) { // If there are validation errors
        $current_mode = "form";
    } else {
        $current_mode = "confirm";
    }
} else { // mode is "confirm", so we are processing the confirmed data to send email
    // メールの送信
    // 本文へ入力値の設定
    $mail_body_content = ""; // Use a different variable for mail body content
    foreach ($form_input as $key => $val) {
        // ... (原有的 $mail_body 构建逻辑保持不变, 只是将结果存到 $mail_body_content)
        if ($val["func"] == 13) {   // File
            $mail_body_content .= "■" . $val["title"] . "：" . (isset($form[$val["name"] . "_file"]) ? $form[$val["name"] . "_file"] : 'なし') . "\n";
        } else if ($val["func"] == 10) {
            $mail_body_content .= "■" . $val["title"] . "：" . (isset($pref_list[$form[$val["name"] . "_pref"]]) ? $pref_list[$form[$val["name"] . "_pref"]] : '') . (isset($form[$val["name"] . "_address"]) ? $form[$val["name"] . "_address"] : '') . "\n";
        } else if ($val["func"] == 11) {
            $mail_body_content .= "■" . $val["title"] . "：" . (isset($form[$val["name"] . "_year"]) ? $form[$val["name"] . "_year"] . "年" : '') .
                (isset($form[$val["name"] . "_month"]) ? $form[$val["name"] . "_month"] . "月" : '') . (isset($form[$val["name"] . "_day"]) ? $form[$val["name"] . "_day"] . "日" : '') . "\n";
        } else if ($val["func"] == 12) {
            $mail_body_content .= "■" . $val["title"] . "：" . (isset($form[$val["name"] . "_month"]) ? $form[$val["name"] . "_month"] . "月" : '') . (isset($form[$val["name"] . "_day"]) ? $form[$val["name"] . "_day"] . "日" : '') . "\n";
        } else if ($val["func"] == 3) { // 単一選択（ラジオボタン）
            $mail_body_content .= "■" . $val["title"] . "：" . (isset($form_input[$val["name"]]["list"][$form[$val["name"]]]) ? $form_input[$val["name"]]["list"][$form[$val["name"]]] : '') . "\n";
        } else if ($val["func"] == 4) { // 複数選択（チェックボックス）
            if (isset($form[$val["name"]]) && is_array($form[$val["name"]])) {
                $ary = array();
                foreach ($form[$val["name"]] as $val2) {
                    if(isset($form_input[$val["name"]]["list"][$val2])) $ary[] = $form_input[$val["name"]]["list"][$val2];
                }
                if(!empty($ary)) $mail_body_content .= "■" . $val["title"] . "：" . implode("、", $ary) . "\n";
            }
        } else if ($val["func"] == 5) { // 選択（プルダウン）
             $mail_body_content .= "■" . $val["title"] . "：" . (isset($form_input[$val["name"]]["list"][$form[$val["name"]]]) ? $form_input[$val["name"]]["list"][$form[$val["name"]]] : '') . "\n";
        } else if ($val["func"] == 6) { // 複数個1行テキスト入力
            $mail_body_content .= "■" . $val["title"] . "：\n";
            if(isset($form_input[$val["name"]]["list"]) && is_array($form_input[$val["name"]]["list"])) {
                foreach ($form_input[$val["name"]]["list"] as $key_item => $val_item) {
                     $mail_body_content .= "　" . $val_item . "：" . (isset($form[$val["name"]][$key_item]) ? $form[$val["name"]][$key_item] : '') . "\n";
                }
            }
        } else if ($val["func"] == 14) {    // キャプチャ
            // キャプチャが存在する場合、そのチェックが通らないとメールの送信はしない (这个检查应在 mode 判断前)
            // 但如果到了这里，通常是已通过或不需要 captcha
        } else { // General text field
             $mail_body_content .= "■" . $val["title"] . "：" . (isset($form[$val["name"]]) ? $form[$val["name"]] : '') . "\n";
        }
    }

    if (isset($_SESSION["captcha"])) { // 清理captcha session
        unset($_SESSION["captcha"]);
    }

    $full_mail_body_for_user = $body . $mail_body_content . $footer;
    $full_mail_body_for_admin = $body_sys . $mail_body_content . $footer;

    $attachments_for_phpmailer = array();
    foreach ($form_input as $val) {
        if ($val["func"] == 13) {   // File
            if (isset($form[$val["name"] . "_value"]) && !empty($form[$val["name"] . "_value"])) {
                $attachments_for_phpmailer[] = array(
                    "base64_content" => $form[$val["name"] . "_value"], // base64编码的内容
                    "filename" => $form[$val["name"] . "_file"],
                    "filetype" => $form[$val["name"] . "_type"]
                );
            }
        }
    }

    $user_email_address = "";
    if (isset($user_mail) && !empty($user_mail) && isset($form[$user_mail]) && filter_var($form[$user_mail], FILTER_VALIDATE_EMAIL)) {
        $user_email_address = $form[$user_mail];
    }


    if ($mail_sys) {
        // 管理者向け
        // echo "Sending to admin: $mail_sys <br>"; // Debug
        sendMailWithPHPMailer($from_mail, $mail_sys, $subject_sys, $full_mail_body_for_admin, $attachments_for_phpmailer, $from_name);
    }
    //
    if ($user_email_address) {
        // 利用者向け
        // echo "Sending to user: $user_email_address <br>"; // Debug
        sendMailWithPHPMailer($from_mail, $user_email_address, $subject, $full_mail_body_for_user, $attachments_for_phpmailer, $from_name);
    }
    //
    $current_mode = "finish";
}


// HTML Template Loading
$contents = "";
if ($current_mode == "confirm") {
    if (file_exists($confirm_html)) $contents = file_get_contents($confirm_html);
} else if ($current_mode == "finish") {
    if (file_exists($finish_html)) $contents = file_get_contents($finish_html);
} else { // form
    if (file_exists($form_html)) $contents = file_get_contents($form_html);
}

if (empty($contents)) {
    die("Error: HTML template file not found or mode is invalid. Mode: " . htmlspecialchars($current_mode));
}


$head = "";
// XML宣言の処理 - 保持
if (preg_match("/^(<\?xml[^?]+\?>\s*)/si", $contents, $m)) {
    $head = $m[1];
    $contents = substr($contents, strlen($head));
}

// HTML出力
echo $head;

// eval() is dangerous. Consider replacing with a safer templating approach if possible.
// For now, keeping it to maintain original functionality.
// Ensure $contents doesn't contain user-supplied PHP code.
// The variables used in eval should be well-defined ($form, $msg, $captcha_path etc.)
echo eval("?>" . $contents . "<?php "); // Added <?php to avoid issues if $contents ends with PHP code.

/*
 * メール送信処理 (PHPMailer 版)
 *
 */
function sendMailWithPHPMailer($from, $to, $subject, $body_text, $attachments_data = array(), $from_name_display = null)
{
    $mail = new PHPMailer(true); // Enable exceptions

    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output for testing
        $mail->SMTPDebug = SMTP::DEBUG_OFF;     // Disable debug output for production
        $mail->isSMTP();                        // Send using SMTP
        $mail->Host       = SMTP_HOST;          // Set the SMTP server to send through
        $mail->SMTPAuth   = true;               // Enable SMTP authentication
        $mail->Username   = SMTP_USERNAME;      // SMTP username
        $mail->Password   = SMTP_PASSWORD;      // SMTP password
        $mail->SMTPSecure = SMTP_SECURE;        // Enable implicit TLS encryption
        $mail->Port       = SMTP_PORT;          // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        // 使用传入的 $from 和 $from_name_display
        $mail->setFrom($from, ($from_name_display ?: $from)); // $from_name_display 为空则使用 $from 作为显示名
        $mail->addAddress(trim($to));           // Add a recipient

        // $mail->addReplyTo('info@example.com', 'Information'); // 可选：设置回复地址
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        //Attachments
        if (!empty($attachments_data)) {
            foreach ($attachments_data as $att) {
                if (isset($att['base64_content']) && isset($att['filename']) && isset($att['filetype'])) {
                    // PHPMailer的addStringAttachment需要原始二进制数据，不是base64
                    $mail->addStringAttachment(base64_decode($att['base64_content']), $att['filename'], 'base64', $att['filetype']);
                }
            }
        }

        //Content
        $mail->isHTML(false); //  当前邮件内容是纯文本。如果需要HTML，设为true，并修改$body_text的格式
        $mail->CharSet = 'UTF-8'; // 强烈推荐使用UTF-8
        $mail->Subject = $subject;
        $mail->Body    = $body_text; // \n 会被视为换行
        // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients'; // 如果isHTML(true)

        $mail->send();
        // echo 'Message has been sent to ' . $to . '<br>'; // For debugging
        return true;
    } catch (Exception $e) {
        // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo} (To: $to)<br>"; // For debugging
        // 记录错误日志而不是直接输出到页面，对用户更友好
        error_log("PHPMailer Error for $to: " . $mail->ErrorInfo);
        return false;
    }
}

// 原有的 sendmail 函数不再使用，可以删除或注释掉
/*
function sendmail($mail_from, $mail_to, $mail_subject, $body, $attach, $from_name=null)
{
    // ... original code ...
    return @mail($mail_to, $subject_str, mb_convert_encoding($body, MAIL_ENCODING, SCRIPT_ENCODING), $header);
}
*/


// 辅助函数保持不变
if (!function_exists("safeStripSlashes")) { // 有的旧系统可能需要
    function safeStripSlashes($var) {
      if (is_array($var)) {
        return array_map('safeStripSlashes', $var);
      } else {
        return stripslashes($var); // 原代码缺少 stripslashes
      }
    }
    // 如果 get_magic_quotes_gpc() 存在且为 on，才应用 stripslashes
    // if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    //     $_REQUEST = safeStripSlashes($_REQUEST);
    //     // Apply to $_POST, $_GET, $_COOKIE as well if needed directly
    // }
}

function value($key)
{
    if (isset($_REQUEST[$key])) {
        if (is_array($_REQUEST[$key])) {
            return implode(",", $_REQUEST[$key]); // Be cautious with implode, ensure data is clean
        }
        return $_REQUEST[$key];
    }
    return ''; // Return empty string if not set
}

function x_count($item)
{
    if (is_array($item)) {
        return count($item);
    }
    return 0;
}

function imgch($ch)
{
    $im = imagecreate(20, 20);
    $bg = imagecolorallocate($im, 255, 255, 255);
    $textcolor = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 3, 3, $ch, $textcolor); // Font size 5 is more standard for imagestring
    $r = mt_rand(-30, 30); // Slightly reduced rotation
    $im2 = imagerotate($im, $r, imagecolorallocatealpha($im, 0,0,0,127)); // Transparent background for rotation
    imagecolortransparent($im2, imagecolorallocatealpha($im2, 0,0,0,127));
    imagedestroy($im);
    return $im2;
}

function captcha()
{
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }

    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // O, I, 0, 1 removed for clarity
    $captcha_string = '';
    for ($i = 0; $i < 4; $i++) {
        $captcha_string .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    $_SESSION["captcha"] = $captcha_string;

    $img = imagecreatetruecolor(120, 40); // Adjusted size
    $bg_color = imagecolorallocate($img, 250, 250, 250);
    imagefill($img, 0, 0, $bg_color);

    $text_color = imagecolorallocate($img, 50, 50, 50);
    $line_color = imagecolorallocate($img, 180, 180, 180);

    // Add some noise lines
    for($i=0; $i<5; $i++) {
        imageline($img, mt_rand(0,120), mt_rand(0,40), mt_rand(0,120), mt_rand(0,40), $line_color);
    }

    // Add text with slight variation
    $font_size = 20; // Approximate for imagettftext, adjust if using imagestring
    $x_offset = 10;
    // Try to find a TTF font, otherwise fallback (or ensure one is available)
    $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'; // Example path, adjust to your server
    if (!file_exists($font)) { $font = 5; } // Fallback to GD built-in font if TTF not found

    for ($i = 0; $i < strlen($captcha_string); $i++) {
        $angle = mt_rand(-15, 15);
        $y_offset = mt_rand(25, 35);
        if (is_int($font)) { // GD font
             imagestring($img, $font, $x_offset, $y_offset - 15, $captcha_string[$i], $text_color);
        } else { // TTF font
            imagettftext($img, $font_size, $angle, $x_offset, $y_offset, $text_color, $font, $captcha_string[$i]);
        }
        $x_offset += 25 + mt_rand(-3,3) ;
    }

    header("Content-type: image/png");
    imagepng($img);
    imagedestroy($img);
    exit;
}
?>