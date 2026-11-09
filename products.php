<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

$user = $_SESSION['user'] ?? null;

/* เพิ่มสินค้าเข้าตะกร้า (บังคับ login) */
if (isset($_POST['add_to_cart'])) {

    if (!isset($_SESSION['user'])) {
        $_SESSION['redirect_after_login'] = "products.php";
        redirect("login.php");
        exit();
    }

    $id  = (int)$_POST['product_id'];
    $qty = max(1, (int)$_POST['qty']);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] += $qty;
    } else {
        $_SESSION['cart'][$id] = $qty;
    }

    redirect("products.php?added=1");
    exit();
}

/* ===== ดึงหมวดหมู่ ===== */
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* ===== รับค่าค้นหา + หมวด ===== */
$keyword = trim($_GET['search'] ?? "");
$cat_id  = (int)($_GET['cat'] ?? 0);

/* ===== ดึงสินค้าตามเงื่อนไข ===== */
$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1
";

$params = [];

if ($keyword !== "") {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$keyword%";
}

if ($cat_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_id;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>🍓 สินค้าทั้งหมด</title>
<?php include "includes/head.php"; ?>

<style>
*{ box-sizing:border-box; }

body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg, #fff6fb 0%, #fff 100%);
  color:#5b4a47;
}

.container{
  max-width:1200px;
  margin:0 auto;
  padding:0 20px;
}

/* ===== Page Title ===== */
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

/* ===== Filter Bar ===== */
.filter-bar{
  margin-top: 26px;
  display:grid;
  grid-template-columns: 1fr 220px 140px;
  gap: 12px;
  align-items:center;
}

.search-input, .cat-select{
  width:100%;
  padding: 14px 16px;
  border-radius: 999px;
  border: 1px solid #ffd1de;
  outline:none;
  font-size: 15px;
  background: rgba(255,255,255,0.92);
  box-shadow: 0 18px 40px rgba(0,0,0,0.08);
}

.search-input:focus, .cat-select:focus{
  border-color:#ff5f88;
  box-shadow: 0 0 0 5px rgba(255,95,136,0.15);
}

.filter-btn{
  padding: 14px 16px;
  border-radius: 999px;
  border:none;
  cursor:pointer;
  font-weight: 900;
  font-size: 15px;
  color:#fff;
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
  transition:0.2s;
}

.filter-btn:hover{
  transform: translateY(-2px);
}

/* ===== Product Grid ===== */
.products{
  margin-top: 26px;
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap:22px;
  padding-bottom: 70px;
  justify-content:center;
}

.item{
  background: rgba(255,255,255,0.90);
  border-radius: 22px;
  padding: 14px;
  box-shadow: 0 14px 35px rgba(0,0,0,0.07);
  border: 1px solid rgba(255,183,206,0.35);
  transition:0.22s;
  overflow:hidden;
  max-width: 320px;
  width:100%;
}

.item:hover{
  transform: translateY(-7px);
  box-shadow: 0 22px 55px rgba(0,0,0,0.12);
}

.badge{
  display:inline-block;
  background: rgba(255,95,136,0.95);
  color:#fff;
  font-size: 12px;
  font-weight: 900;
  padding: 6px 10px;
  border-radius: 999px;
  margin-bottom: 10px;
}

.item img{
  width:100%;
  height: 175px;
  object-fit:cover;
  border-radius: 18px;
  margin-bottom: 12px;
}

.item h3{
  margin: 6px 0 6px;
  font-size: 18px;
  font-weight: 900;
  color:#4b3b39;
}

.price{
  margin: 0 0 10px;
  color:#ff3f72;
  font-weight: 900;
  font-size: 16px;
}

.divider{
  height:1px;
  background:#ffe1ea;
  margin: 12px 0;
  border-radius: 999px;
}

.cart-row{
  display:flex;
  align-items:center;
  gap:10px;
}

.qty-input{
  width: 70px;
  padding: 9px;
  text-align:center;
  border:1px solid #ffd1de;
  border-radius: 14px;
  outline:none;
}

