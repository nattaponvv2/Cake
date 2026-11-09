<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit;
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$error = "";
$success = "";

$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

/* ===== บันทึกสินค้า ===== */
if(isset($_POST['save'])){
    $name = trim($_POST['name'] ?? "");
    $category = trim($_POST['category'] ?? "");
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $desc = trim($_POST['description'] ?? "");

    if($name==="" || $category==="" || $price<=0){
        $error = "กรุณากรอกชื่อสินค้า หมวดหมู่ และราคาที่ถูกต้อง";
    }else{

        // ===== Upload image =====
        $imgPath = "";
        if(!empty($_FILES['image']['name'])){
            $folder = "uploads/products/";
            if(!is_dir($folder)){
                mkdir($folder, 0777, true);
            }

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName = "p_".time()."_".rand(1000,9999).".".$ext;
            $target = $folder.$newName;

            if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
                $imgPath = $newName;

            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO products (name, category, price, stock, description, image)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category, $price, $stock, $desc, $imgPath]);

        header("Location: admin_products.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin • เพิ่มสินค้า</title>
<?php include "includes/admin_style.php"; ?>


<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}
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


.container{ max-width:1100px; margin: 24px auto; padding: 0 16px 60px; }
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

.form{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
@media(max-width:800px){
  .form{ grid-template-columns: 1fr; }
}

.field label{
  display:block;
  font-weight:900;
  margin-bottom:6px;
  color:#6f5a56;
}
.field input, .field select, .field textarea{
  width:100%;
  padding: 12px 14px;
  border-radius: 16px;
  border: 1px solid rgba(255,183,206,0.55);
  outline:none;
  font-family:"Prompt",sans-serif;
  font-weight:800;
}
.field textarea{ min-height: 110px; resize: vertical; }

.actions{
  margin-top: 14px;
  display:flex;
  gap: 10px;
  flex-wrap: wrap;
}
.btn{
  padding: 12px 18px;
  border-radius: 999px;
  border:none;
  cursor:pointer;
  font-weight: 900;
  text-decoration:none;
  transition:.2s;
}
.btn-save{
  background: linear-gradient(90deg,#ff5f88,#ff87a0);
  color:#fff;
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
}
.btn-save:hover{ transform: translateY(-2px); }
.btn-back{
  background:#fff;
  border:1px solid rgba(255,183,206,0.55);
  color:#ff4f7b;
}
.alert{
  padding: 12px 14px;
  border-radius: 16px;
  font-weight: 900;
  margin-bottom: 12px;
}
.alert-error{ background:#ffecec; color:#b00020; border:1px solid #ffc9d2; }
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

nav{
  display:flex;
  align-items:center;
  gap:6px;
}

nav a{
  color:#5b4a47;
  text-decoration:none;
  font-weight:900;
  padding:10px 14px;
  border-radius:14px;
  transition:0.2s;
}

nav a:hover{
  background:#ffe1ea;
  color:#ff5f88;
}

nav a.active{
  background:#ffe1ea;
  color:#ff5f88;
}

/* =========================
   ✅ STANDARD MOBILE NAV
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
  }

  .brand{
    flex:1;
  }

  .menu-btn{
    display:flex !important;
    align-items:center;
    justify-content:center;
    position: static;
  }

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
  🛍️ เพิ่มสินค้าใหม่ • แอดมินจัดการได้เอง 💖
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin • เพิ่มสินค้า</h1>
      <small>เพิ่มชื่อ • ราคา • สต๊อก • หมวดหมู่ • รูป</small>
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
    <h2>➕ เพิ่มสินค้าใหม่</h2>
    <p>กรอกข้อมูลสินค้า แล้วกดบันทึกได้เลย</p>
  </div>

  <div class="card">

    <?php if($error): ?>
      <div class="alert alert-error">❌ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="form">

        <div class="field">
          <label>ชื่อสินค้า</label>
          <input type="text" name="name" placeholder="เช่น Nike Air Max 97" required>
        </div>

        <div class="field">
          <label>หมวดหมู่</label>
          <select name="category" required>
  <option value="">-- เลือกหมวดหมู่ --</option>
  <?php foreach($categories as $c): ?>
    <option value="<?= e($c['name']) ?>"><?= e($c['name']) ?></option>
  <?php endforeach; ?>
</select>


        </div>

        <div class="field">
          <label>ราคา (บาท)</label>
          <input type="number" step="0.01" name="price" required>
        </div>

        <div class="field">
          <label>สต๊อก (จำนวน)</label>
          <input type="number" name="stock" value="0">
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>รายละเอียดสินค้า</label>
          <textarea name="description" placeholder="ใส่รายละเอียดสินค้า..."></textarea>
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>รูปสินค้า (ไม่บังคับ)</label>
          <input type="file" name="image" accept="image/*">
        </div>

      </div>

      <div class="actions">
        <button class="btn btn-save" type="submit" name="save">💾 บันทึกสินค้า</button>
        <a class="btn btn-back" href="admin_products.php">⬅ กลับหน้ารายการสินค้า</a>
      </div>
    </form>

  </div>
</div>
<script>
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}
</script>
</body>
</html>
