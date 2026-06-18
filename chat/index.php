<?php
session_start();
define('ACCESS', true);
require_once '../auth/auth_check.php';

// ใช้โปรโตคอล relative - ใช้ domain เดียวกันกับ current site
$current_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$current_host = $_SERVER['HTTP_HOST'];

// ถ้า Node.js ใช้ port 5000
$node_origin = "{$current_protocol}://{$current_host}";

// หรือถ้า Node.js อยู่บน subdomain
// $node_origin = "{$current_protocol}://chat.{$current_host}";

// หรือถ้า Node.js อยู่คนละ port (ต้องแน่ใจว่า CORS อนุญาต)
$node_origin = "{$current_protocol}://{$current_host}:5000";

// รับ chat_with parameter จาก query string (user_id ที่ต้องการแชท)
$chat_with = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;
$message = isset($_GET['message']) ? trim($_GET['message']) : null;

$iframe_src = $node_origin . '/?userId=' . $_SESSION['user_id'];
if ($chat_with) {
    $iframe_src .= '&chatWith=' . $chat_with;
}
if ($message) {
    $iframe_src .= '&message=' . urlencode($message);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embedded Chat</title>
    <style>
        body, html { height: 90%; margin: 0; }
        iframe { width: 100%; height: 90vh; border-radius : 30px; }
    </style>
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <iframe id="chatFrame" src="<?php echo htmlspecialchars($iframe_src, ENT_QUOTES); ?>" title="Chat"></iframe>
        </div>
    </div>

    <script>
        // ถ้าต้องการส่งข้อมูลเพิ่มเติมหลัง iframe โหลด ให้ใช้ postMessage
        // window.addEventListener('message', ...) ในฝั่ง Node client เพื่อรับ
        const frame = document.getElementById('chatFrame');
        frame.addEventListener('load', () => {
            // ตัวอย่าง: ส่ง token/session (ถ้ามี) — ปิดใช้งานถ้าไม่ต้องการ
            // const payload = { type: 'session', token: '...or userId...' };
            // frame.contentWindow.postMessage(payload, '<?php echo $node_origin; ?>');
        });
    </script>
</body>

</html>