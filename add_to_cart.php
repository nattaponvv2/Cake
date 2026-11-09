<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "login"]);
    exit();
}

$id  = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));

if ($id <= 0) {
    echo json_encode(["status" => "error"]);
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] += $qty;
} else {
    $_SESSION['cart'][$id] = $qty;
}

$cartCount = count($_SESSION['cart']);

echo json_encode([
    "status" => "success",
    "cartCount" => $cartCount
]);
