<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 初始化错误信息
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identification_number = $_POST['identification_number'];
    $password = $_POST['password'];

    // 查询用户信息
    $stmt = $conn->prepare("SELECT id, name, user_type, password FROM users WHERE identification_number=?");
    $stmt->bind_param("s", $identification_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // 验证密码
        if (password_verify($password, $user['password'])) {
            // 设置会话变量
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_type'] = $user['user_type'];

            // 根据用户类型重定向
            if ($user['user_type'] === 'admin') {
                header("location: admin_welcome.php");
            } else {
                header("location: welcome.php");
            }
            exit();
        } else {
            $error_message = "密码错误，请重试。";
        }
    } else {
        $error_message = "证件号未找到，请确认输入是否正确。";
    }

    $stmt->close(); // 确保关闭预处理语句
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>用户登录</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 400px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    .form-group { margin-bottom: 15px; }
    .error { color: red; text-align: center; }
    .btn-block { width: 100%; }
    .links { text-align: center; margin-top: 15px; }
</style>
</head>
<body>

<header>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">物品管理平台</a>
    </div>
</nav>
</header>

<main class="container">
    <h2>用户登录</h2>
    
    <?php if (!empty($error_message)): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <div class="form-group">
            <label for="identification_number">证件号:</label>
            <input type="text" class="form-control" id="identification_number" name="identification_number" required>
        </div>
        <div class="form-group">
            <label for="password">密码:</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-success btn-block">登录</button>
    </form>
    <div class="links">
        <p>没有账号？<a href="register.php">去注册</a></p>
        <p>忘记密码？<a href="forgot_password.php">去重置</a></p>
    </div>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>