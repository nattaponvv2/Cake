<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

$user = $_SESSION['user'] ?? null;

// ลบสินค้า
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    redirect("cart.php");
}

// ถ้ากดยืนยันสั่งซื้อ แต่ยังไม่ login -> ไป login ก่อน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = "cart.php";
        redirect("login.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ตะกร้าสินค้า</title>
<?php include "includes/head.php"; ?>

<style>
*{ box-sizing:border-box; }

body{
    margin:0;
    font-family:"Prompt", sans-serif;
    background: linear-gradient(180deg, #fff6fb 0%, #fff 100%);
    color:#5b4a47;
}

.container{
    max-width: 1050px;
    margin: 0 auto;
    padding: 40px 18px;
}

.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin-bottom: 18px;
}

h1{
    margin:0;
    color:#ff5f88;
    font-size: 28px;
    font-weight: 900;
}

.back{
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    color:white;
    background: linear-gradient(90deg, #ff5f88, #ff87a0);
    padding:10px 16px;
    border-radius:999px;
    font-weight:900;
    box-shadow:0 16px 30px rgba(255,95,136,0.25);
    transition:.2s;
    white-space: nowrap;
}

.back:hover{ transform: translateY(-2px); }

.card{
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(255,183,206,0.35);
    border-radius: 22px;
    box-shadow: 0 18px 50px rgba(0,0,0,0.08);
    padding: 18px;
    overflow:hidden;
}

.table-wrap{
    width:100%;
    overflow:auto;
    border-radius: 18px;
    border: 1px solid #ffe1ea;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width: 720px;
    background:white;
}

th, td{
    padding: 14px 16px;
    border-bottom:1px solid #f3f3f3;
    text-align:left;
}

th{
    background: linear-gradient(90deg, #ff5f88, #ffb7ce);
    color:white;
    font-weight: 900;
    letter-spacing: 0.2px;
}

tr:last-child td{ border-bottom:none; }

td:nth-child(2),
td:nth-child(3),
td:nth-child(4){
    text-align:center;
    font-weight:700;
}

.product-name{
    font-weight:900;
    color:#4b3b39;
}

.remove{
    display:inline-flex;
    justify-content:center;
    align-items:center;
    width:38px;
    height:38px;
    border-radius: 14px;
    background:#fff0f4;
    border: 1px solid #ffd1de;
    color:#ff3f72;
    text-decoration:none;
    font-size:18px;
    transition:.2s;
}

.remove:hover{
    transform: translateY(-2px);
    background:#ffe1ea;
}

.total-box{
    margin-top: 16px;
    padding: 14px 16px;
    border-radius: 18px;
    background: #fff6fb;
    border: 1px solid #ffe1ea;
    font-size: 18px;
    font-weight: 900;
    color:#ff3f72;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
}

.total-box span:last-child{
    font-size: 20px;
}

.pay-box{
    margin-top: 16px;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid #ffe1ea;
    background: rgba(255,255,255,0.85);
}

.pay-title{
    margin:0 0 10px;
    font-size: 16px;
    font-weight: 900;
    color:#ff5f88;
}

.pay-options{
    display:flex;
    gap:14px;
    flex-wrap: wrap;
}

.pay-option{
    flex:1;
    min-width: 240px;
    padding: 14px 14px;
    border-radius: 18px;
    border: 1px solid #ffd1de;
    background:#fff;
    display:flex;
    gap:10px;
    align-items:flex-start;
    cursor:pointer;
    transition:.2s;
}

.pay-option:hover{
    transform: translateY(-2px);
    box-shadow: 0 18px 45px rgba(0,0,0,0.08);
}

.pay-option input{
    margin-top: 4px;
    transform: scale(1.1);
    accent-color: #ff5f88;
}

.pay-option b{
    display:block;
    font-size: 15px;
    color:#4b3b39;
}

.pay-option small{
    display:block;
    margin-top: 2px;
    color:#8a6f6a;
    font-weight: 600;
}

.qr-box{
    display:none;
    margin-top: 14px;
    padding: 16px;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #ffd1de;
}

.qr-box h3{
    margin:0 0 8px;
    color:#ff5f88;
    font-weight: 900;
}

.qr-box img{
    width: 220px;
    max-width: 100%;
    border-radius: 18px;
    border: 1px solid #ffe1ea;
    box-shadow: 0 16px 40px rgba(0,0,0,0.10);
}

.file-input{
    width: 100%;
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 16px;
    border: 1px solid #ffd1de;
    background: #fff6fb;
    outline:none;
}

.actions{
    margin-top: 18px;
    display:flex;
    justify-content:flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

button{
    border:none;
    cursor:pointer;
    font-family:"Prompt", sans-serif;
    font-weight: 900;
    border-radius: 999px;
    padding: 12px 18px;
    transition:.2s;
}

.btn-paid{
    background: linear-gradient(90deg, #ff5f88, #ff87a0);
    color:white;
    box-shadow:0 16px 30px rgba(255,95,136,0.25);
}

.btn-paid:hover{ transform: translateY(-2px); }

.btn-confirm{
    background: #2f2a29;
    color:white;
    padding: 12px 22px;
    box-shadow:0 16px 35px rgba(0,0,0,0.18);
}

.btn-confirm:hover{ transform: translateY(-2px); }

.btn-confirm:disabled{
    background: #c7b9b7;
    cursor:not-allowed;
    box-shadow:none;
}

.empty{
    text-align:center;
    padding: 60px 10px;
    color:#8a6f6a;
}

.empty h2{
    margin:0 0 8px;
    color:#ff5f88;
    font-weight: 900;
}

@media (max-width: 720px){
    .header{
        flex-direction: column;
        align-items:flex-start;
    }
    h1{ font-size: 24px; }
    .actions{
        justify-content: stretch;
    }
    .btn-confirm{
        width:100%;
        text-align:center;
    }
}
</style>
</head>

<body>
<?php include "includes/header.php"; ?>
  </nav>
</header>

<div class="container">

    <div class="header">
        <h1>🧺 ตะกร้าสินค้าของคุณ</h1>
        <a class="back" href="shop.php">← กลับไปหน้าร้าน</a>
    </div>

    <ol class="checkout-steps" aria-label="ขั้นตอนสั่งซื้อ">
        <li class="current"><span>1</span> ตะกร้าสินค้า</li>
        <li><span>2</span> ชำระเงิน</li>
        <li><span>3</span> ยืนยันคำสั่งซื้อ</li>
    </ol>

    <?php if (empty($_SESSION['cart'])): ?>

        <div class="card empty">
            <h2>ยังไม่มีสินค้าในตะกร้า 😢</h2>
            <p>กลับไปเลือกขนมอร่อยๆ ในหน้าร้านได้เลย 💗</p>
        </div>

    <?php else: ?>

        <div class="card">

            <form method="post" action="checkout.php" enctype="multipart/form-data">

                <input type="hidden" name="paid" id="paidField" value="0">

                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>สินค้า</th>
                            <th style="text-align:center;">จำนวน</th>
                            <th style="text-align:center;">ราคา/ชิ้น</th>
                            <th style="text-align:center;">รวม</th>
                            <th style="text-align:center;">ลบ</th>
                        </tr>

                        <?php
                        $total = 0;

                        foreach ($_SESSION['cart'] as $id => $qty) {
                            $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
                            $stmt->execute([$id]);
                            $p = $stmt->fetch(PDO::FETCH_ASSOC);

                            if(!$p) continue;

                            $sum = $p['price'] * $qty;
                            $total += $sum;
                        ?>
                        <tr>
                            <td class="product-name"><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= (int)$qty ?></td>
                            <td><?= number_format($p['price']) ?></td>
                            <td><?= number_format($sum) ?></td>
                            <td style="text-align:center;">
                                <a class="remove" href="?remove=<?= (int)$id ?>" title="ลบสินค้า">✕</a>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>

                <div class="total-box">
                    <span>ยอดรวมทั้งหมด</span>
                    <span><?= number_format($total) ?> บาท</span>
                </div>

                <div class="pay-box">
                    <p class="pay-title">เลือกวิธีชำระเงิน</p>

                    <div class="pay-options">

                        <label class="pay-option">
                            <input type="radio" name="method" id="promptpay" value="พร้อมเพย์" required>
                            <div>
                                <b>พร้อมเพย์</b>
                                <small>สแกน QR แล้วอัปโหลดสลิป</small>
                            </div>
                        </label>

                        <label class="pay-option">
                            <input type="radio" name="method" id="cod" value="เก็บเงินปลายทาง">
                            <div>
                                <b>เก็บเงินปลายทาง</b>
                                <small>จ่ายเงินตอนรับสินค้า</small>
                            </div>
                        </label>

                    </div>

                    <div class="qr-box" id="qrBox">
                        <h3>สแกนเพื่อชำระเงิน</h3>

                        <!-- ❗ แก้ path รูป QR ของคุณตรงนี้ -->
                        <img src="uploads/promptpay_qr.jpg" alt="PromptPay QR">

                        <div style="margin-top:14px; font-weight:900; color:#4b3b39;">
                            📤 อัปโหลดสลิป
                        </div>

                        <input class="file-input" type="file" name="slip" id="slipInput" accept="image/*">

                        <div style="margin-top:12px;">
                            <button type="button" class="btn-paid" onclick="confirmPaid()">
                                ฉันชำระเงินแล้ว
                            </button>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <button id="confirmBtn" class="btn-confirm" type="submit" disabled>
                        ✅ ยืนยันการสั่งซื้อ
                    </button>
                </div>

            </form>

        </div>

    <?php endif; ?>

</div>

<script>
    function toggleMenu(){
  document.getElementById("mainMenu").classList.toggle("show");
}

const promptpay = document.getElementById("promptpay");
const cod = document.getElementById("cod");
const qrBox = document.getElementById("qrBox");
const confirmBtn = document.getElementById("confirmBtn");
const paidField = document.getElementById("paidField");
const slipInput = document.getElementById("slipInput");

if(promptpay){
    promptpay.addEventListener("change", () => {
        qrBox.style.display = "block";
        confirmBtn.disabled = true;
        paidField.value = "0";
    });
}

if(cod){
    cod.addEventListener("change", () => {
        qrBox.style.display = "none";
        confirmBtn.disabled = false;
        paidField.value = "0";
    });
}

function confirmPaid(){

    if(!slipInput.files.length){
        alert("กรุณาอัปโหลดสลิปก่อน ❌");
        return;
    }

    alert("อัปโหลดสลิปเรียบร้อย ✅ รอร้านตรวจสอบ");

    paidField.value = "1";
    confirmBtn.disabled = false;
}
</script>

</body>
</html>
