<?php
session_start();
require 'functions.php'; // 假设 logUserAction() 在此文件中定义
if (isset($_SESSION['id'])) {
    // 记录注销操作
    logUserAction($_SESSION['id'], 'logout', "");
}

// 销毁所有会话变量
$_SESSION = array(); // 清空会话数组

// 如果使用的是 PHP 5.4 或更高版本，建议使用 session_destroy()
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到登录页面
header("location: login.php");
exit();
?>