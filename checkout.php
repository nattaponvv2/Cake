<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

$user = $_SESSION['user'] ?? null;

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = "cart.php";
    redirect("login.php");
    exit();
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect("cart.php");
    exit();
}

if (!isset($_POST['method'])) {
    redirect("cart.php");
    exit();
}

if ($_POST['method'] === 'พร้อมเพย์' && ($_POST['paid'] ?? '0') != '1') {
    die("กรุณาชำระเงินก่อนยืนยันคำสั่งซื้อ");
}

$payment_method = $_POST['method'];
$total = 0;
$slip_path = null;

$payment_status = ($payment_method == 'พร้อมเพย์') ? 'รอตรวจสอบสลิป' : 'เก็บเงินปลายทาง';
$customer_name = trim(($user['first_name'] ?? '') . " " . ($user['last_name'] ?? ''));

# ================== อัปโหลดสลิป ==================
if ($payment_method == 'พร้อมเพย์') {

    if (!isset($_FILES['slip']) || $_FILES['slip']['error'] != 0) {
        die("กรุณาอัปโหลดสลิป");
    }

    $folder = "uploads/slips/";
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
    $new_name = "slip_" . time() . "." . $ext;
    $target = $folder . $new_name;

    move_uploaded_file($_FILES['slip']['tmp_name'], $target);

    $slip_path = $target;
}

# ================== คำนวณราคา ==================
foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id=?");
    $stmt->execute([$id]);
    $price = $stmt->fetchColumn();
    $total += $price * $qty;
}

# ================== บันทึก orders ==================
$stmt = $pdo->prepare("
    INSERT INTO orders 
    (user_id, customer_name, phone, address, total, payment_method, status, slip_image, payment_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $user['id'],
    $customer_name,
    $user['phone'] ?? '',
    $user['address'] ?? '',
    $total,
    $payment_method,
    'Pending Payment',
    $slip_path,
    $payment_status
]);

$order_id = $pdo->lastInsertId();

# ================== บันทึก order_items ==================
foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$order_id, $id, $qty]);
}

