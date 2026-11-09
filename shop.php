<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

/* เพิ่มสินค้าเข้าตะกร้า (บังคับ login) */
if (isset($_POST['add_to_cart'])) {

    if (!isset($_SESSION['user'])) {
        $_SESSION['redirect_after_login'] = "shop.php";
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

    redirect("shop.php?added=1");

    exit();
}

$user = $_SESSION['user'] ?? null;

/* ดึงสินค้า */
$products = $pdo->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);

/* ไม่สุ่มแล้ว: เอา 4 ตัวแรก */
$recommended = array_slice($products, 0, 4);

function e($s){
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>🍰 ร้านอบอุ่นเบเกอรี่</title>
<?php include "includes/head.php"; ?>
<style>
*{ box-sizing:border-box; }
html{ scroll-behavior: smooth; }

body {
  margin: 0;
  font-family: "Prompt", sans-serif;
  background: radial-gradient(circle at 20% 0%, #ffe7f0 0%, #fff 35%, #fff 100%);
  color: #3a2f2d;
}

.container{
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* ===== Hero ===== */
.hero {
  position: relative;
  overflow: hidden;
  border-bottom-left-radius: 38px;
  border-bottom-right-radius: 38px;
}

.hero img {
  width: 100%;
  height: 480px;
  object-fit: cover;
  filter: brightness(0.78);
  transform: scale(1.03);
}

.hero::after{
  content:"";
  position:absolute;
  inset:0;
  background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.12) 0%, rgba(0,0,0,0.55) 65%);
}

.hero-text {
  position: absolute;
  top: 50%;
  left: 50%;
  width: min(780px, 92%);
  transform: translate(-50%, -50%);
  background: rgba(255, 255, 255, 0.12);
  padding: 34px 38px;
  border-radius: 30px;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.35);
  z-index: 2;
  text-align:left;
}

.hero-text h1 {
  font-size: 48px;
  color: white;
  margin: 0;
  font-weight: 900;
  text-shadow: 0 10px 30px rgba(0,0,0,0.35);
  letter-spacing: -0.5px;
}

.hero-text p {
  color: rgba(255,255,255,0.92);
  font-size: 18px;
  margin: 12px 0 0;
  font-weight:700;
}

.hero-actions{
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  margin-top: 18px;
}

.hero-actions a {
  display: inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding: 12px 22px;
  border-radius: 999px;
  text-decoration: none;
  font-weight: 900;
  transition: 0.2s;
}

.btn-primary{
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  color: white;
  box-shadow: 0 16px 30px rgba(255,95,136,0.35);
}
.btn-primary:hover{
  transform: translateY(-2px);
}

.btn-ghost{
  background: rgba(255,255,255,0.18);
  color:#fff;
  border:1px solid rgba(255,255,255,0.35);
}
.btn-ghost:hover{
  transform: translateY(-2px);
  background: rgba(255,255,255,0.25);
}

