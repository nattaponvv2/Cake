<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit;
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* ===== ลบสินค้า ===== */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    // ดึงรูปเก่ามาลบด้วย (ถ้ามี)
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if($p){
        if(!empty($p['image'])){
    $file = $p['image'];

    if(!str_starts_with($file, "uploads/")){
        $file = "uploads/products/" . $file;
    }

    if(file_exists($file)){
        unlink($file);
    }
}



        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);
    }

    header("Location: admin_products.php");
    exit;
}

/* ===== ดึงสินค้า ===== */
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin • จัดการสินค้า</title>
<?php include "includes/head.php"; ?>


<style>
  nav{
  display:none;
}

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
  display:flex;
  justify-content:space-between;
  align-items:center;
  box-shadow:0 10px 25px rgba(0,0,0,0.06);
  backdrop-filter: blur(10px);
  position: sticky;
  top:0;
  z-index:1000;
}
.brand{ display:flex; align-items:center; gap:10px; }
.brand .logo{
  width:42px;height:42px;border-radius:14px;
  background: linear-gradient(135deg,#ff5f88,#ffb7ce);
  box-shadow: 0 14px 35px rgba(255,95,136,0.25);
}
header h1{
  margin:0;
  font-size:20px;
  color:#ff5f88;
  font-weight:900;
  line-height:1.1;
}
header small{
  display:block;
  font-size:12px;
  color:#8a6f6a;
  font-weight:800;
  margin-top:2px;
}
nav{ display:flex; align-items:center; gap:6px; }
nav a{
  color:#5b4a47;
  text-decoration:none;
  font-weight:900;
  padding:10px 14px;
  border-radius:14px;
  transition:0.2s;
}
nav a:hover{ background:#ffe1ea; color:#ff5f88; }
nav a.active{ background:#ffe1ea; color:#ff5f88; }



/* ===== Layout ===== */
.container{ max-width:1250px; margin: 24px auto; padding: 0 16px 60px; }
.title{ text-align:center; margin: 16px 0 20px; }
.title h2{ margin:0; font-size:26px; font-weight:900; color:#ff4f7b; }
.title p{ margin:8px 0 0; font-weight:800; color:#8a6f6a; font-size:13px; }

.card{
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(255,183,206,0.35);
  border-radius: 22px;
  box-shadow: 0 18px 60px rgba(0,0,0,0.08);
  padding: 18px;
}

.btn{
  display:inline-block;
  padding: 10px 16px;
  border-radius: 999px;
  border:none;
  cursor:pointer;
  font-weight: 900;
  text-decoration:none;
  transition:.2s;
}
.btn-add{
  background: linear-gradient(90deg,#ff5f88,#ff87a0);
  color:#fff;
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
}
.btn-add:hover{ transform: translateY(-2px); }
.btn-del{
  background:#ffecec;
  color:#b00020;
}
.btn-edit{
  background:#eef6ff;
  color:#1c4aa8;
}

.table-wrap{
  margin-top: 14px;
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
}
td{
  padding: 10px 10px;
  border-bottom: 1px solid #f2e4ea;
  text-align:center;
  font-size: 13px;
}
tr:hover td{ background:#fff1f6; }

.img{
  width:60px;height:60px;
  object-fit:cover;
  border-radius: 14px;
  border: 1px solid rgba(255,183,206,0.5);
  box-shadow: 0 10px 22px rgba(0,0,0,0.08);
}
/* =========================
   ✅ STANDARD MOBILE NAV
   (ใช้ได้ทุกหน้า Admin)
========================= */

/* ปุ่ม 3 ขีด */
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

/* Desktop */
@media (min-width: 901px){
  nav{
    display:flex !important;
    flex-direction: row;
    gap: 6px;
    position: static;
    background: transparent;
    box-shadow: none;
    padding: 0;
  }
  .menu-btn{
    display:none !important;
  }
}

/* Mobile */
@media (max-width: 900px){

  header{
    padding: 14px 16px;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    position: sticky;
    top: 0;
    width: 100%;
  }

  /* โลโก้ + ชื่ออยู่ซ้าย */
  .brand{
    flex:1;
  }

  /* ปุ่ม 3 ขีดอยู่ขวา */
  .menu-btn{
    display:flex !important;
    align-items:center;
    justify-content:center;
    position: static;
  }

  /* เมนูเริ่มต้นซ่อน */
  nav{
    display:none !important;
    position:absolute;
    top: 100%;
    left: 16px;
    right: 16px;
    background:#fff;
    border-radius:18px;
    padding:12px;
    box-shadow:0 18px 50px rgba(0,0,0,0.12);
    flex-direction: column;
    gap:10px;
    z-index:9999;
  }

  /* เมื่อกดแล้วให้โชว์ */
  nav.show{
    display:flex !important;
  }

  nav a{
    width:100%;
    text-align:center;
    padding: 14px;
    border-radius:14px;
    font-size:14px;
  }
}


</style>
</head>
<body>

<div class="top-banner">
  🧁 จัดการสินค้า • เพิ่ม/แก้ไข/ลบ ได้เลย ไม่ต้องเข้า phpMyAdmin 💗
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin • สินค้า</h1>
      <small>เพิ่มสินค้า • สต๊อก • หมวดหมู่ • รูปสินค้า</small>
    </div>
  </div>
<button class="menu-btn" onclick="toggleMenu()">☰</button>
<nav id="navMenu">
    <a href="admin_transaction.php">📦 ออเดอร์</a>
    <a href="admin_custom_orders.php">🎂 เค้กพิเศษ</a>
    <a class="active" href="admin_products.php">🛍️ สินค้า</a>
    <a href="admin_income.php">📊 รายรับรายจ่าย</a>
    <a href="shop.php">กลับหน้าร้าน</a>
    <a href="includes/logout.php" style="color:#dc3545;">ออกจากระบบ</a>
  </nav>
</header>

<div class="container">
  <div class="title">
    <h2>🛍️ จัดการสินค้า</h2>
    <p>แอดมินสามารถเพิ่มสินค้าใหม่ แก้ไขราคา สต๊อก และลบสินค้าได้เลย</p>
  </div>

  <div class="card">
    <a class="btn btn-add" href="admin_product_add.php">➕ เพิ่มสินค้าใหม่</a>

    <div class="table-wrap">
      <table>
        <tr>
          <th>ID</th>
          <th>รูป</th>
          <th>ชื่อสินค้า</th>
          <th>หมวดหมู่</th>
          <th>ราคา</th>
          <th>สต๊อก</th>
          <th>จัดการ</th>
        </tr>

  

<?php foreach($products as $p): ?>
<tr>
  <td><?= (int)$p['id'] ?></td>

  <td>
    <?php
      $img = $p['image'] ?? '';

if($img){
    $img = trim($img);

    // ถ้าเป็นลิงก์เต็ม
    if(preg_match('/^https?:\/\//i', $img)){
        // ใช้ได้เลย
    }
    // ถ้าเริ่มด้วย uploads/ อยู่แล้ว
    else if(strpos($img, "uploads/") === 0){
        // ใช้ได้เลย
    }
    // ถ้าเป็นชื่อไฟล์ล้วน
    else{
        $try1 = "uploads/products/" . $img;
        if(file_exists($try1)){
            $img = $try1;
        }else{
            $img = "uploads/" . $img;
        }
    }
}

    ?>

    <?php if(!empty($img)): ?>
      <img class="img" src="<?= e($img) ?>">
    <?php else: ?>
      <span>-</span>
    <?php endif; ?>
  </td>

  <td><?= e($p['name']) ?></td>
  <td><?= e($p['category'] ?? '-') ?></td>
  <td><?= number_format((float)$p['price'],2) ?></td>
  <td><?= (int)$p['stock'] ?></td>

  <td>
    <a class="btn btn-edit" href="admin_product_edit.php?id=<?= (int)$p['id'] ?>">✏️ แก้ไข</a>
    <a class="btn btn-del"
       href="admin_products.php?delete=<?= (int)$p['id'] ?>"
       onclick="return confirm('แน่ใจนะว่าจะลบสินค้านี้?')">🗑️ ลบ</a>
  </td>
</tr>
<?php endforeach; ?>


      </table>
    </div>
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
