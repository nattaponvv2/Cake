<?php
session_start();
session_unset(); // ลบข้อมูล session ทั้งหมด
session_destroy(); // ทำลาย session
header("Location: index.php");
exit();
?>