/* ===== Promo ===== */
.promo-grid{
  margin-top: 20px;
  display:grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.promo-card{
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(255,183,206,0.35);
  border-radius: 24px;
  padding: 18px;
  box-shadow: 0 16px 40px rgba(0,0,0,0.06);
  transition: .22s;
  position: relative;
  overflow:hidden;
}

.promo-card::before{
  content:"";
  position:absolute;
  inset:-40px;
  background: radial-gradient(circle at 30% 20%, rgba(255,95,136,0.14) 0%, transparent 55%);
  transform: rotate(10deg);
}

.promo-card:hover{
  transform: translateY(-6px);
  box-shadow: 0 22px 55px rgba(0,0,0,0.10);
}

.promo-title{
  font-weight: 900;
  color:#ff5f88;
  font-size: 14px;
  position: relative;
  z-index: 2;
}
.promo-big{
  margin-top: 8px;
  font-size: 20px;
  font-weight: 900;
  color:#2f2321;
  position: relative;
  z-index: 2;
}
.promo-sub{
  margin-top: 6px;
  font-size: 13px;
  font-weight: 700;
  color:#8a6f6a;
  position: relative;
  z-index: 2;
}

/* ===== Titles ===== */
.section-title {
  text-align: center;
  margin-top: 52px;
  margin-bottom: 10px;
  color: #ff5f88;
  font-size: 30px;
  font-weight: 900;
}
.section-subtitle {
  text-align: center;
  margin-top: 0;
  margin-bottom: 22px;
  color: #8a6f6a;
  font-size: 15px;
  font-weight:700;
}

/* ===== Recommended Slider ===== */
.reco-wrap{
  position: relative;
}

.reco-row{
  display:flex;
  gap: 18px;
  overflow-x: auto;
  padding: 6px 6px 24px;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
}

.reco-row::-webkit-scrollbar{
  height: 10px;
}
.reco-row::-webkit-scrollbar-thumb{
  background: #ffd1de;
  border-radius: 999px;
}
.reco-row::-webkit-scrollbar-track{
  background: rgba(255,255,255,0.6);
  border-radius: 999px;
}

.reco-btn{
  position:absolute;
  top: 38%;
  transform: translateY(-50%);
  border:none;
  cursor:pointer;
  font-weight: 900;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(255,255,255,0.92);
  box-shadow: 0 18px 40px rgba(0,0,0,0.12);
  transition: .2s;
  z-index: 50;
}

.reco-btn:hover{
  transform: translateY(-50%) scale(1.05);
}

.reco-btn.left{ left: -6px; }
.reco-btn.right{ right: -6px; }

/* ===== Product Card (Premium) ===== */
.item {
  position: relative;
  background: rgba(255,255,255,0.92);
  border-radius: 26px;
  padding: 14px;
  box-shadow: 0 14px 35px rgba(0,0,0,0.07);
  border: 1px solid rgba(255, 183, 206, 0.35);
  text-align: left;
  transition: 0.22s;
  overflow: hidden;
  width: 100%;
}

.item:hover {
  transform: translateY(-8px);
  box-shadow: 0 26px 65px rgba(0,0,0,0.12);
}

.item.recommended{
  scroll-snap-align: start;
  min-width: 290px;
  max-width: 290px;
  border: 2px solid rgba(255,95,136,0.25);
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,246,251,0.9));
}

.badge{
  position:absolute;
  top:12px;
  left:12px;
  color:#fff;
  font-size:12px;
  font-weight:900;
  padding:6px 10px;
  border-radius:999px;
  box-shadow:0 10px 18px rgba(0,0,0,0.12);
  z-index: 5;
}

.badge.recommend{ background: rgba(255,95,136,0.95); }
.badge.new{ background: rgba(34,197,94,0.95); }
.badge.hot{ background: rgba(255,140,0,0.95); }

.item img {
  width: 100%;
  height: 185px;
  object-fit: cover;
  border-radius: 20px;
  margin-bottom: 12px;
  transition: .25s ease;
}

.item:hover img{
  transform: scale(1.03);
}

.item h3 {
  margin: 6px 0 6px;
  font-size: 18px;
  font-weight: 900;
  color: #2f2321;
}

.price {
  margin: 0 0 10px;
  color: #ff3f72;
  font-weight: 900;
  font-size: 16px;
}

.meta{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-bottom: 10px;
}
.chip{
  font-size:12px;
  font-weight:900;
  padding:6px 10px;
  border-radius:999px;
  background:#fff;
  border:1px solid rgba(255,183,206,0.55);
  color:#8a6f6a;
}

.divider{
  height:1px;
  background: #ffe1ea;
  margin: 12px 0;
  border-radius: 999px;
}

/* ===== Buttons ===== */
.cart-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.qty-input {
  width: 70px;
  padding: 10px;
  text-align: center;
  border: 1px solid #ffd1de;
  border-radius: 14px;
  font-size: 14px;
  outline: none;
  background: #fff;
  font-weight:800;
}

.qty-input:focus {
  border-color: #ff5f88;
  box-shadow: 0 0 0 4px rgba(255,95,136,0.18);
}

.add-cart-btn {
  flex: 1;
  padding: 12px 12px;
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  color: #fff;
  border: none;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 900;
  cursor: pointer;
  transition: 0.2s;
  box-shadow: 0 14px 28px rgba(255,95,136,0.25);
}

.add-cart-btn:hover {
  transform: translateY(-2px);
  filter: brightness(0.98);
}
.add-cart-btn:active{
  transform: translateY(1px);
}

