<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/functions.php";

$user = $_SESSION['user'] ?? null;
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

$current = basename($_SERVER['PHP_SELF']);
function activeMenu($file, $current){
    return ($file === $current) ? 'active' : '';
}
?>

<style>
*{ box-sizing:border-box; }
body{
  margin:0;
  font-family:"Prompt", sans-serif;
  background: linear-gradient(180deg, #fff6fb 0%, #fff 100%);
  color:#5b4a47;
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

<div class="top-banner">
  💖 ซื้อครบ 300 บาท ส่งฟรีทั่วนครปฐม 💖
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>ร้านอบอุ่นเบเกอรี่</h1>
      <small>Warm Bakery • Fresh Everyday</small>
    </div>
  </div>

  <button class="menu-btn" onclick="toggleMenu()">☰</button>

  <nav id="mainMenu">
    <a class="<?= activeMenu('shop.php',$current) ?>" href="shop.php">หน้าร้าน</a>
    <a class="<?= activeMenu('products.php',$current) ?>" href="products.php">สินค้า</a>
    <a class="<?= activeMenu('cart.php',$current) ?>" href="cart.php">ตะกร้า (<?= $cartCount ?>)</a>

    <a class="<?= activeMenu('custom_cake.php',$current) ?>" href="custom_cake.php">สั่งเค้กพิเศษ</a>
    <a class="<?= activeMenu('my_custom_orders.php',$current) ?>" href="my_custom_orders.php">งานของฉัน</a>

    <?php if ($user && ($user['role'] ?? '') === 'admin'): ?>
      <a class="<?= activeMenu('admin_transaction.php',$current) ?>" href="admin_transaction.php">จัดการร้าน</a>
      <a class="<?= activeMenu('admin_products.php',$current) ?>" href="admin_products.php">🛍️ สินค้า</a>
    <?php endif; ?>

    <?php if($user): ?>
      <a href="logout.php">ออกจากระบบ</a>
    <?php else: ?>
      <a class="<?= activeMenu('login.php',$current) ?>" href="login.php">เข้าสู่ระบบ</a>
    <?php endif; ?>
  </nav>
</header>

<script>
function toggleMenu(){
  document.getElementById("mainMenu").classList.toggle("show");
}
</script>
