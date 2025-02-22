<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 初始化错误信息变量
$errorMessage = '';
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 确保 user_type 是有效的值，并直接使用英文标识
    $userType = isset($_POST['user_type']) && in_array($_POST['user_type'], ['normal', 'admin']) ? $_POST['user_type'] : 'normal';
    $idNumber = trim($_POST['identification_number']);
    $name = trim($_POST['name']);
    $contactInfo = trim($_POST['contact_info']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // 加密密码

    // 检查 identification_number 是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE identification_number=?");
    $stmt->bind_param("s", $idNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errorMessage = "该证件号已注册，请使用其他证件号。";
    } else {
        // 如果证件号未被占用，则继续注册流程
        $stmt = $conn->prepare("INSERT INTO users (user_type, identification_number, name, contact_info, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $userType, $idNumber, $name, $contactInfo, $password);

        if ($stmt->execute()) {
            // 记录注册操作
            $userId = mysqli_insert_id($conn);
            logUserAction($userId, 'register', json_encode($_POST));

            $successMessage = "注册成功，请登录。";
            header("refresh:2;url=login.php");
        } else {
            $errorMessage = "Error: " . htmlspecialchars($stmt->error);
        }

        $stmt->close(); // 确保关闭预处理语句
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>用户注册</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 400px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    .form-group { margin-bottom: 15px; }
    .btn-primary { width: 100%; }
    .alert { margin-top: 10px; }
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
    <h2>用户注册</h2>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form action="register.php" method="post">
        <div class="form-group">
            <label for="user_type">用户类型:</label>
            <select class="form-select" id="user_type" name="user_type" required>
                <option value="normal">普通用户</option>
                <option value="admin">管理员</option>
            </select>
        </div>
        <div class="form-group">
            <label for="identification_number">证件号:</label>
            <input type="text" class="form-control" id="identification_number" name="identification_number" placeholder="请输入证件号" required>
        </div>
        <div class="form-group">
            <label for="name">姓名:</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="请输入姓名" required>
        </div>
        <div class="form-group">
            <label for="contact_info">联系方式:</label>
            <input type="text" class="form-control" id="contact_info" name="contact_info" placeholder="请输入联系方式" required>
        </div>
        <div class="form-group">
            <label for="password">密码:</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="请输入密码" required>
        </div>
        <button type="submit" class="btn btn-primary">注册</button>
    </form>
    <div class="links">
        <p>已有账号？<a href="login.php">立即登录</a></p>
    </div>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>