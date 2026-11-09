<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isLoggedIn()) {
    redirect("login.php");
    exit();
}

$user = $_SESSION['user'];
$orderId = (int)($_GET['id'] ?? 0);

function e($s){
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* ===== ดึง order นี้ (ต้องเป็นของ user เท่านั้น) ===== */
$stmt = $pdo->prepare("
    SELECT *
    FROM custom_orders
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$order){
    die("ไม่พบรายการนี้ หรือคุณไม่มีสิทธิ์เข้าถึง");
}

/* ===== ส่งข้อความ ===== */
if(isset($_POST['send_message'])){
    $msg = trim($_POST['message'] ?? "");

    if($msg !== ""){
        $stmt = $pdo->prepare("
            INSERT INTO custom_messages (custom_order_id, sender, message, is_read)
            VALUES (?, 'customer', ?, 0)
        ");
        $stmt->execute([$orderId, $msg]);

        // refresh กันกด F5 ส่งซ้ำ
        header("Location: custom_chat.php?id=".$orderId);
        exit();
    }
}

/* ===== ทำเครื่องหมายว่า ลูกค้าอ่านแล้ว (ข้อความจาก admin) ===== */
$stmt = $pdo->prepare("
    UPDATE custom_messages
    SET is_read = 1
    WHERE custom_order_id = ?
      AND sender = 'admin'
      AND is_read = 0
");
$stmt->execute([$orderId]);

/* ===== ดึงข้อความทั้งหมด ===== */
$stmt = $pdo->prepare("
    SELECT *
    FROM custom_messages
    WHERE custom_order_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$orderId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>💬 แชทกับร้าน</title>
<?php include "includes/head.php"; ?>

<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}

/* ===== CHAT HEAD ===== */
.chat-head{
  margin-top: 26px;
  background:#fff;
  border-radius: 26px;
  padding: 18px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.08);
  border: 1px solid rgba(255,183,206,0.35);
}

.chat-head h2{
  margin:0;
  font-size: 22px;
  font-weight: 900;
  color:#ff5f88;
}

.chat-head .info{
  margin-top: 10px;
  font-weight:800;
  color:#7a5c57;
  line-height:1.75;
}

/* ===== CHAT BOX ===== */
.chat-box{
  margin-top: 16px;
  background:#fff;
  border-radius: 26px;
  padding: 18px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.08);
  border: 1px solid rgba(255,183,206,0.35);
  min-height: 420px;
  max-height: 520px;
  overflow:auto;
}

.msg{
  display:flex;
  margin-bottom: 12px;
}

.msg .bubble{
  padding: 12px 14px;
  border-radius: 18px;
  max-width: 72%;
  font-weight:800;
  line-height:1.5;
  box-shadow: 0 12px 25px rgba(0,0,0,0.06);
}

.msg.customer{
  justify-content:flex-end;
}
.msg.customer .bubble{
  background: linear-gradient(90deg,#ff5f88,#ff87a0);
  color:#fff;
  border-bottom-right-radius: 6px;
}

.msg.admin{
  justify-content:flex-start;
}
.msg.admin .bubble{
  background:#fff6fb;
  border:1px solid #ffd1de;
  color:#5b4a47;
  border-bottom-left-radius: 6px;
}

.time{
  font-size: 12px;
  opacity: 0.85;
  margin-top: 6px;
  font-weight:700;
}

/* ===== SEND BOX ===== */
.send-box{
  margin-top: 14px;
  display:flex;
  gap: 10px;
  align-items:flex-end;
  padding-bottom: 60px;
}

.send-box textarea{
  flex:1;
  padding: 12px 14px;
  border-radius: 18px;
  border:1px solid #ffd1de;
  outline:none;
  min-height: 54px;
  max-height: 140px;
  resize: vertical;
  font-size: 15px;
  background:#fff;
}

.send-box textarea:focus{
  border-color:#ff5f88;
  box-shadow: 0 0 0 5px rgba(255,95,136,0.15);
}

.send-box button{
  padding: 14px 18px;
  border-radius: 999px;
  border:none;
  cursor:pointer;
  font-weight: 900;
  color:#fff;
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
  transition:0.2s;
}
.send-box button:hover{
  transform: translateY(-2px);
}


</style>
</head>

<body>
<?php include "includes/header.php"; ?>

<div class="container">

  <div class="chat-head">
    <h2>💬 แชทงาน #<?= (int)$order['id'] ?></h2>
    <div class="info">
      <b>สถานะ:</b> <?= e($order['status']) ?><br>
      <b>ขนาด:</b> <?= e($order['cake_size']) ?> |
      <b>รส:</b> <?= e($order['cake_flavor']) ?> |
      <b>ไส้:</b> <?= e($order['cake_filling']) ?><br>
      <b>ข้อความบนหน้าเค้ก:</b> “<?= e($order['message_on_cake']) ?>”
    </div>
  </div>

  <div class="chat-box" id="chatBox">
    <?php foreach($messages as $m): ?>
      <div class="msg <?= e($m['sender']) ?>">
        <div class="bubble">
          <?= nl2br(e($m['message'])) ?>
          <div class="time"><?= e($m['created_at']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <form class="send-box" method="post">
    <textarea name="message" placeholder="พิมพ์ข้อความถึงร้าน..."></textarea>
    <button type="submit" name="send_message">ส่ง</button>
  </form>

</div>

<script>
// auto scroll ไปข้อความล่าสุด
const chatBox = document.getElementById("chatBox");
chatBox.scrollTop = chatBox.scrollHeight;
</script>

</body>
</html>
