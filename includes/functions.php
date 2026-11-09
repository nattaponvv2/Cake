<?php
function redirect($page) {
    header("Location: $page");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}
