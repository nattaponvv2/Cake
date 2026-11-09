<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit;
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
/* ================== UPDATE STATUS ================== */
if(isset($_POST['update_status'])){
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'] ?? 'waiting';

    $allow = ['waiting','paid','cod','cancel','pending'];

    if(in_array($new_status, $allow)){
        $stmt = $pdo->prepare("UPDATE orders SET payment_status=? WHERE id=?");
        $stmt->execute([$new_status, $order_id]);
    }

    redirect("admin_transaction.php");
    exit;
}

/* ================== ORDERS ================== */
$orders = $pdo->query("
    SELECT * FROM orders
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ================== TODAY TOTAL ================== */
$todayTotal = $pdo->query("
    SELECT COALESCE(SUM(total),0) AS total
    FROM orders
    WHERE DATE(created_at)=CURDATE()
    AND payment_status IN ('paid','cod')
")->fetch()['total'];

/* ================== COUNT SUMMARY ================== */
$todayOrders = $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE DATE(created_at)=CURDATE()
")->fetchColumn();

$waitingCount = $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE payment_status='waiting'
")->fetchColumn();

$paidCount = $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE payment_status='paid'
")->fetchColumn();

/* ================== DAILY SUMMARY ================== */
$dailySummary = $pdo->query("
    SELECT DATE(created_at) AS day, SUM(total) AS total
    FROM orders
    WHERE payment_status IN ('paid','cod')
    GROUP BY day
    ORDER BY day DESC
    LIMIT 7
")->fetchAll();

/* ================== MONTHLY SUMMARY ================== */
$monthlySummary = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, SUM(total) AS total
    FROM orders
    WHERE payment_status IN ('paid','cod')
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>แอดมินตรวจออเดอร์</title>
<?php include "includes/head.php"; ?>

<style>
*{ box-sizing:border-box; }

body{
    font-family:"Prompt", sans-serif;
    margin:0;
    background: linear-gradient(180deg, #fff6fb 0%, #ffffff 100%);
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


/* ===== Layout ===== */
.container{
    max-width: 1250px;
    margin: 26px auto;
    padding: 0 16px 60px;
}

.page-title{
    text-align:center;
    margin: 18px 0 18px;
}

.page-title h1{
    margin:0;
    font-size: 26px;
    font-weight: 900;
    color:#ff4f7b;
}

.page-title p{
    margin: 8px 0 0;
    color:#8a6f6a;
    font-weight:700;
    font-size: 13px;
}

/* ===== Cards ===== */
.grid{
    display:grid;
    grid-template-columns: 1fr;
    gap: 18px;
}

.card{
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(255,183,206,0.35);
    border-radius: 22px;
    box-shadow: 0 18px 60px rgba(0,0,0,0.08);
    padding: 18px;
}

.card h2{
    margin:0 0 12px;
    font-size: 18px;
    font-weight: 900;
    color:#ff4f7b;
}

/* ===== Summary ===== */
.summary{
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

@media (max-width: 1000px){
    .summary{ grid-template-columns: 1fr 1fr; }
}
@media (max-width: 600px){
    .summary{ grid-template-columns: 1fr; }
}

.sum-box{
    background:#fff;
    border: 1px solid rgba(255,183,206,0.35);
    border-radius: 18px;
    padding: 16px;
}

.sum-box .label{
    font-weight: 900;
    color:#6f5a56;
    font-size: 14px;
}

.sum-box .value{
    margin-top: 8px;
    font-size: 24px;
    font-weight: 900;
    color:#ff4f7b;
}

/* ===== Table ===== */
.table-wrap{
    overflow:auto;
    border-radius: 18px;
    border: 1px solid rgba(255,183,206,0.35);
}

table{
    width:100%;
    border-collapse: collapse;
    min-width: 980px;
    background:#fff;
}

th{
    background: linear-gradient(90deg, #ff5f88, #ff87a0);
    color:white;
    padding: 12px 10px;
    font-size: 13px;
    text-align:center;
    position: sticky;
    top: 0;
}

td{
    padding: 10px 10px;
    border-bottom: 1px solid #f2e4ea;
    text-align:center;
    font-size: 13px;
}

tr:hover td{
    background:#fff1f6;
}

/* ===== Status badge ===== */
.badge{
    display:inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 12px;
    border: 1px solid transparent;
    white-space: nowrap;
}

.badge-paid{
    background:#eafff1;
    color:#1a7f3b;
    border-color:#bff5d2;
}

.badge-cod{
    background:#eef6ff;
    color:#1c4aa8;
    border-color:#cfe1ff;
}

.badge-waiting{
    background:#fff7e6;
    color:#b26a00;
    border-color:#ffe0a3;
}

.badge-pending{
    background:#fff7e6;
    color:#b26a00;
    border-color:#ffe0a3;
}

.badge-cancel{
    background:#ffecec;
    color:#b00020;
    border-color:#ffc9d2;
}

/* ===== Slip ===== */
.slip{
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 14px;
    border: 1px solid rgba(255,183,206,0.5);
    box-shadow: 0 10px 22px rgba(0,0,0,0.08);
    cursor:pointer;
    transition:.2s;
}
.slip:hover{
    transform: scale(1.06);
}

/* ===== Popup image ===== */
#imgModal{
    display:none;
    position:fixed;
    inset:0;
    background: rgba(0,0,0,0.55);
    z-index: 9999;
    justify-content:center;
    align-items:center;
    padding: 18px;
}

#imgModal .box{
    width: min(760px, 95vw);
    background:#fff;
    border-radius: 22px;
    padding: 14px;
    box-shadow:0 20px 70px rgba(0,0,0,0.25);
}

#imgModal img{
    width:100%;
    border-radius: 18px;
    display:block;
}

#imgModal .close{
    margin-top: 12px;
    width:100%;
    border:none;
    padding: 12px;
    border-radius: 999px;
    cursor:pointer;
    font-weight: 900;
    background:#2f2a29;
    color:#fff;
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
    width: 100%;
    margin: 0;
    border-radius: 0;
    left: 0;
    right: 0;
  }

  .top-banner{
    border-radius: 0;
  }
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
}

@media (max-width: 768px){
  .hide-mobile{
    display:none !important;
  }
}
@media (max-width: 900px){
  .container{
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 12px !important;
  }

  .card{
    border-radius: 16px;
    padding: 14px;
  }
}
.table-wrap{
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  width: 100%;
}


.table-wrap{
  overflow-x: auto;   /* ตารางเลื่อนได้ */
  -webkit-overflow-scrolling: touch;
  width: 100%;
}
.table-wrap{
  touch-action: pan-x;
}
.table-wrap{
  cursor: grab;
}

.top-banner,
header{
  width: 100%;
  margin: 0;
  border-radius: 0;
}
@media (max-width: 900px){

  header{
    width: 100%;
    margin: 0;
    border-radius: 0;
    left: 0;
    right: 0;
  }

  .top-banner{
    border-radius: 0;
  }
}
/* ====== MOBILE NAV FIX ====== */

/* ปุ่ม 3 ขีด */
.menu-btn{
  display:none;
  border:0;
  background:#fff;
  width:44px;
  height:44px;
  border-radius:14px;
  font-size:20px;
  cursor:pointer;
  box-shadow:0 10px 25px rgba(0,0,0,0.10);
}



</style>
</head>

<body>
<div class="top-banner">
  💗 ระบบหลังบ้าน • ตรวจออเดอร์ • ตรวจสลิป • ยอดขายปัง 💗
</div>


<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin • ตรวจออเดอร์</h1>
      <small>จัดการคำสั่งซื้อ • ตรวจสลิป • อัปเดตสถานะ</small>
    </div>
  </div>
  <?php include "includes/admin_style.php"; ?>

<button class="menu-btn" onclick="toggleMenu()">☰</button>
<nav id="navMenu">

    <a class="active" href="admin_transaction.php">📦 ออเดอร์</a>
    <a href="admin_custom_orders.php">🎂 เค้กพิเศษ</a>
    <a href="admin_products.php">🛍️ สินค้า</a>
    <a href="admin_income.php">📊 รายรับรายจ่าย</a>
    <a href="shop.php">กลับหน้าร้าน</a>
    <a href="includes/logout.php" style="color:#dc3545;">ออกจากระบบ</a>
  </nav>
</header>



<div class="container">

    <div class="page-title">
        <h1>📦 รายการสั่งซื้อ / ตรวจสลิป</h1>
        <p>ระบบหลังบ้านร้านอบอุ่นเบเกอรี่ 🍰</p>
    </div>

    <div class="grid">

        <!-- ===== SUMMARY ===== -->
        <div class="card">
            <h2>💖 สรุปภาพรวม</h2>

            <div class="summary">
                <div class="sum-box">
                    <div class="label">💰 รายได้วันนี้</div>
                    <div class="value"><?= number_format($todayTotal,2) ?> บาท</div>
                </div>

                <div class="sum-box">
                    <div class="label">📦 ออเดอร์วันนี้</div>
                    <div class="value"><?= number_format($todayOrders) ?> รายการ</div>
                </div>

                <div class="sum-box">
                    <div class="label">⏳ รอตรวจสลิป</div>
                    <div class="value"><?= number_format($waitingCount) ?> รายการ</div>
                </div>

                <div class="sum-box">
                    <div class="label">✅ ชำระแล้ว</div>
                    <div class="value"><?= number_format($paidCount) ?> รายการ</div>
                </div>
            </div>
        </div>

        <!-- ===== DAILY ===== -->
        <div class="card">
            <h2>📅 สรุปรายวัน (7 วันล่าสุด)</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>วันที่</th><th>ยอดรวม (บาท)</th></tr>
                    <?php foreach($dailySummary as $d): ?>
                    <tr>
                        <td><?= e($d['day']) ?></td>
                        <td><?= number_format($d['total'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- ===== MONTHLY ===== -->
        <div class="card">
            <h2>🗓️ สรุปรายเดือน (6 เดือนล่าสุด)</h2>
            <div class="table-wrap">
                <table>
                    <tr><th>เดือน</th><th>ยอดรวม (บาท)</th></tr>
                    <?php foreach($monthlySummary as $m): ?>
                    <tr>
                        <td><?= e($m['month']) ?></td>
                        <td><?= number_format($m['total'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- ===== ORDERS ===== -->
        <div class="card">
            <h2>📦 รายการออเดอร์ทั้งหมด</h2>

            <div class="table-wrap">
                <table>
                    <tr>
  <th>ID</th>
  <th>ลูกค้า</th>
  <th class="hide-mobile">เบอร์</th>
  <th>ยอด</th>
  <th class="hide-mobile">ชำระเงิน</th>
  <th>สลิป</th>
  <th>สถานะ</th>
  <th class="hide-mobile">เวลา</th>
  <th>จัดการ</th>

</tr>


                    <?php foreach($orders as $o): 
                        $status = $o['payment_status'] ?? 'waiting';

                        $badgeClass = "badge-waiting";
                        $statusText = "⏳ รอตรวจสลิป";

                        if($status === 'paid'){
                            $badgeClass = "badge-paid";
                            $statusText = "✅ ชำระแล้ว";
                        }
                        if($status === 'cod'){
                            $badgeClass = "badge-cod";
                            $statusText = "🚚 เก็บเงินปลายทาง";
                        }
                        if($status === 'pending'){
                            $badgeClass = "badge-pending";
                            $statusText = "⏳ รอตรวจสลิป";
                        }
                        if($status === 'cancel'){
                            $badgeClass = "badge-cancel";
                            $statusText = "❌ ยกเลิก";
                        }
                    ?>
                    <tr>
  <td><?= (int)$o['id'] ?></td>
  <td><?= e($o['customer_name']) ?></td>

  <td class="hide-mobile"><?= e($o['phone']) ?></td>

  <td><?= number_format((float)$o['total'],2) ?></td>

  <td class="hide-mobile"><?= e($o['payment_method']) ?></td>

  <td>
    <?php if(!empty($o['slip_image'])): ?>
      <img
        src="<?= e($o['slip_image']) ?>"
        class="slip"
        onclick="openImg('<?= e($o['slip_image']) ?>')"
        title="คลิกดูรูปใหญ่"
      >
    <?php else: ?>
      -
    <?php endif; ?>
  </td>

  <td>
    <span class="badge <?= $badgeClass ?>">
      <?= e($statusText) ?>
    </span>
  </td>

  <td class="hide-mobile"><?= e($o['created_at']) ?></td>
  <td>
  <form method="post" style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">


    <select name="new_status"
      style="padding:8px 10px;border-radius:999px;border:1px solid #ffd1de;font-weight:900;cursor:pointer;">
      <option value="waiting">⏳ waiting</option>
      <option value="paid">✅ paid</option>
      <option value="cod">🚚 cod</option>
      <option value="cancel">❌ cancel</option>
    </select>

    <button type="submit" name="update_status" value="1"
      style="padding:8px 12px;border-radius:999px;border:none;font-weight:900;cursor:pointer;background:#2f2a29;color:#fff;">
      อัปเดต
    </button>
  </form>
</td>

</tr>

                    <?php endforeach; ?>

                </table>
            </div>
        </div>

    </div>
</div>

<!-- ===== Modal ===== -->
<div id="imgModal" onclick="closeImg()">
    <div class="box" onclick="event.stopPropagation()">
        <img id="modalImg" src="">
        <button class="close" onclick="closeImg()">ปิด</button>
    </div>
</div>

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