/* Quick view button */
.quick-btn{
  width:100%;
  margin-top:10px;
  padding: 11px 12px;
  border-radius: 16px;
  border: 1px solid rgba(255,95,136,0.25);
  background: rgba(255,255,255,0.75);
  font-weight: 900;
  cursor:pointer;
  transition:.2s;
}
.quick-btn:hover{
  background:#ffe1ea;
  transform: translateY(-2px);
}

/* ===== Popup ===== */
.modal{
  position: fixed;
  inset:0;
  background: rgba(0,0,0,0.45);
  display:none;
  align-items:center;
  justify-content:center;
  z-index: 9999;
  padding: 18px;
}

.modal.show{ display:flex; }

.modal-box{
  width: min(720px, 96%);
  background: #fff;
  border-radius: 26px;
  padding: 18px;
  box-shadow: 0 30px 90px rgba(0,0,0,0.25);
  position: relative;
  overflow:hidden;
}

.modal-close{
  position:absolute;
  top:12px;
  right:12px;
  border:none;
  cursor:pointer;
  font-weight:900;
  border-radius:999px;
  width:42px;height:42px;
  background:#ffe1ea;
}

.modal-grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:16px;
}

.modal-grid img{
  width:100%;
  height: 260px;
  object-fit: cover;
  border-radius: 22px;
}

.modal-title{
  font-size: 22px;
  font-weight: 900;
  margin: 0;
  color:#2f2321;
}
.modal-price{
  margin-top: 10px;
  font-size: 18px;
  font-weight: 900;
  color:#ff3f72;
}
.modal-desc{
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color:#8a6f6a;
  line-height: 1.7;
}



/* ===== Contact Footer ===== */
.contact-footer{
  background: #ffffff;
  padding: 70px 20px 30px;
  border-top: 1px solid #eee;
  text-align: center;
  margin-top: 60px;
}

.contact-footer .footer-box{
  max-width: 900px;
  margin: 0 auto;
}

.contact-footer h2{
  font-size: 34px;
  font-weight: 900;
  margin-bottom: 18px;
  color: #222;
}

.contact-footer .info{
  font-size: 16px;
  line-height: 1.9;
  color: #222;
  font-weight: 600;
}