unset($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">

<!-- ⭐ สำคัญมาก ถ้าไม่มี responsive จะพัง -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>สั่งซื้อสำเร็จ</title>


<style>
*{ box-sizing:border-box; }

body{
  margin:0;
  font-family:"Prompt", sans-serif;
  background: linear-gradient(180deg, #fff6fb 0%, #fff 100%);
  color:#5b4a47;
}

/* ===== Top Banner ===== */
.top-banner{
  background: linear-gradient(90deg, #ff5f88, #ffb7ce);
  color: #fff;
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
  width:42px;
  height:42px;
  border-radius:14px;
  background: linear-gradient(135deg,#ff5f88,#ffb7ce);
  box-shadow: 0 14px 35px rgba(255,95,136,0.25);
}

header h1{
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
}

nav a{
  color: #5b4a47;
  text-decoration: none;
  font-weight: 900;
  padding: 10px 14px;
  border-radius: 14px;
  transition: 0.2s;
}

nav a:hover{
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
  }

  .brand{
    width:100%;
  }

  .menu-btn{
    display:block;
    position:absolute;
    right: 16px;
    top: 70px;
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

/* ===== Success Card ===== */
.container{
  max-width: 900px;
  margin: 0 auto;
  padding: 60px 18px;
}

.card{
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(255,183,206,0.35);
  border-radius: 26px;
  box-shadow: 0 18px 60px rgba(0,0,0,0.10);
  padding: 28px 22px;
  text-align:center;
}

.badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width: 72px;
  height: 72px;
  border-radius: 999px;
  background: linear-gradient(90deg, #ff5f88, #ffb7ce);
  color:white;
  font-size: 34px;
  box-shadow:0 18px 35px rgba(255,95,136,0.25);
  margin-bottom: 14px;
}

.card h2{
  margin: 0;
  color:#ff5f88;
  font-weight: 900;
  font-size: 28px;
}

.sub{
  margin: 10px 0 0;
  color:#8a6f6a;
  font-weight: 600;
}

.summary{
  margin-top: 18px;
  padding: 16px;
  border-radius: 20px;
  background: #fff6fb;
  border: 1px solid #ffe1ea;
  text-align:left;
  max-width: 520px;
  margin-left:auto;
  margin-right:auto;
}

.row{
  display:flex;
  justify-content:space-between;
  gap: 14px;
  padding: 10px 8px;
  border-bottom: 1px dashed #ffd1de;
  font-weight: 700;
}

.row:last-child{ border-bottom:none; }

.row span:first-child{
  color:#8a6f6a;
  font-weight: 800;
}

.row span:last-child{
  color:#4b3b39;
  font-weight: 900;
}

.total{
  font-size: 18px;
  color:#ff3f72 !important;
}

.actions{
  margin-top: 22px;
  display:flex;
  justify-content:center;
  gap: 10px;
  flex-wrap: wrap;
}

.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding: 12px 18px;
  border-radius: 999px;
  font-weight: 900;
  text-decoration:none;
  transition:.2s;
}

.btn-primary{
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  color:white;
  box-shadow:0 16px 30px rgba(255,95,136,0.25);
}

.btn-primary:hover{ transform: translateY(-2px); }

.btn-dark{
  background:#2f2a29;
  color:white;
  box-shadow:0 16px 35px rgba(0,0,0,0.18);
}

.btn-dark:hover{ transform: translateY(-2px); }

.note{
  margin-top: 14px;
  font-size: 13px;
  color:#8a6f6a;
}
</style>
</head>

<body>

<div class="top-banner">
  💖 ซื้อครบ 300 บาท ส่งฟรีทั่วนครปฐม 💖
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>🍰 ร้านอบอุ่นเบเกอรี่</h1>
      <small>Warm Bakery • Fresh Everyday</small>
    </div>
  </div>

  <button class="menu-btn" onclick="toggleMenu()">☰</button>

  <nav id="mainMenu">
    <a href="shop.php">หน้าร้าน</a>
    <a href="products.php">สินค้า</a>

    <?php $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
    <a href="cart.php">ตะกร้า (<?= $cartCount ?>)</a>

    <a href="my_custom_orders.php">สั่งเค้กพิเศษ</a>

    <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
      <a href="admin_transaction.php">จัดการร้าน</a>
      <a href="admin_products.php">🛍️ สินค้า</a>
    <?php endif; ?>

    <?php if($user): ?>
      <a href="includes/logout.php">ออกจากระบบ</a>
    <?php else: ?>
      <a href="login.php">เข้าสู่ระบบ</a>
    <?php endif; ?>
  </nav>
</header>

<div class="container">
  <div class="card">

    <div class="badge">✓</div>

    <h2>สั่งซื้อสำเร็จแล้ว 🎉</h2>
    <p class="sub">ขอบคุณที่อุดหนุนร้านอบอุ่นเบเกอรี่ 💗</p>

    <div class="summary">
      <div class="row">
        <span>วิธีชำระเงิน</span>
        <span><?= htmlspecialchars($payment_method) ?></span>
      </div>

      <div class="row">
        <span>สถานะการชำระเงิน</span>
        <span><?= htmlspecialchars($payment_status) ?></span>
      </div>

      <div class="row">
        <span>เลขออเดอร์</span>
        <span>#<?= (int)$order_id ?></span>
      </div>

      <div class="row">
        <span>ยอดรวมทั้งหมด</span>
        <span class="total"><?= number_format($total) ?> บาท</span>
      </div>
    </div>

    <div class="actions">
      <a class="btn btn-primary" href="shop.php">🛍️ กลับไปหน้าร้าน</a>
      <a class="btn btn-dark" href="cart.php">🧺 ดูตะกร้า</a>
    </div>

    <div class="note">
      * หากชำระแบบพร้อมเพย์ ระบบจะรอร้านตรวจสอบสลิปก่อนจัดส่ง
    </div>

  </div>
</div>

<script>
function toggleMenu(){
  document.getElementById("mainMenu").classList.toggle("show");
}
</script>

</body>
</html>
