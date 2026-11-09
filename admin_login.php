<?php
session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

// ถ้ามี session แล้ว ไม่ต้องกลับมาหน้า login
if (isset($_SESSION['admin'])) {
    header("Location: admin_transaction.php");
    exit();
}

// เมื่อมีการกดปุ่มล็อกอิน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // ดึงข้อมูลจากตาราง admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // ตรวจสอบรหัสผ่าน
    if ($admin && $password === $admin['password']) {
        // สร้าง session สำหรับแอดมิน
        $_SESSION['admin'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'role' => $admin['role']
        ];

        header("Location: admin_transaction.php");
        exit();
    } else {
        $error = "❌ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เข้าสู่ระบบผู้ดูแล | ร้านอบอุ่นเบเกอรี่</title>
<style>
    body {
        font-family: "Prompt", Tahoma, sans-serif;
        background-color: #fff8f2;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 100px;
    }
    form {
        background: #fff;
        padding: 25px 35px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        width: 320px;
    }
    h2 {
        color: #ff87a0;
        margin-bottom: 20px;
    }
    label {
        font-weight: bold;
        color: #5b4a47;
    }
    input {
        padding: 8px;
        margin: 6px 0 14px 0;
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    button {
        margin-top: 10px;
        width: 100%;
        background-color: #ff87a0;
        border: none;
        padding: 10px;
        border-radius: 5px;
        color: #fff;
        font-weight: bold;
        cursor: pointer;
    }
    button:hover {
        background-color: #ff7091;
    }
    .error {
        color: red;
        margin-top: 10px;
        font-weight: bold;
        text-align: center;
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
</head>
<body>

<h2>👩‍🍳 เข้าสู่ระบบผู้ดูแลร้านอบอุ่นเบเกอรี่</h2>

<form method="POST">
    <label>ชื่อผู้ใช้:</label>
    <input type="text" name="username" required>

    <label>รหัสผ่าน:</label>
    <input type="password" name="password" required>

    <button type="submit">เข้าสู่ระบบ</button>

    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</form>

</body>
</html>
