<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit();
}

$orderId = (int)($_GET['id'] ?? 0);

function e($s){
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
// ทำเครื่องหมายว่า "ลูกค้าอ่านแล้ว" สำหรับข้อความที่ admin ส่ง
$stmt = $pdo->prepare("
    UPDATE custom_messages
SET is_read = 1
WHERE custom_order_id = ?
  AND sender = 'customer'
  AND is_read = 0

");
$stmt->execute([$orderId]);

/* ===== ดึง order ===== */
$stmt = $pdo->prepare("
    SELECT co.*, u.phone
FROM custom_orders co
LEFT JOIN users u ON co.user_id = u.id
WHERE co.id = ?
LIMIT 1

");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$order){
    die("ไม่พบรายการนี้");
}

/* ===== ส่งข้อความ (admin) ===== */
if(isset($_POST['send_message'])){
    $msg = trim($_POST['message'] ?? "");

    if($msg !== ""){
        $stmt = $pdo->prepare("
            INSERT INTO custom_messages (custom_order_id, sender, message)
            VALUES (?, 'admin', ?)
        ");
        $stmt->execute([$orderId, $msg]);

        header("Location: admin_custom_chat.php?id=".$orderId);
        exit();
    }
}

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
<title>💬 แอดมินแชทลูกค้า</title>
<?php include "includes/admin_style.php"; ?>


<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}
/* ===== Top Banner ===== */
.top-banner{
  background: linear-gradient(90deg, #ff5f88, #ffb7ce);
  color:#ffffff;
  text-align:center;
  padding: 12px;
  font-weight: 900;
  letter-spacing: 0.3px;
  position: relative;
  overflow:hidden;
}
.top-banner::after{
  content:"";
  position:absolute;
  inset:-80px;
  background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.35) 0%, transparent 55%);
  transform: rotate(12deg);
}

/* ===== Navbar ===== */
header{
  background-color: rgba(255,255,255,0.92);
  padding: 16px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 10px 25px rgba(0,0,0,0.06);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.brand{
  display:flex;
  align-items:center;
  gap:10px;
}
.brand .logo{
  width:42px;height:42px;border-radius:14px;
  background: linear-gradient(135deg,#ff5f88,#ffb7ce);
  box-shadow: 0 14px 35px rgba(255,95,136,0.25);
}
header h1{
  margin:0;
  font-size: 20px;
  color: #ff5f88;
  font-weight: 900;
  line-height: 1.1;
}
header small{
  display:block;
  font-size:12px;
  color:#8a6f6a;
  font-weight:800;
  margin-top:2px;
}
nav{
  display:flex;
  align-items:center;
  gap: 6px;
}
nav a{
  color: #5b4a47;
  text-decoration: none;
  font-weight: 900;
  cursor: pointer;
  padding: 10px 14px;
  border-radius: 14px;
  transition: 0.2s;
}
nav a:hover{
  background: #ffe1ea;
  color: #ff5f88;
}
nav a.active{
  background: #ffe1ea;
  color: #ff5f88;
}



.container{
  max-width:1100px;
  margin:0 auto;
  padding:0 20px;
}

.chat-head{
  margin-top: 26px;
  background:#fff;
  border-radius: 22px;
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
  margin-top: 8px;
  font-weight:800;
  color:#7a5c57;
  line-height:1.7;
}

.chat-box{
  margin-top: 16px;
  background:#fff;
  border-radius: 22px;
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
  max-width: 70%;
  font-weight:800;
  line-height:1.5;
  box-shadow: 0 12px 25px rgba(0,0,0,0.06);
}

.msg.customer{
  justify-content:flex-start;
}
.msg.customer .bubble{
  background:#fff6fb;
  border:1px solid #ffd1de;
  color:#5b4a47;
  border-bottom-left-radius: 6px;
}

.msg.admin{
  justify-content:flex-end;
}
.msg.admin .bubble{
  background: linear-gradient(90deg,#ff5f88,#ff87a0);
  color:#fff;
  border-bottom-right-radius: 6px;
}

.time{
  font-size: 12px;
  opacity: 0.85;
  margin-top: 6px;
  font-weight:700;
}

.send-box{
  margin-top: 14px;
  display:flex;
  gap: 10px;
  align-items:center;
  padding-bottom: 60px;
}

.send-box textarea{
  flex:1;
  padding: 12px 14px;
  border-radius: 18px;
  border:1px solid #ffd1de;
  outline:none;
  min-height: 54px;
  max-height: 120px;
  resize: vertical;
  font-size: 15px;
}

.send-box button{
  padding: 14px 16px;
  border-radius: 16px;
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
/* =========================
   ✅ Responsive (Mobile)
========================= */

.menu-btn{
  display:none;
  border:none;
  cursor:pointer;
  font-size: 22px;
  font-weight: 900;
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: #ffe1ea;
  color:#ff5f88;
  box-shadow: 0 14px 30px rgba(0,0,0,0.08);
  transition: 0.2s;
}

.menu-btn:hover{
  transform: translateY(-2px);
}

@media (max-width: 900px){

  header{
    padding: 14px 16px;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    position: sticky;
    top: 0;
    width: 100%;
  }

  .brand{
    width:100%;
  }

  .menu-btn{
    display:block;
    position: absolute;
    right: 16px;
    top: 64px;
  }

  nav{
    display:none;
    width:100%;
    flex-direction: column;
    gap: 10px;
    margin-top: 6px;
  }

  nav.show{
    display:flex;
    width: 100%;
    border-radius: 18px;
  }

  nav a{
    width:100%;
    text-align:center;
    padding: 14px;
    font-size: 14px;
  }

  /* ===== Card layout ===== */
  .card{
    grid-template-columns: 1fr;
    padding: 14px;
  }

  img.ref{
    width: 100%;
    height: 220px;
    border-radius: 18px;
  }

  .right{
    width:100%;
  }

  .btn{
    width:100%;
  }

  .page-head h2{
    font-size: 24px;
  }

  .grid div{
    width: 100%;
  }
}

@media (max-width: 480px){
  img.ref{
    height: 200px;
  }
}
/* ===== Top Banner ===== */
.top-banner {
  background: linear-gradient(90deg, #ff5f88, #ffb7ce);
  color: #ffffff;
  text-align: center;
  padding: 12px;
  font-weight: 900;
  letter-spacing: 0.3px;
  position: relative;
  overflow:hidden;
}
.top-banner::after{
  content:"";
  position:absolute;
  inset:-80px;
  background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.35) 0%, transparent 55%);
  transform: rotate(12deg);
}

/* ===== Navbar ===== */
header {
  background-color: rgba(255,255,255,0.92);
  padding: 16px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 10px 25px rgba(0,0,0,0.06);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.brand{
  display:flex;
  align-items:center;
  gap:10px;
}
.brand .logo{
  width:42px;height:42px;border-radius:14px;
  background: linear-gradient(135deg,#ff5f88,#ffb7ce);
  box-shadow: 0 14px 35px rgba(255,95,136,0.25);
}
header h1 {
  margin: 0;
  font-size: 20px;
  color: #ff5f88;
  font-weight: 900;
  line-height: 1.1;
}
header small{
  display:block;
  font-size:12px;
  color:#8a6f6a;
  font-weight:800;
  margin-top:2px;
}

nav{
  display:flex;
  align-items:center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content:flex-end;
}

nav a {
  color: #5b4a47;
  text-decoration: none;
  font-weight: 900;
  cursor: pointer;
  padding: 10px 14px;
  border-radius: 14px;
  transition: 0.2s;
  position: relative;
}

nav a:hover {
  background: #ffe1ea;
  color: #ff5f88;
}

nav a.active{
  background: #ffe1ea;
  color: #ff5f88;
}

/* ===== Mobile Menu Button ===== */
.menu-btn{
  display:none;
  border:none;
  cursor:pointer;
  font-size: 22px;
  font-weight: 900;
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: #ffe1ea;
  color:#ff5f88;
  box-shadow: 0 14px 30px rgba(0,0,0,0.08);
  transition: 0.2s;
}

.menu-btn:hover{
  transform: translateY(-2px);
}

/* ===== Mobile Mode ===== */
@media (max-width: 900px){

  header{
    padding: 14px 16px;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    position: sticky;
    top: 0;
  }

  .brand{
    width:100%;
  }

  .menu-btn{
    display:block;
    align-self: flex-end;
    margin-top: -56px;
  }

  nav{
    display:none;
    width:100%;
    flex-direction: column;
    gap: 10px;
    margin-top: 6px;
  }

  nav.show{
    display:flex;
    background: rgba(255,255,255,0.95);
    border: 1px solid rgba(255,183,206,0.35);
    border-radius: 18px;
    padding: 10px;
    box-shadow: 0 18px 40px rgba(0,0,0,0.10);
  }

  nav a{
    width:100%;
    text-align:center;
    padding: 12px;
    font-size: 14px;
  }
}
.container{
  max-width:1100px;
  margin:0 auto;
  padding:0 20px;
}
</style>
</head>

<body>

<div class="top-banner">
  💗 งานเค้กพิเศษ • ตอบลูกค้าไว ยอดขายก็ปัง 💗
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin • แชทลูกค้า</h1>
      <small>คุยกับลูกค้า • ตอบข้อความ • งานเค้กพิเศษ</small>
    </div>
  </div>
<button class="menu-btn" onclick="toggleMenu()">☰</button>
  <nav id="navMenu">
    <a href="admin_transaction.php">📦 ออเดอร์</a>
    <a class="active" href="admin_custom_orders.php">🎂 เค้กพิเศษ</a>
    <a href="admin_income.php">📊 รายรับรายจ่าย</a>
    <a href="shop.php">กลับหน้าร้าน</a>
    <a href="logout.php" style="color:#dc3545;">ออกจากระบบ</a>
  </nav>
</header>


<div class="container">

  <div class="chat-head">
    <h2>💬 งาน #<?= (int)$order['id'] ?> • ลูกค้าเบอร์ <?= e($order['phone'] ?? '-') ?></h2>
    <div class="info">
      <b>เบอร์:</b> <?= e($order['phone'] ?? '-') ?><br>
      <b>สถานะ:</b> <?= e($order['status']) ?><br>
      <b>วันรับ:</b> <?= e($order['pickup_date']) ?><br>
      <b>ข้อความหน้าเค้ก:</b> “<?= e($order['message_on_cake']) ?>”
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
    <textarea name="message" placeholder="พิมพ์ข้อความตอบลูกค้า..."></textarea>
    <button type="submit" name="send_message">ส่ง</button>
  </form>

</div>

<script>
const chatBox = document.getElementById("chatBox");
chatBox.scrollTop = chatBox.scrollHeight;
</script>
<script>
function openImg(src){
    document.getElementById("modalImg").src = src;
    document.getElementById("imgModal").style.display = "flex";
}
function closeImg(){
    document.getElementById("imgModal").style.display = "none";
    document.getElementById("modalImg").src = "";
}
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}

</script>
</body>
</html>
