<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit();
}

function e($s){
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/* ===== อัปเดตสถานะ ===== */
if(isset($_POST['update_status'])){
    $id = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    $allow = ['รอตรวจสอบ','รับงานแล้ว','กำลังทำ','รอส่งมอบ','เสร็จแล้ว','ยกเลิก'];

    if($id > 0 && in_array($status, $allow)){
        $stmt = $pdo->prepare("UPDATE custom_orders SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
    }

    header("Location: admin_custom_orders.php");
    exit();
}

/* ===== ดึงรายการทั้งหมด + จำนวนข้อความใหม่จากลูกค้า ===== */
$orders = $pdo->query("
    SELECT 
        co.*, 
        u.phone,
        (
            SELECT COUNT(*) 
            FROM custom_messages cm
            WHERE cm.custom_order_id = co.id
              AND cm.sender = 'customer'
              AND cm.is_read = 0
        ) AS unread_count
    FROM custom_orders co
    LEFT JOIN users u ON co.user_id = u.id
    ORDER BY co.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>👑 แอดมิน - งานเค้กพิเศษ</title>
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
.top-banner { background: linear-gradient(90deg, #ff5f88, #ffb7ce); color: #ffffff; text-align: center; padding: 12px; font-weight: 900; letter-spacing: 0.3px; position: relative; overflow:hidden; } .top-banner::after{ content:""; position:absolute; inset:-80px; background: radial-gradient(circle at 30% 40%, rgba(255,255,255,0.35) 0%, transparent 55%); transform: rotate(12deg); } 
/* ===== Navbar ===== */ header { background-color: rgba(255,255,255,0.92); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 25px rgba(0,0,0,0.06); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 1000; } .brand{ display:flex; align-items:center; gap:10px; } .brand .logo{ width:42px;height:42px;border-radius:14px; background: linear-gradient(135deg,#ff5f88,#ffb7ce); box-shadow: 0 14px 35px rgba(255,95,136,0.25); } header h1 { margin: 0; font-size: 20px; color: #ff5f88; font-weight: 900; line-height: 1.1; } header small{ display:block; font-size:12px; color:#8a6f6a; font-weight:800; margin-top:2px; } nav{ display:flex; align-items:center; gap: 6px; } nav a { color: #5b4a47; text-decoration: none; font-weight: 900; cursor: pointer; padding: 10px 14px; border-radius: 14px; transition: 0.2s; position: relative; } nav a:hover { background: #ffe1ea; color: #ff5f88; } nav a.active{ background: #ffe1ea; color: #ff5f88; }

.container{
  max-width:1200px;
  margin:0 auto;
  padding:0 20px;
}

.page-head{
  text-align:center;
  margin-top: 34px;
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
  border-radius: 22px;
  padding: 18px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.08);
  border: 1px solid rgba(255,183,206,0.35);
  display:grid;
  grid-template-columns: 140px 1fr 260px;
  gap: 16px;
  align-items: start;
}

img.ref{
  width:140px;
  height:140px;
  object-fit:cover;
  border-radius: 18px;
  border:1px solid #ffe1ea;
}

.badge{
  display:inline-block;
  background: rgba(255,95,136,0.95);
  color:#fff;
  font-size: 12px;
  font-weight: 900;
  padding: 6px 10px;
  border-radius: 999px;
}

.info h3{
  margin: 6px 0 8px;
  font-size: 18px;
  font-weight: 900;
  color:#2f2321;
}

.grid{
  display:flex;
  flex-wrap:wrap;
  gap: 10px;
  margin-top: 10px;
  font-weight: 800;
  color:#7a5c57;
  font-size: 14px;
}
.grid div{
  background:#fff;
  border:1px solid #ffe1ea;
  padding: 8px 12px;
  border-radius: 14px;
}

.note{
  margin-top: 10px;
  background:#fff6fb;
  border:1px solid #ffd1de;
  padding: 12px;
  border-radius: 16px;
  font-weight: 800;
  color:#7a5c57;
  line-height: 1.6;
}

.right{
  display:flex;
  flex-direction:column;
  gap: 10px;
}

select{
  width:100%;
  padding: 12px 12px;
  border-radius: 16px;
  border:1px solid #ffd1de;
  outline:none;
  font-weight: 900;
}

.btn{
  width:100%;
  padding: 12px 14px;
  border-radius: 16px;
  font-weight: 900;
  border:none;
  cursor:pointer;
  transition:0.2s;
}

.btn-save{
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  color:#fff;
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
}
.btn-save:hover{ transform: translateY(-2px); }

.btn-chat{
  background: #fff;
  border: 1px solid rgba(255,95,136,0.25);
}
.btn-chat:hover{
  background:#ffe1ea;
  transform: translateY(-2px);
}

.small{
  font-size: 13px;
  font-weight: 800;
  color:#8a6f6a;
  line-height:1.7;
}


.badge-new{
  display:inline-block;
  margin-left:8px;
  background:#ff5f88;
  color:#fff;
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:900;
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
      <h1>👑 Admin • งานเค้กพิเศษ</h1>
      <small>จัดการออเดอร์ • แชทกับลูกค้า • อัปเดตสถานะ</small>
    </div>
  </div>

  <button class="menu-btn" onclick="toggleMenu()">☰</button>

  <nav id="navMenu">
    <a href="admin_transaction.php">📦 ออเดอร์</a>
    <a class="active" href="admin_custom_orders.php">🎂 เค้กพิเศษ</a>
    <a href="admin_products.php">🛍️ สินค้า</a>
    <a href="admin_income.php">📊 รายรับรายจ่าย</a>
    <a href="shop.php">กลับหน้าร้าน</a>
    <a href="logout.php" style="color:#dc3545;">ออกจากระบบ</a>
  </nav>
</header>



<div class="container">

  <div class="page-head">
    <h2>🎂 รายการเค้กพิเศษ (Custom)</h2>
    <p>กดเข้าแชทเพื่อคุยกับลูกค้า + เปลี่ยนสถานะงานได้เลย</p>
  </div>

  <div class="list">
    <?php foreach($orders as $o): ?>
      <div class="card">

        <?php if(!empty($o['reference_image'])): ?>
          <img class="ref" src="<?= e($o['reference_image']) ?>" alt="">
        <?php else: ?>
          <img class="ref" src="https://via.placeholder.com/140x140?text=Cake" alt="">
        <?php endif; ?>

        <div class="info">
          <span class="badge">งาน #<?= (int)$o['id'] ?></span>

          <h3>ลูกค้าเบอร์: <?= e($o['phone'] ?? '-') ?></h3>

          <div class="grid">
            <div>ขนาด: <?= e($o['cake_size']) ?></div>
            <div>รส: <?= e($o['cake_flavor']) ?></div>
            <div>ไส้: <?= e($o['cake_filling']) ?></div>
            <div>สีครีม: <?= e($o['cream_color']) ?></div>
            <div>ธีม: <?= e($o['theme']) ?></div>
            <div>วันรับ: <?= e($o['pickup_date']) ?></div>
          </div>

          <div class="note">
            <b>ข้อความบนหน้าเค้ก:</b><br>
            “<?= e($o['message_on_cake']) ?>”
            <br><br>
            <b>รายละเอียดเพิ่มเติม:</b><br>
            <?= nl2br(e($o['details'])) ?>

          </div>

          <div class="small">
            ส่งเมื่อ: <?= e($o['created_at']) ?>
          </div>
        </div>

        <div class="right">
          <form method="post">
            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">

            <select name="status">
              <?php
                $opts = ['รอตรวจสอบ','รับงานแล้ว','กำลังทำ','รอส่งมอบ','เสร็จแล้ว','ยกเลิก'];
                foreach($opts as $st):
              ?>
                <option value="<?= e($st) ?>" <?= ($o['status']==$st?'selected':'') ?>>
                  <?= e($st) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button class="btn btn-save" type="submit" name="update_status">
              💾 บันทึกสถานะ
            </button>
          </form>


          <a class="btn btn-chat" href="admin_custom_chat.php?id=<?= (int)$o['id'] ?>">
  💬 เข้าแชทกับลูกค้า
  <?php if(($o['unread_count'] ?? 0) > 0): ?>
    <span style="
      display:inline-block;
      margin-left:8px;
      background:#ff5f88;
      color:#fff;
      padding:4px 10px;
      border-radius:999px;
      font-size:12px;
      font-weight:900;
    ">
      <?= (int)$o['unread_count'] ?> ใหม่
    </span>
  <?php endif; ?>
</a>

        </div>

      </div>
    <?php endforeach; ?>
  </div>

</div>
<script>
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}
</script>
</body>
</html>
