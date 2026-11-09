<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isLoggedIn()) {
    redirect("login.php");
    exit();
}

$user = $_SESSION['user'];

/* ===== ดึงรายการ custom orders ของ user ===== */
$stmt = $pdo->prepare("
    SELECT *
    FROM custom_orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s){
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/* ===== ฟังก์ชันช่วยทำให้รูปขึ้นชัวร์ ===== */
function imgPath($path){
    if(empty($path)) return "";

    // ตัดช่องว่าง
    $path = trim($path);

    // ถ้าเป็นลิงก์เต็ม (http) ให้ใช้ได้เลย
    if(preg_match('/^https?:\/\//i', $path)){
        return $path;
    }

    // ถ้าใน DB เก็บแบบ "uploads/xxx.jpg" ก็ใช้ได้เลย
    if(strpos($path, "uploads/") === 0){
        return $path;
    }

    // ถ้าใน DB เก็บแค่ชื่อไฟล์ เช่น "cake1.jpg"
    return "uploads/" . $path;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>📌 งานเค้กพิเศษของฉัน</title>
<?php include "includes/head.php"; ?>

<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}

/* ===== MAIN ===== */
.container{
  max-width:1100px;
  margin:0 auto;
  padding:0 20px;
}

.page-head{
  text-align:center;
  margin-top: 36px;
}

.page-head h2{
  font-size: 34px;
  font-weight: 900;
  color:#ff5f88;
  margin:0;
}

.page-head p{
  margin: 8px 0 0;
  color:#8a6f6a;
  font-weight:700;
}

.list{
  margin-top: 26px;
  display:grid;
  grid-template-columns: 1fr;
  gap: 14px;
  padding-bottom: 60px;
}

.card{
  background: rgba(255,255,255,0.92);
  border-radius: 26px;
  padding: 18px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.08);
  border: 1px solid rgba(255,183,206,0.35);
  display:flex;
  gap:16px;
  align-items:flex-start;
}

.badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  background: rgba(255,95,136,0.95);
  color:#fff;
  font-size: 12px;
  font-weight: 900;
  padding: 7px 12px;
  border-radius: 999px;
}

.left{
  flex:1;
}

.status{
  font-weight:900;
  margin-top:10px;
  color:#ff5f88;
}

.row{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top: 12px;
  color:#7a5c57;
  font-weight:800;
  font-size: 14px;
}

.row div{
  background:#fff;
  border:1px solid #ffe1ea;
  padding:8px 12px;
  border-radius: 14px;
}

.btn{
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding: 12px 16px;
  border-radius: 999px;
  font-weight: 900;
  border:none;
  cursor:pointer;
  transition:0.2s;
}

.btn-chat{
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  color:#fff;
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
}
.btn-chat:hover{ transform: translateY(-2px); }

img.ref{
  width:130px;
  height:130px;
  object-fit:cover;
  border-radius: 20px;
  border:1px solid #ffe1ea;
  box-shadow: 0 12px 30px rgba(0,0,0,0.08);
}

.empty{
  margin-top: 30px;
  text-align:center;
  font-weight:900;
  color:#ff5f88;
  padding:40px;
  background:#fff;
  border-radius: 26px;
  border:1px dashed #ffb7ce;
}

.meta{
  margin-top:10px;
  font-weight:900;
  color:#7a5c57;
  line-height:1.7;
}

</style>
</head>

<body>
<?php include "includes/header.php"; ?>
<div class="container">

  <div class="page-head">
    <h2>📌 งานเค้กพิเศษของฉัน</h2>
    <p>ดูสถานะ + คุยกับร้านได้แบบแชท 💖</p>
  </div>

  <?php if(count($orders)==0): ?>
    <div class="empty">
      ยังไม่มีงานเค้กพิเศษเลย 😢 <br><br>
      <a href="custom_cake.php" class="btn btn-chat">🎂 ไปสั่งเค้กพิเศษ</a>
    </div>
  <?php endif; ?>

  <div class="list">
    <?php foreach($orders as $o): ?>

      <?php
        $img = imgPath($o['reference_image'] ?? "");
      ?>

      <div class="card">

        <?php if(!empty($img)): ?>
          <img class="ref" src="<?= e($img) ?>" alt="รูปอ้างอิงเค้ก">
        <?php else: ?>
          <img class="ref" src="https://via.placeholder.com/500x500?text=Cake" alt="Cake">
        <?php endif; ?>

        <div class="left">
          <span class="badge">🍰 งาน #<?= (int)$o['id'] ?></span>

          <div class="status">สถานะ: <?= e($o['status']) ?></div>

          <div class="row">
            <div>ขนาด: <?= e($o['cake_size']) ?></div>
            <div>รส: <?= e($o['cake_flavor']) ?></div>
            <div>ไส้: <?= e($o['cake_filling']) ?></div>
            <div>สีครีม: <?= e($o['cream_color']) ?></div>
            <div>ธีม: <?= e($o['theme']) ?></div>
            <div>วันรับ: <?= e($o['pickup_date']) ?></div>
          </div>

          <div class="meta">
            ข้อความบนหน้าเค้ก: “<?= e($o['message_on_cake']) ?>”
          </div>

          <?php if(!empty($o['details'])): ?>
            <div class="meta" style="opacity:0.9;">
              รายละเอียดเพิ่มเติม: <?= e($o['details']) ?>
            </div>
          <?php endif; ?>

          <div style="margin-top:14px;">
            <a class="btn btn-chat" href="custom_chat.php?id=<?= (int)$o['id'] ?>">
              💬 เปิดแชทกับร้าน
            </a>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

</body>
</html>
