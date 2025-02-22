<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 初始化消息变量
$errorMessage = '';
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idNumber = trim($_POST['identification_number']);
    $newPassword = trim($_POST['new_password']);
    $confirmNewPassword = trim($_POST['confirm_new_password']);

    // 验证证件号是否存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE identification_number=?");
    $stmt->bind_param("s", $idNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // 确认新密码与确认新密码匹配
        if ($newPassword === $confirmNewPassword) {
            // 更新密码
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $updateStmt->bind_param("si", $hashedPassword, $user['id']);

            if ($updateStmt->execute()) {
                // 记录密码修改操作
                logUserAction($user['id'], 'change_password', "");

                $successMessage = "密码修改成功，请使用新密码登录。";
                header("refresh:2;url=login.php");
            } else {
                $errorMessage = "Error updating password: " . htmlspecialchars($updateStmt->error);
            }

            $updateStmt->close();
        } else {
            $errorMessage = "新密码和确认新密码不匹配，请重新输入。";
        }
    } else {
        $errorMessage = "证件号不存在，请重试。";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>修改密码</title>
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
    <h2>修改密码</h2>
    <p>请输入您的证件号和新密码：</p>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form action="forgot_password.php" method="post">
        <div class="form-group">
            <label for="identification_number">证件号:</label>
            <input type="text" class="form-control" id="identification_number" name="identification_number" required>
        </div>
        <div class="form-group">
            <label for="new_password">新密码:</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_new_password">确认新密码:</label>
            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
        </div>
        <button type="submit" class="btn btn-primary">提交</button>
    </form>
    <div class="links">
        <a href="login.php">返回登录页面</a>
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