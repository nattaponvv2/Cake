<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

/* ================================
   Redirect ถ้า login แล้ว
================================ */
if (isset($_SESSION['user'])) {
    redirect($_SESSION['user']['role'] === 'admin' ? "admin_transaction.php" : "shop.php");
    exit();
}

$error = null;
$success = null;

/* ================================
   REGISTER
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'register') {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $address    = trim($_POST['address'] ?? '');

    if (!$first_name || !$last_name || !$phone || !$password || !$address) {
        $error = "❌ กรุณากรอกข้อมูลให้ครบ";
    } else {

        // เช็คเบอร์ซ้ำ
        $check = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $check->execute([$phone]);

        if ($check->fetch()) {
            $error = "❌ เบอร์โทรนี้ถูกใช้งานแล้ว";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, phone, password, address, role)
                VALUES (?, ?, ?, ?, ?, 'user')
            ");

            $stmt->execute([$first_name, $last_name, $phone, $hash, $address]);

            $success = "✅ สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
        }
    }
}

/* ================================
   LOGIN
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'login') {

    $phone = trim($_POST['phone'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if (!$phone || !$pass) {
        $error = "❌ กรุณากรอกข้อมูลให้ครบ";
    } else {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            $storedPassword = $user['password'];
            $loginSuccess = false;

            // รหัสยังไม่ hash
            if (!preg_match('/^\$2y\$/', $storedPassword)) {
                if ($pass === $storedPassword) {
                    $newHash = password_hash($pass, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                        ->execute([$newHash, $user['id']]);
                    $storedPassword = $newHash;
                    $loginSuccess = true;
                }
            } else {
                if (password_verify($pass, $storedPassword)) {
                    $loginSuccess = true;

                    if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($pass, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                            ->execute([$newHash, $user['id']]);
                    }
                }
            }

            if ($loginSuccess) {

                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'phone' => $user['phone'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role']
                ];

                redirect($user['role'] === 'admin' ? "admin_transaction.php" : "shop.php");
                exit();

            } else {
                $error = "❌ รหัสผ่านไม่ถูกต้อง";
            }

        } else {
            $error = "❌ ไม่พบเบอร์โทรศัพท์นี้ในระบบ";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ร้านอบอุ่นเบเกอรี่</title>
<?php include "includes/head.php"; ?>
<style>
*{ box-sizing:border-box; }

body{
    margin:0;
    font-family:"Prompt", sans-serif;
    background: linear-gradient(180deg, #fff6fb 0%, #fff 100%);
    color:#5b4a47;
}

.wrap{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 50px 16px;
}

.card{
    width: 100%;
    max-width: 420px;
    background: rgba(255,255,255,0.92);
    border: 1px solid rgba(255,183,206,0.35);
    border-radius: 28px;
    box-shadow: 0 18px 60px rgba(0,0,0,0.10);
    padding: 26px 22px;
}

.brand{
    text-align:center;
    margin-bottom: 18px;
}

.logo{
    width: 76px;
    height: 76px;
    border-radius: 999px;
    background: linear-gradient(90deg, #ff5f88, #ffb7ce);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size: 34px;
    font-weight: 900;
    margin: 0 auto 12px;
    box-shadow:0 18px 35px rgba(255,95,136,0.25);
}

h1{
    margin:0;
    font-size: 22px;
    font-weight: 900;
    color:#ff5f88;
}

.subtitle{
    margin: 8px 0 0;
    color:#8a6f6a;
    font-weight: 600;
    font-size: 13px;
}

.title{
    margin: 16px 0 14px;
    text-align:center;
    font-size: 18px;
    font-weight: 900;
    color:#ff5f88;
}

.alert{
    padding: 10px 12px;
    border-radius: 16px;
    font-weight: 800;
    margin-bottom: 12px;
    text-align:center;
}

.alert-error{
    background:#ffe7ea;
    border:1px solid #ffc6d1;
    color:#c81d3a;
}

.alert-success{
    background:#eafff1;
    border:1px solid #bff5d2;
    color:#1a7f3b;
}

.field{
    margin-bottom: 12px;
}

label{
    display:block;
    margin-bottom: 6px;
    font-weight: 800;
    font-size: 13px;
    color:#6f5a56;
}

input, textarea{
    width:100%;
    padding: 12px 14px;
    border-radius: 18px;
    border: 1px solid #f1d3db;
    outline:none;
    font-size: 14px;
    transition:.2s;
    background:#fff;
}

textarea{
    min-height: 90px;
    resize: vertical;
}

input:focus, textarea:focus{
    border-color:#ff87a0;
    box-shadow: 0 0 0 4px rgba(255,135,160,0.18);
}

.btn{
    width:100%;
    padding: 12px 14px;
    border-radius: 999px;
    border:none;
    cursor:pointer;
    font-weight: 900;
    font-size: 15px;
    transition:.2s;
}

.btn-primary{
    background: linear-gradient(90deg, #ff5f88, #ff87a0);
    color:white;
    box-shadow:0 16px 30px rgba(255,95,136,0.22);
}

.btn-primary:hover{
    transform: translateY(-2px);
}

.bottom{
    margin-top: 14px;
    text-align:center;
    font-size: 13px;
    color:#8a6f6a;
    font-weight: 700;
}

.bottom a{
    color:#ff3f72;
    text-decoration:none;
    font-weight: 900;
}

.bottom a:hover{
    text-decoration: underline;
}

.back{
    display:inline-block;
    margin-top: 10px;
    padding: 10px 16px;
    border-radius: 999px;
    background:#2f2a29;
    color:white !important;
    text-decoration:none;
    font-weight: 900;
}
</style>
</head>

<body>

<div class="wrap">
    <div class="card">

        <div class="brand">
            <div class="logo">🎂</div>
            <h1>ร้านอบอุ่นเบเกอรี่</h1>
            <div class="subtitle">ขนมอบโฮมเมด หวานละมุน 💗</div>
        </div>

        <?php if (!isset($_GET['register'])): ?>

            <div class="title">เข้าสู่ระบบ</div>

            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

            <form method="post" action="?action=login">

                <div class="field">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" placeholder="เช่น 0912345678" required>
                </div>

                <div class="field">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="bottom">
                ยังไม่มีบัญชี? <a href="?register=1">สมัครสมาชิก</a>
            </div>

        <?php else: ?>

            <div class="title">สมัครสมาชิก</div>

            <?php if ($error) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>

            <form method="post" action="?action=register">

                <div class="field">
                    <label>ชื่อ</label>
                    <input type="text" name="first_name" placeholder="ชื่อ" required>
                </div>

                <div class="field">
                    <label>นามสกุล</label>
                    <input type="text" name="last_name" placeholder="นามสกุล" required>
                </div>

                <div class="field">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="text" name="phone" placeholder="เช่น 0912345678" required>
                </div>

                <div class="field">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" placeholder="ตั้งรหัสผ่าน" required>
                </div>

                <div class="field">
                    <label>ที่อยู่จัดส่ง</label>
                    <textarea name="address" placeholder="บ้านเลขที่ / หมู่ / ซอย / ถนน / ตำบล / อำเภอ / จังหวัด / รหัสไปรษณีย์" required></textarea>
                </div>

                <button class="btn btn-primary" type="submit">สมัครสมาชิก</button>
            </form>

            <div class="bottom">
                <a class="back" href="index.php">← กลับไปหน้าเข้าสู่ระบบ</a>
            </div>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
