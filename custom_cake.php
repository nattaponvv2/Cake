<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = "custom_cake.php";
    redirect("login.php");
    exit();
}

$user = $_SESSION['user'];
$success = "";
$error = "";

if(isset($_POST['submit_custom'])){

    $cake_size = trim($_POST['cake_size'] ?? "");
    $cake_flavor = trim($_POST['cake_flavor'] ?? "");
    $cake_filling = trim($_POST['cake_filling'] ?? "");
    $cream_color = trim($_POST['cream_color'] ?? "");
    $theme = trim($_POST['theme'] ?? "");
    $message_on_cake = trim($_POST['message_on_cake'] ?? "");
    $pickup_date = trim($_POST['pickup_date'] ?? "");
    $details = trim($_POST['details'] ?? "");

    if($cake_size=="" || $cake_flavor=="" || $cake_filling=="" || $cream_color=="" || $theme=="" || $message_on_cake=="" || $pickup_date==""){
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่องที่จำเป็น";
    } else {

        $ref_image = null;

        // ===== อัปโหลดรูปตัวอย่าง =====
        if(isset($_FILES['reference_image']) && $_FILES['reference_image']['error'] == 0){

            $folder = "uploads/custom/";
            if(!is_dir($folder)){
                mkdir($folder, 0777, true);
            }

            $ext = pathinfo($_FILES['reference_image']['name'], PATHINFO_EXTENSION);
            $newName = "custom_" . time() . "_" . rand(1000,9999) . "." . $ext;

            $target = $folder . $newName;
            move_uploaded_file($_FILES['reference_image']['tmp_name'], $target);

            $ref_image = $target;
        }

        // ===== บันทึกลง DB =====
        $stmt = $pdo->prepare("
            INSERT INTO custom_orders
            (user_id, cake_size, cake_flavor, cake_filling, cream_color, theme, message_on_cake, pickup_date, details, reference_image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user['id'],
            $cake_size,
            $cake_flavor,
            $cake_filling,
            $cream_color,
            $theme,
            $message_on_cake,
            $pickup_date,
            $details,
            $ref_image
        ]);

        $orderId = $pdo->lastInsertId();

        // ===== เพิ่มข้อความเริ่มต้น (ลูกค้า) =====
        $stmt2 = $pdo->prepare("
            INSERT INTO custom_messages (custom_order_id, sender, message)
            VALUES (?, 'customer', ?)
        ");
        $stmt2->execute([$orderId, "ส่งคำสั่งเค้กพิเศษแล้ว รอร้านตอบกลับค่ะ/ครับ 💖"]);

        $success = "ส่งรีเควสเค้กพิเศษสำเร็จแล้ว 🎂✨";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>🎂 สั่งเค้กพิเศษ</title>
<?php include "includes/head.php"; ?>

<style>
*{box-sizing:border-box;}
body{
  margin:0;
  font-family:"Prompt",sans-serif;
  background: linear-gradient(180deg,#fff6fb 0%, #fff 100%);
  color:#5b4a47;
}

/* ===== NAV ===== */
nav{
  display:flex;
  align-items:center;
  gap:6px;
}

nav a{
  color:#5b4a47;
  text-decoration:none;
  font-weight:800;
  padding:10px 14px;
  border-radius:999px;
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

/* ===== MAIN ===== */
.container{
  max-width:1100px;
  margin:0 auto;
  padding:0 20px;
}

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

.form-card{
  margin-top: 26px;
  background: rgba(255,255,255,0.92);
  border-radius: 26px;
  padding: 26px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.08);
  border: 1px solid rgba(255,183,206,0.35);
}

.grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

label{
  font-weight:900;
  display:block;
  margin-bottom:6px;
  color:#4b3b39;
}

input, select, textarea{
  width:100%;
  padding: 13px 16px;
  border-radius: 18px;
  border:1px solid #ffd1de;
  outline:none;
  font-size: 15px;
  background:#fff;
}

textarea{
  min-height:110px;
  resize: vertical;
}

input:focus, select:focus, textarea:focus{
  border-color:#ff5f88;
  box-shadow: 0 0 0 5px rgba(255,95,136,0.15);
}

.full{ grid-column: 1 / -1; }

.btn{
  width:100%;
  padding: 14px 16px;
  border-radius: 999px;
  border:none;
  cursor:pointer;
  font-weight: 900;
  font-size: 16px;
  color:#fff;
  background: linear-gradient(90deg, #ff5f88, #ff87a0);
  box-shadow: 0 16px 30px rgba(255,95,136,0.25);
  transition:0.2s;
}

.btn:hover{ transform: translateY(-2px); }

.alert-success{
  background:#e9fff1;
  border:1px solid #b9f2cc;
  padding:12px 14px;
  border-radius:16px;
  font-weight:900;
  color:#157a3b;
  margin-bottom: 12px;
}

.alert-error{
  background:#fff0f4;
  border:1px solid #ffd1de;
  padding:12px 14px;
  border-radius:16px;
  font-weight:900;
  color:#c12a4a;
  margin-bottom: 12px;
}

.preview{
  margin-top: 18px;
  padding: 18px;
  border-radius: 22px;
  border: 1px dashed #ffb7ce;
  background: #fff;
}

.preview h3{
  margin:0 0 10px;
  color:#ff5f88;
  font-weight:900;
}
</style>
</head>

<body>
  <?php include "includes/header.php"; ?>

<div class="container">

  <div class="page-head">
    <h2>🎂 สั่งเค้กพิเศษ</h2>
    <p>เลือกเองได้หมด! แล้วร้านจะตอบกลับในระบบ 💖</p>
  </div>

  <div class="form-card">

    <?php if($success): ?>
      <div class="alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if($error): ?>
      <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="grid">

        <div>
          <label>ขนาดเค้ก *</label>
          <select name="cake_size" required>
            <option value="">-- เลือกขนาด --</option>
            <option>1 ปอนด์</option>
            <option>2 ปอนด์</option>
            <option>3 ปอนด์</option>
            <option>4 ปอนด์</option>
          </select>
        </div>

        <div>
          <label>รสเค้ก *</label>
          <select name="cake_flavor" required>
            <option value="">-- เลือกรส --</option>
            <option>วานิลลา</option>
            <option>ช็อกโกแลต</option>
            <option>สตรอว์เบอร์รี่</option>
            <option>ชาไทย</option>
            <option>กาแฟ</option>
          </select>
        </div>

        <div>
          <label>ไส้เค้ก *</label>
          <select name="cake_filling" required>
            <option value="">-- เลือกไส้ --</option>
            <option>ครีมสด</option>
            <option>คัสตาร์ด</option>
            <option>ช็อกโกแลต</option>
            <option>สตรอว์เบอร์รี่</option>
            <option>บลูเบอร์รี่</option>
          </select>
        </div>

        <div>
          <label>สีครีม *</label>
          <select name="cream_color" required>
            <option value="">-- เลือกสี --</option>
            <option>ขาว</option>
            <option>ชมพูพาสเทล</option>
            <option>ฟ้าอ่อน</option>
            <option>ครีม</option>
            <option>ม่วงลาเวนเดอร์</option>
          </select>
        </div>

        <div>
          <label>ธีม *</label>
          <select name="theme" required>
            <option value="">-- เลือกธีม --</option>
            <option>มินิมอล</option>
            <option>วันเกิด</option>
            <option>วาเลนไทน์</option>
            <option>การ์ตูน</option>
            <option>ดอกไม้</option>
          </select>
        </div>

        <div>
          <label>วันรับเค้ก *</label>
          <input type="date" name="pickup_date" required>
        </div>

        <div class="full">
          <label>ข้อความบนหน้าเค้ก *</label>
          <input type="text" name="message_on_cake" placeholder="เช่น Happy Birthday Nattapon 🎉" required>
        </div>

        <div class="full">
          <label>รายละเอียดเพิ่มเติม</label>
          <textarea name="details" placeholder="เช่น ขอหวานน้อย / ขอแต่งหน้าด้วยสตรอว์เบอร์รี่ / ไม่ใส่ถั่ว..."></textarea>
        </div>

        <div class="full">
          <label>แนบรูปตัวอย่าง (ถ้ามี)</label>
          <input type="file" name="reference_image" accept="image/*">
        </div>

        <div class="full">
          <button class="btn" type="submit" name="submit_custom">💌 ส่งรีเควสเค้กพิเศษ</button>
        </div>

      </div>
    </form>

    <div class="preview">
      <h3>💡 หลังส่งแล้วจะเกิดอะไรขึ้น?</h3>
      <div style="font-weight:800;color:#7a5c57;line-height:1.7">
        1) ระบบบันทึกคำสั่งทำเค้กของคุณ<br>
        2) ร้านจะตอบกลับพร้อมราคา/ยืนยันวันรับเค้ก<br>
        3) คุณดูสถานะได้ที่หน้า <b>งานของฉัน</b>
      </div>
    </div>

  </div>

</div>

</body>
</html>