.contact-footer .socials{
  margin-top: 18px;
  display: flex;
  justify-content: center;
  gap: 14px;
  flex-wrap: wrap;
}
.contact-footer .socials a{
  width: 52px;
  height: 52px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 20px;
  font-weight: 900;
  color: #fff;
  box-shadow: 0 14px 30px rgba(0,0,0,0.10);
  transition: 0.2s;
}
.contact-footer .socials a:hover{
  transform: translateY(-4px);
  filter: brightness(0.95);
}
.social-facebook{ background:#1877F2; }
.social-x{ background:#111; }
.social-ig{ background: radial-gradient(circle at 30% 30%, #feda75, #fa7e1e, #d62976, #962fbf, #4f5bd5); }
.social-youtube{ background:#FF0000; }
.social-line{ background:#00C300; }
/* ===== Reviews Section ===== */
.reviews{
  margin-top: 60px;
  padding: 55px 0;
  background: rgba(255,255,255,0.75);
  border-radius: 34px;
  border: 1px solid rgba(255,183,206,0.35);
  box-shadow: 0 18px 55px rgba(0,0,0,0.06);
  position: relative;
  overflow:hidden;
}

.reviews::before{
  content:"";
  position:absolute;
  inset:-80px;
  background: radial-gradient(circle at 20% 30%, rgba(255,95,136,0.12) 0%, transparent 55%);
  transform: rotate(10deg);
}

.reviews-head{
  text-align:center;
  position: relative;
  z-index: 2;
}

.reviews-head h2{
  margin:0;
  font-size: 34px;
  font-weight: 900;
  color:#2f2321;
}

.reviews-head p{
  margin: 8px 0 0;
  font-size: 15px;
  font-weight: 800;
  color:#8a6f6a;
}

.review-grid{
  margin-top: 32px;
  display:grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  position: relative;
  z-index: 2;
}

.review-card{
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(255,183,206,0.35);
  border-radius: 26px;
  padding: 18px 16px;
  box-shadow: 0 14px 35px rgba(0,0,0,0.06);
  transition: .22s;
  text-align:center;
}

.review-card:hover{
  transform: translateY(-6px);
  box-shadow: 0 24px 60px rgba(0,0,0,0.10);
}

.avatar{
  width: 108px;
  height: 108px;
  border-radius: 999px;
  margin: 0 auto 14px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size: 44px;
  font-weight: 900;
  color:#2f2321;
  border: 8px solid #fff;
  box-shadow: 0 18px 40px rgba(0,0,0,0.08);
}

.av1{ background: linear-gradient(135deg, #b9f2ff, #e9fbff); }
.av2{ background: linear-gradient(135deg, #ffe28a, #fff6d4); }
.av3{ background: linear-gradient(135deg, #bff7c9, #eaffef); }
.av4{ background: linear-gradient(135deg, #ffb6c5, #ffe2ea); }

.review-text{
  margin:0;
  font-size: 14px;
  line-height: 1.75;
  color:#3a2f2d;
  font-weight: 700;
}

.review-name{
  margin-top: 12px;
  font-size: 13px;
  font-weight: 900;
  color:#ff5f88;
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

<section class="hero">
  <img src="uploads/vvv.png" alt="">
  <div class="hero-text">
    <h1>สินค้าใหม่มาแล้ว 🎉</h1>
    <p>เลือกขนมที่คุณชอบ แล้วเพิ่มลงตะกร้าได้เลย 💗</p>

    <div class="hero-actions">
      <a class="btn-primary" href="products.php">🧁 ดูสินค้าทั้งหมด</a>
      <a class="btn-ghost" href="#contact">📍 ติดต่อร้าน</a>
    </div>
  </div>
</section>

<div class="container">

  <!-- Promo -->
  <div class="promo-grid">
    <div class="promo-card">
      <div class="promo-title">🎁 โปรวันนี้</div>
      <div class="promo-big">ซื้อครบ 300 ส่งฟรี</div>
      <div class="promo-sub">เฉพาะพื้นที่นครปฐมเท่านั้น</div>
    </div>

    <div class="promo-card">
      <div class="promo-title">💖 สมาชิก</div>
      <div class="promo-big">รับส่วนลด 5%</div>
      <div class="promo-sub">เมื่อเข้าสู่ระบบและสั่งครั้งแรก</div>
    </div>

    <div class="promo-card">
      <div class="promo-title">🍓 ของใหม่</div>
      <div class="promo-big">NEW MENU</div>
      <div class="promo-sub">มีของใหม่ทุกสัปดาห์</div>
    </div>
  </div>

  <!-- Recommended -->
  <h2 class="section-title">⭐ สินค้าแนะนำ</h2>
  <p class="section-subtitle">ขายดีและลูกค้าชอบมากที่สุด 💗 (เลื่อนซ้าย-ขวาได้)</p>

  <div class="reco-wrap">
    <button class="reco-btn left" onclick="scrollReco(-1)">◀</button>
    <button class="reco-btn right" onclick="scrollReco(1)">▶</button>

    <div class="reco-row" id="recoRow">
      <?php foreach ($recommended as $index => $p): ?>

        <?php
          // ทำ badge แบบไม่ต้องแก้ฐานข้อมูล
          $badgeType = ($index==0) ? "hot" : (($index==1) ? "new" : "recommend");
          $badgeText = ($badgeType=="hot") ? "🔥 HOT" : (($badgeType=="new") ? "✨ NEW" : "⭐ RECOMMEND");
        ?>

        <div class="item recommended">

          <span class="badge <?= $badgeType ?>"><?= $badgeText ?></span>

          <?php
$img = $p['image'] ?? '';
if($img && !str_starts_with($img, "uploads/")){
    $try1 = "uploads/products/" . $img;
    if(file_exists($try1)){
        $img = $try1;
    }else{
        $img = "uploads/" . $img;
    }
}
?>
<img src="<?= e($img ?: 'uploads/no-image.png') ?>" alt="">

          <h3><?= e($p['name']) ?></h3>

          <div class="meta">
            <span class="chip">อบใหม่</span>
            <span class="chip">หวานกำลังดี</span>
            <span class="chip">ทำสดทุกวัน</span>
          </div>

          <p class="price">ราคา <?= number_format($p['price']) ?> บาท</p>

          <div class="divider"></div>

          <form method="post" class="cart-box" onsubmit="saveScroll();">
  <input type="hidden" name="add_to_cart" value="1">
  <input type="hidden" name="product_id" value="<?= $p['id']; ?>">

  <div class="cart-row">
    <input type="number" name="qty" value="1" min="1" class="qty-input">
    <button type="submit" class="add-cart-btn">🧺 เพิ่มลงตะกร้า</button>
  </div>
</form>


          <button class="quick-btn"
  onclick="openModal(
    '<?= e($p['name']) ?>',
    '<?= number_format($p['price']) ?>',
    '<?= e($img ?: "uploads/no-image.png") ?>'
  )">
  👀 ดูรายละเอียด
</button>


        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- ===== Modal ===== -->
<div class="modal" id="productModal" onclick="closeModal(event)">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal()">✕</button>

    <div class="modal-grid">
      <img id="mImg" src="" alt="">
      <div>
        <h3 class="modal-title" id="mName"></h3>
        <div class="modal-price" id="mPrice"></div>
        <div class="modal-desc">
          ขนมทำสดใหม่ทุกวัน 💖<br>
          ใช้วัตถุดิบคุณภาพดี หอม นุ่ม อร่อย<br><br>
          📌 แนะนำ: ทานคู่กับกาแฟ/ชา จะฟินมาก!
        </div>
      </div>
    </div>
  </div>
</div>
<!-- ===== Reviews ===== -->
<div class="container">
  <section class="reviews">
    <div class="reviews-head">
      <h2>รีวิวความประทับใจของลูกค้า</h2>
      <p>ถึง ร้านอบอุ่นเบเกอรี่ 💖</p>
    </div>

    <div class="review-grid">

      <div class="review-card">
        <div class="avatar av1">🐻</div>
        <p class="review-text">
          “ขนมนุ่มมาก หอมเนยสุดๆ สั่งครั้งแรกติดใจเลยค่ะ  
          แพ็คของดี ส่งไวมาก ประทับใจสุดๆ 💗”
        </p>
        <div class="review-name">— คุณมิน</div>
      </div>

      <div class="review-card">
        <div class="avatar av2">🧁</div>
        <p class="review-text">
          “หวานกำลังดี ไม่เลี่ยนเลยค่ะ  
          คุกกี้กรอบนอกนุ่มใน กินกับนมคือฟินมากกก 😭✨”
        </p>
        <div class="review-name">— คุณพีท</div>
      </div>

      <div class="review-card">
        <div class="avatar av3">🍓</div>
        <p class="review-text">
          “เค้กนุ่มมาก ไส้แน่นมากกก  
          เหมาะซื้อเป็นของฝากสุดๆ ร้านนี้คุณภาพเกินราคา 💕”
        </p>
        <div class="review-name">— คุณน้ำ</div>
      </div>

      <div class="review-card">
        <div class="avatar av4">🍰</div>
        <p class="review-text">
          “เป็นร้านประจำไปแล้วค่ะ  
          ของใหม่มาเรื่อยๆ น่ารักทุกเมนู  
          เด็กๆที่บ้านชอบมากค่ะ 🥹💖”
        </p>
        <div class="review-name">— คุณฝน</div>
      </div>

    </div>
  </section>
</div>

<section class="contact-footer" id="contact">
  <div class="footer-box">

    <h2>ติดต่อเรา</h2>

    <div class="info">
      บจก.อบอุ่น (สาขา 00001) 77 ซอยจระเข้ 99<br>
      ถนน นพพระเก้า 77 แขวงสวนเพลิง เขตสวนน้ำ<br>
      นครปฐม 77777<br><br> 

      <b>เบอร์โทร</b> : 099-336-084x<br>
      <b>Facebook</b> : Bake to Happy<br>
      <b>Line ID</b> : @bakehappy
    </div>

    <div class="socials">
      <a class="social-facebook" href="#" title="Facebook">f</a>
      <a class="social-x" href="#" title="X">X</a>
      <a class="social-ig" href="#" title="Instagram">◎</a>
      <a class="social-youtube" href="#" title="YouTube">▶</a>
      <a class="social-line" href="#" title="LINE">LINE</a>
    </div>

  </div>
</section>

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