.add-cart-btn{
  flex:1;
  padding: 11px 12px;
  background: linear-gradient(90deg, #f77f9d, #f77f9d);
  color:#fff;
  border:none;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 900;
  cursor:pointer;
  box-shadow: 0 14px 28px rgba(255,95,136,0.25);
  transition:0.2s;
}

.add-cart-btn:hover{
  transform: translateY(-2px);
}

/* ===== Popup ===== */
.popup{
  position:fixed;
  top:50%;
  left:50%;
  transform: translate(-50%,-50%) scale(0);
  background:#fff;
  padding: 22px 36px;
  border-radius: 18px;
  text-align:center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
  transition:0.25s;
  z-index:9999;
}
.popup.show{
  transform: translate(-50%,-50%) scale(1);
}
.popup button{
  margin-top: 10px;
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  color:white;
  border:none;
  border-radius:999px;
  padding:10px 18px;
  font-weight:900;
  cursor:pointer;
}

/* ===== Responsive ===== */
html, body{
  scroll-behavior: auto !important;
}
/* Responsive */
@media(max-width: 980px){
  .review-grid{
    grid-template-columns: repeat(2, 1fr);
  }
}
@media(max-width: 520px){
  .review-grid{
    grid-template-columns: 1fr;
  }
  .reviews-head h2{
    font-size: 26px;
  }
}
@media (max-width: 520px){

  .container{
    padding: 0 14px;
  }

  header{
    padding: 12px 14px;
  }

  nav a{
    min-width: 100px;
    padding: 10px 10px;
    font-size: 13px;
  }

  .hero img{
    height: 320px;
  }

  .hero-text{
    padding: 18px 16px;
    border-radius: 22px;
    text-align: center;
  }

  .hero-text h1{
    font-size: 26px;
    line-height: 1.2;
  }

  .hero-text p{
    font-size: 14px;
  }

  .promo-grid{
    grid-template-columns: 1fr;
  }

  .item.recommended{
    min-width: 250px;
    max-width: 250px;
  }

  .reco-btn{
    display:none; /* ซ่อนปุ่มเลื่อนบนมือถือ */
  }
}

</style>
</head>

<body>
<?php include "includes/header.php"; ?>
<div class="container">

  <div class="page-head">
    <h2>🍓 สินค้าทั้งหมด</h2>
    <p>เลือกหมวดหมู่ + ค้นหา แล้วเพิ่มลงตะกร้าได้เลย 💗</p>
  </div>

  <!-- ===== Filter Form ===== -->
  <form method="get" class="filter-bar">
    <input class="search-input" type="text" name="search" value="<?= htmlspecialchars($keyword) ?>" placeholder="ค้นหาสินค้า เช่น เค้ก, คุกกี้, ขนมปัง...">

    <select class="cat-select" name="cat">
      <option value="0">📌 ทุกหมวดหมู่</option>
      <?php foreach($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($cat_id == $c['id']) ? "selected" : "" ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="filter-btn" type="submit">ค้นหา</button>
  </form>

  <!-- ===== Products ===== -->
  <div class="products">
    <?php if(count($products) == 0): ?>
      <div style="grid-column:1/-1;text-align:center;padding:40px;font-weight:900;color:#ff5f88;">
        😢 ไม่พบสินค้าที่คุณค้นหา
      </div>
    <?php endif; ?>

    <?php foreach($products as $p): ?>
      <div class="item">
        <span class="badge">
          <?= htmlspecialchars($p['category_name'] ?? "ไม่ระบุหมวด") ?>
        </span>

        <?php
$img = $p['image'] ?? '';

if($img){
  if(substr($img, 0, 8) === "uploads/"){

    $imgPath = $img;
  }else{
    if(file_exists("uploads/products/".$img)){
      $imgPath = "uploads/products/".$img;
    }else{
      $imgPath = "uploads/".$img;
    }
  }
}else{
  $imgPath = "";
}
?>

<?php
$img = $p['image'] ?? '';

if($img){
  // รองรับ PHP7/8
  if(substr($img, 0, 8) === "uploads/"){
    $imgPath = $img;
  }else{
    if(file_exists("uploads/products/".$img)){
      $imgPath = "uploads/products/".$img;
    }else{
      $imgPath = "uploads/".$img;
    }
  }
}else{
  $imgPath = "";
}
?>

<?php if($imgPath && file_exists($imgPath)): ?>
  <img src="<?= htmlspecialchars($imgPath) ?>" alt="">
<?php else: ?>
  <img src="uploads/no-image.png" alt="">
<?php endif; ?>


        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <p class="price">ราคา <?= number_format($p['price']) ?> บาท</p>

        <div class="divider"></div>

        <form method="post">
          <input type="hidden" name="add_to_cart" value="1">
          <input type="hidden" name="product_id" value="<?= $p['id']; ?>">

          <div class="cart-row">
            <input type="number" name="qty" id="qty-<?= $p['id'] ?>" value="1" min="1" class="qty-input">

            <button type="submit" class="add-cart-btn">🧺 เพิ่มลงตะกร้า</button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script>
function toggleMenu(){
  document.getElementById("mainMenu").classList.toggle("show");
}

function scrollReco(direction){
  const row = document.getElementById("recoRow");
  row.scrollBy({ left: direction * 320, behavior: "smooth" });
}

function openLogoutPopup(){
  window.location.href = "includes/logout.php";
}

/* ===== Modal ===== */
function openModal(name, price, img){
  document.getElementById("mName").innerText = name;
  document.getElementById("mPrice").innerText = "ราคา " + price + " บาท";
  document.getElementById("mImg").src = img;
  document.getElementById("productModal").classList.add("show");
}

function closeModal(e){
  if(!e || e.target.id === "productModal"){
    document.getElementById("productModal").classList.remove("show");
  }
}

function saveScroll(){
  localStorage.setItem("scrollY", window.scrollY);
  localStorage.setItem("restoreScroll", "1");
}

window.addEventListener("load", function(){
  const ok = localStorage.getItem("restoreScroll");
  const y = localStorage.getItem("scrollY");

  if(ok === "1" && y){
    document.documentElement.style.scrollBehavior = "auto";
    window.scrollTo(0, parseInt(y));

    setTimeout(() => {
      document.documentElement.style.scrollBehavior = "smooth";
    }, 50);

    localStorage.removeItem("restoreScroll");
    localStorage.removeItem("scrollY");
  }
});
</script>

</body>
</html>
