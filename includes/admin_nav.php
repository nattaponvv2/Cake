<header>
  <div class="brand">
    <div class="logo"></div>
    <div>
      <h1>👑 Admin Panel</h1>
      <small>ระบบจัดการร้าน</small>
    </div>
  </div>

  <button class="menu-btn" onclick="toggleMenu()">☰</button>

  <nav id="navMenu">
    <a href="admin_transaction.php">📦 ออเดอร์</a>
    <a href="admin_custom_orders.php">🎂 เค้กพิเศษ</a>
    <a href="admin_products.php">🛍️ สินค้า</a>
    <a href="admin_income.php">📊 รายรับรายจ่าย</a>
    <a href="shop.php">กลับหน้าร้าน</a>
    <a href="includes/logout.php" style="color:#dc3545;">ออกจากระบบ</a>
  </nav>
</header>

<script>
function toggleMenu(){
  document.getElementById("navMenu").classList.toggle("show");
}
</script>
