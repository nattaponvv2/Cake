<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

session_start();
require_once "includes/db.php";
require_once "includes/functions.php";

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirect("index.php");
    exit;
}

/* ================= HELPER ================= */
function e($str){ return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }

/* ================= CSRF TOKEN ================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ================= MONTH FILTER =================
   รูปแบบ: YYYY-MM
*/
$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

/* ================= ADD RECORD ================= */
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    $date   = trim($_POST['date'] ?? '');
    $title  = trim($_POST['title'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $type   = $_POST['type'] ?? '';
    $note   = trim($_POST['note'] ?? '');

    if ($date && $title && $amount > 0 && in_array($type, ['income','expense'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO incomes (date, title, amount, type, note) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$date, $title, $amount, $type, $note]);
            redirect("admin_income.php?month=".$selectedMonth);
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = "กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง";
    }
}

/* ================= DELETE (POST ONLY) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM incomes WHERE id = ?");
            $stmt->execute([$id]);
            redirect("admin_income.php?month=".$selectedMonth);
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }
}

/* ================= RESET MONTH ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }

    try {
        $stmt = $pdo->prepare("
            DELETE FROM incomes
            WHERE DATE_FORMAT(date,'%Y-%m') = ?
        ");
        $stmt->execute([$selectedMonth]);
        redirect("admin_income.php?month=".$selectedMonth);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

/* ================= FETCH MONTH DATA ================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM incomes
    WHERE DATE_FORMAT(date,'%Y-%m') = ?
    ORDER BY date DESC, id DESC
");
$stmt->execute([$selectedMonth]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================= FETCH ALL DATA (FOR TOTAL) ================= */
$all = $pdo->query("SELECT * FROM incomes ORDER BY date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

/* ================= SUMMARY (MONTH) ================= */
$monthIncome = 0;
$monthExpense = 0;

foreach ($records as $r) {
    if ($r['type'] === 'income') $monthIncome += $r['amount'];
    if ($r['type'] === 'expense') $monthExpense += $r['amount'];
}
$monthBalance = $monthIncome - $monthExpense;

/* ================= SUMMARY (ALL) ================= */
$totalIncome = 0;
$totalExpense = 0;
foreach ($all as $r) {
    if ($r['type'] === 'income') $totalIncome += $r['amount'];
    if ($r['type'] === 'expense') $totalExpense += $r['amount'];
}
$totalBalance = $totalIncome - $totalExpense;

/* ================= GRAPH (DAILY IN MONTH) ================= */
$daily = [];
foreach ($records as $r) {
    $day = $r['date'];
    if (!isset($daily[$day])) $daily[$day] = ['income'=>0,'expense'=>0];
    $daily[$day][$r['type']] += (float)$r['amount'];
}
ksort($daily); // เรียงจากเก่าไปใหม่ (กราฟสวย)

$labels = array_keys($daily);
$incomeData = array_map(fn($d)=>$d['income'], array_values($daily));
$expenseData = array_map(fn($d)=>$d['expense'], array_values($daily));

/* ================= MONTH LIST (DROPDOWN) ================= */
$monthList = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(date,'%Y-%m') AS m
    FROM incomes
    ORDER BY m DESC
")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array(date('Y-m'), $monthList)) array_unshift($monthList, date('Y-m'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายรับรายจ่าย (แอดมิน)</title>

<style>
:root{
    --bg:#f5f7fb;
    --card:#ffffff;
    --text:#111827;
    --muted:#6b7280;
    --primary:#4f46e5;
    --primary2:#4338ca;
    --income:#16a34a;
    --expense:#dc2626;
    --line:#e5e7eb;
    --soft:#f9fafb;
    --radius:16px;
}
*{box-sizing:border-box}
body{
    margin:0;
    font-family:"Prompt",system-ui,Tahoma,sans-serif;
    background:var(--bg);
    color:var(--text);
}

/* ===== LAYOUT ===== */
.container{
    max-width:1150px;
    margin:24px auto;
    padding:0 14px;
}
.header{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
h1{
    margin:0;
    font-size:22px;
    letter-spacing:.2px;
}
.sub{
    margin:4px 0 0;
    color:var(--muted);
    font-size:14px;
}

/* ===== CARDS ===== */
.grid{
    display:grid;
    grid-template-columns: 1.1fr .9fr;
    gap:16px;
}
.card{
    background:var(--card);
    border:1px solid rgba(0,0,0,.04);
    border-radius:var(--radius);
    box-shadow:0 8px 30px rgba(15,23,42,.06);
    padding:18px;
}
.card h2{
    margin:0 0 12px;
    font-size:16px;
}
.badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    background:var(--soft);
    border:1px solid var(--line);
    color:var(--muted);
    font-size:13px;
    font-weight:700;
}

/* ===== SUMMARY ===== */
.summary{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
    margin-top:12px;
}
.box{
    background:linear-gradient(180deg,#fff,#fafafa);
    border:1px solid var(--line);
    border-radius:14px;
    padding:14px;
    text-align:center;
}
.box .label{color:var(--muted);font-size:13px}
.box .value{font-size:18px;font-weight:900;margin-top:6px}
.box.income .value{color:var(--income)}
.box.expense .value{color:var(--expense)}
.box.balance .value{color:var(--primary)}

/* ===== FORMS ===== */
form .row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
    margin-bottom:10px;
}
input,select,textarea{
    width:100%;
    padding:10px 12px;
    border:1px solid var(--line);
    border-radius:12px;
    outline:none;
    background:#fff;
    font-family:inherit;
}
textarea{resize:vertical; min-height:44px}
input:focus,select:focus,textarea:focus{
    border-color:rgba(79,70,229,.6);
    box-shadow:0 0 0 4px rgba(79,70,229,.12);
}
.actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:space-between;
}
.btn{
    border:none;
    cursor:pointer;
    padding:10px 16px;
    border-radius:14px;
    font-weight:900;
    transition:.2s;
}
.btn-primary{
    background:linear-gradient(135deg,var(--primary),var(--primary2));
    color:#fff;
}
.btn-primary:hover{transform:translateY(-1px)}
.btn-danger{
    background:var(--expense);
    color:#fff;
}
.btn-soft{
    background:var(--soft);
    border:1px solid var(--line);
    color:var(--text);
}
.btn-soft:hover{background:#fff}

/* ===== TABLE ===== */
.table-wrap{overflow:auto;border-radius:14px;border:1px solid var(--line)}
table{
    width:100%;
    border-collapse:collapse;
    min-width:820px;
}
th,td{
    padding:12px 10px;
    border-bottom:1px solid var(--line);
    text-align:center;
    font-size:14px;
}
th{
    position:sticky;
    top:0;
    background:#111827;
    color:#fff;
    z-index:2;
}
tr:hover td{background:#fbfbff}
.type-income{color:var(--income);font-weight:900}
.type-expense{color:var(--expense);font-weight:900}
.note{color:var(--muted);text-align:left}

/* ===== ALERT ===== */
.alert{
    background:#fee2e2;
    color:#991b1b;
    padding:12px 14px;
    border-radius:14px;
    margin:0 0 16px;
    border:1px solid rgba(220,38,38,.25);
}

/* ===== GRAPH ===== */
.canvas-wrap{
    background:linear-gradient(180deg,#fff,#fbfbff);
    border:1px solid var(--line);
    border-radius:14px;
    padding:12px;
}
.canvas-wrap canvas{width:100%; height:240px}

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
  📊 ระบบรายรับรายจ่าย • สรุปยอดแบบมืออาชีพ 💗
</div>

<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin • รายรับรายจ่าย</h1>
      <small>เพิ่มรายการ • ดูกราฟ • สรุปยอด</small>
    </div>
  </div>

  <button class="menu-btn" onclick="toggleMenu()">☰</button>

  <nav id="navMenu">
    <a href="admin_transaction.php">📦 ออเดอร์</a>
    <a href="admin_custom_orders.php">🎂 เค้กพิเศษ</a>
    <a href="admin_products.php">🛍️ สินค้า</a>
    <a class="active" href="admin_income.php">📊 รายรับรายจ่าย</a>
    <a href="shop.php">กลับหน้าร้าน</a>
    <a href="logout.php" style="color:#dc3545;">ออกจากระบบ</a>
  </nav>
</header>
<div class="container">
<div class="header">
    

    <form method="GET" style="margin:0; display:flex; gap:10px; align-items:center;">
        <span class="badge">📅 เลือกเดือน</span>
        <select name="month" onchange="this.form.submit()" style="min-width:150px;">
            <?php foreach($monthList as $m): ?>
                <option value="<?= e($m) ?>" <?= $m==$selectedMonth?'selected':'' ?>>
                    <?= e($m) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (!empty($error)): ?>
    <div class="alert">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="grid">

    <!-- LEFT -->
    <div>

        <div class="card">
            <h2>➕ เพิ่มรายการใหม่</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="row">
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                    <input type="text" name="title" placeholder="ชื่อรายการ เช่น ค่าส่ง / ขายรองเท้า" required>
                    <input type="number" step="0.01" name="amount" placeholder="จำนวนเงิน" required>
                    <select name="type">
                        <option value="income">รายรับ</option>
                        <option value="expense">รายจ่าย</option>
                    </select>
                </div>

                <textarea name="note" placeholder="หมายเหตุ (ไม่บังคับ)"></textarea>

                <div class="actions" style="margin-top:10px;">
                    <button class="btn btn-primary" type="submit">บันทึกรายการ</button>

                    <span class="badge">
                        เดือนที่กำลังดู: <b><?= e($selectedMonth) ?></b>
                    </span>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>📋 รายการในเดือน <?= e($selectedMonth) ?></h2>

            <div class="summary">
                <div class="box income">
                    <div class="label">รายรับ (เดือนนี้)</div>
                    <div class="value"><?= number_format($monthIncome,2) ?></div>
                </div>
                <div class="box expense">
                    <div class="label">รายจ่าย (เดือนนี้)</div>
                    <div class="value"><?= number_format($monthExpense,2) ?></div>
                </div>
                <div class="box balance">
                    <div class="label">คงเหลือ (เดือนนี้)</div>
                    <div class="value"><?= number_format($monthBalance,2) ?></div>
                </div>
            </div>

            <div style="height:12px"></div>

            <div class="table-wrap">
                <table>
                    <tr>
                        <th>วันที่</th>
                        <th>ชื่อรายการ</th>
                        <th>จำนวนเงิน</th>
                        <th>ประเภท</th>
                        <th>หมายเหตุ</th>
                        <th>จัดการ</th>
                    </tr>

                    <?php if(empty($records)): ?>
                        <tr>
                            <td colspan="6" style="padding:18px;color:#6b7280;">
                                ยังไม่มีรายการในเดือนนี้
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><?= e($r['date']) ?></td>
                            <td style="text-align:left;font-weight:800;">
                                <?= e($r['title']) ?>
                            </td>
                            <td style="font-weight:900;">
                                <?= number_format($r['amount'],2) ?>
                            </td>
                            <td class="<?= $r['type']=='income'?'type-income':'type-expense' ?>">
                                <?= $r['type']=='income'?'รายรับ':'รายจ่าย' ?>
                            </td>
                            <td class="note"><?= e($r['note']) ?></td>
                            <td>
                                <form method="POST" style="margin:0" onsubmit="return confirm('ลบรายการนี้?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button class="btn btn-soft" type="submit">🗑 ลบ</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div style="height:12px"></div>

            <form method="POST" onsubmit="return confirm('ลบข้อมูลทั้งหมดของเดือน <?= e($selectedMonth) ?> ?')">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="reset" class="btn btn-danger">🧨 รีเซ็ตข้อมูลเดือนนี้</button>
            </form>
        </div>

    </div>

    <!-- RIGHT -->
    <div>

        <div class="card">
            <h2>📈 กราฟรายวัน (เดือน <?= e($selectedMonth) ?>)</h2>
            <div class="canvas-wrap">
                <canvas id="chart"></canvas>
            </div>
            <div style="margin-top:10px;color:var(--muted);font-size:13px;">
                * กราฟนี้รวมยอดรายรับ/รายจ่ายในแต่ละวันของเดือนที่เลือก
            </div>
        </div>

        <div class="card">
            <h2>🌍 สรุปทั้งหมด (ทุกเดือน)</h2>

            <div class="summary">
                <div class="box income">
                    <div class="label">รายรับรวม</div>
                    <div class="value"><?= number_format($totalIncome,2) ?></div>
                </div>
                <div class="box expense">
                    <div class="label">รายจ่ายรวม</div>
                    <div class="value"><?= number_format($totalExpense,2) ?></div>
                </div>
                <div class="box balance">
                    <div class="label">คงเหลือรวม</div>
                    <div class="value"><?= number_format($totalBalance,2) ?></div>
                </div>
            </div>

</div>

</div>

<script>
/* ================= SIMPLE CANVAS CHART =================
   ไม่ใช้ library เพื่อให้ไฟล์เดียวจบ
*/
const labels = <?= json_encode($labels) ?>;
const incomeData = <?= json_encode($incomeData) ?>;
const expenseData = <?= json_encode($expenseData) ?>;

const canvas = document.getElementById("chart");
const ctx = canvas.getContext("2d");

function resizeCanvas(){
    canvas.width = canvas.parentElement.clientWidth * 2;
    canvas.height = 260 * 2;
    drawChart();
}
window.addEventListener("resize", resizeCanvas);

function drawChart(){
    const w = canvas.width;
    const h = canvas.height;

    ctx.clearRect(0,0,w,h);

    // padding
    const padL = 70, padR = 30, padT = 30, padB = 70;
    const cw = w - padL - padR;
    const ch = h - padT - padB;

    // max value
    const maxVal = Math.max(
        1,
        ...incomeData,
        ...expenseData
    );

    // grid
    ctx.globalAlpha = 1;
    ctx.lineWidth = 2;
    ctx.strokeStyle = "#e5e7eb";
    for(let i=0;i<=5;i++){
        const y = padT + (ch/5)*i;
        ctx.beginPath();
        ctx.moveTo(padL, y);
        ctx.lineTo(padL+cw, y);
        ctx.stroke();

        // labels y
        const val = Math.round((maxVal - (maxVal/5)*i));
        ctx.fillStyle = "#6b7280";
        ctx.font = "24px Prompt";
        ctx.fillText(val.toString(), 12, y+8);
    }

    // x axis labels (ไม่ให้เยอะเกิน)
    const showEvery = Math.max(1, Math.floor(labels.length / 6));

    function xPos(i){
        if(labels.length <= 1) return padL + cw/2;
        return padL + (cw/(labels.length-1))*i;
    }
    function yPos(v){
        return padT + ch - (v/maxVal)*ch;
    }

    // draw line function
    function drawLine(data, color){
        ctx.strokeStyle = color;
        ctx.lineWidth = 6;
        ctx.beginPath();
        data.forEach((v,i)=>{
            const x = xPos(i);
            const y = yPos(v);
            if(i===0) ctx.moveTo(x,y);
            else ctx.lineTo(x,y);
        });
        ctx.stroke();

        // dots
        ctx.fillStyle = color;
        data.forEach((v,i)=>{
            const x = xPos(i);
            const y = yPos(v);
            ctx.beginPath();
            ctx.arc(x,y,7,0,Math.PI*2);
            ctx.fill();
        });
    }

    // income (green) + expense (red)
    drawLine(incomeData, "#16a34a");
    drawLine(expenseData, "#dc2626");

    // x labels
    ctx.fillStyle = "#6b7280";
    ctx.font = "22px Prompt";
    labels.forEach((d,i)=>{
        if(i % showEvery === 0 || i === labels.length-1){
            const x = xPos(i);
            ctx.save();
            ctx.translate(x, padT+ch+40);
            ctx.rotate(-0.5);
            ctx.fillText(d.substring(8,10), 0, 0); // แสดงเฉพาะวัน
            ctx.restore();
        }
    });

    // legend
    ctx.fillStyle = "#111827";
    ctx.font = "26px Prompt";
    ctx.fillText("รายรับ", padL, 28);
    ctx.fillStyle = "#16a34a";
    ctx.fillRect(padL+95, 10, 40, 18);

    ctx.fillStyle = "#111827";
    ctx.fillText("รายจ่าย", padL+160, 28);
    ctx.fillStyle = "#dc2626";
    ctx.fillRect(padL+270, 10, 40, 18);
}

resizeCanvas();
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
