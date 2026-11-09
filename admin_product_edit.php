<?php
session_start();
require_once "includes/db.php";
// ดึงหมวดหมู่จากฐานข้อมูล
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit;
}

function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);


$stmt = $pdo->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
$p['category'] = $p['category'] ?? '';

if(!$p){
    die("ไม่พบสินค้า");
}

$error = "";

/* ===== อัปเดตสินค้า ===== */
if(isset($_POST['update'])){
    $name = trim($_POST['name'] ?? "");
    $category = trim($_POST['category'] ?? "");
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $desc = trim($_POST['description'] ?? "");

    if($name==="" || $category==="" || $price<=0){
        $error = "กรุณากรอกชื่อสินค้า หมวดหมู่ และราคาที่ถูกต้อง";
    }else{

        $imgPath = $p['image'] ?? "";

        // ถ้าอัปโหลดรูปใหม่
        if(!empty($_FILES['image']['name'])){
            $folder = "uploads/products/";
            if(!is_dir($folder)){
                mkdir($folder, 0777, true);
            }

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName = "p_".time()."_".rand(1000,9999).".".$ext;
            $target = $folder.$newName;

            if(move_uploaded_file($_FILES['image']['tmp_name'], $target)){
                // ลบรูปเก่า
                // ลบรูปเก่า
if(!empty($imgPath)){
    $old = $imgPath;

    if(!str_starts_with($old, "uploads/")){
        $try1 = "uploads/products/" . $old;

        if(file_exists($try1)){
            $old = $try1;
        }else{
            $old = "uploads/" . $old;
        }
    }

    if(file_exists($old)){
        unlink($old);
    }
}


                $imgPath = $target;
            }
        }

        $stmt = $pdo->prepare("
            UPDATE products
            SET name=?, category=?, price=?, stock=?, description=?, image=?
            WHERE id=?
        ");
        $stmt->execute([$name, $category, $price, $stock, $desc, $imgPath, $id]);

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
<title>Admin • แก้ไขสินค้า</title>
<?php include "includes/head.php"; ?>

<style>
  nav{
  display:none;
}

nav.show{
  display:flex;
}

*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}
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

.preview{
  display:flex;
  gap:12px;
  align-items:center;
  margin-top: 8px;
}
.preview img{
  width:72px;height:72px;
  object-fit:cover;
  border-radius: 18px;
  border: 1px solid rgba(255,183,206,0.5);
  box-shadow: 0 12px 28px rgba(0,0,0,0.08);
}

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
@media (min-width: 901px){
  nav{
    display:flex;
    flex-direction: row;
  }
  .menu-btn{
    display:none;
  }
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
  ✏️ แก้ไขสินค้า • อัปเดตราคา/สต๊อก ได้ทันที 💖
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin • แก้ไขสินค้า</h1>
      <small>แก้ไขข้อมูลสินค้า + เปลี่ยนรูปได้</small>
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
    <h2>✏️ แก้ไขสินค้า #<?= (int)$p['id'] ?></h2>
    <p>แก้ไขข้อมูลแล้วกดบันทึกได้เลย</p>
  </div>

  <div class="card">

    <?php if($error): ?>
      <div class="alert alert-error">❌ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="form">

        <div class="field">
          <label>ชื่อสินค้า</label>
          <input type="text" name="name" value="<?= e($p['name']) ?>" required>
        </div>

        <div class="field">
          <label>หมวดหมู่</label>
          <select name="category" required>
  <option value="">-- เลือกหมวดหมู่ --</option>

  <?php foreach($categories as $c): ?>
    <option value="<?= e($c['name']) ?>"
      <?= (($p['category'] ?? '') == $c['name']) ? 'selected' : '' ?>>
      <?= e($c['name']) ?>
    </option>
  <?php endforeach; ?>
</select>
</div>



        <div class="field">
          <label>ราคา (บาท)</label>
          <input type="number" step="0.01" name="price" value="<?= e($p['price']) ?>" required>
        </div>

        <div class="field">
          <label>สต๊อก (จำนวน)</label>
          <input type="number" name="stock" value="<?= (int)$p['stock'] ?>">
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>รายละเอียดสินค้า</label>
          <textarea name="description"><?= e($p['description']) ?></textarea>
        </div>

        <div class="field" style="grid-column:1/-1;">
          <label>รูปสินค้า (อัปโหลดใหม่ได้)</label>
          <input type="file" name="image" accept="image/*">

         <div class="preview">
  <b>รูปปัจจุบัน:</b>

  <?php if(!empty($p['image'])): ?>
    <?php
      $img = $p['image'];

      // ถ้า DB เก็บแค่ชื่อไฟล์ เช่น cake1.jpg
      if(!str_starts_with($img, "uploads/")){
          $try1 = "uploads/products/" . $img;

          if(file_exists($try1)){
              $img = $try1;
          }else{
              $img = "uploads/" . $img;
          }
      }
    ?>
    <img src="<?= e($img) ?>" alt="รูปสินค้า">
  <?php else: ?>
    <span>- ไม่มีรูป -</span>
  <?php endif; ?>
</div>

        </div>

      </div>

      <div class="actions">
        <button class="btn btn-save" type="submit" name="update">💾 บันทึกการแก้ไข</button>
        <a class="btn btn-back" href="admin_products.php">⬅ กลับหน้ารายการสินค้า</a>
      </div>
    </form>

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
