<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 检查是否是管理员登录
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 查询管理员信息（如果需要）
$stmt = $conn->prepare("SELECT name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>管理员欢迎页面</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; }
    .container { max-width: 600px; }
    .table th, .table td { vertical-align: middle; }
    .btn-custom { background-color: #4CAF50; border-color: #4CAF50; }
    .btn-custom:hover { background-color: #45a049; border-color: #45a049; }
</style>
</head>
<body>

<header>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">物品管理平台</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarsExampleDefault">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link" href="admin_welcome.php">管理员首页</a>
                </li>
            </ul>
            <span class="navbar-text">
                登录用户：<?= isset($admin['name']) ? htmlspecialchars($admin['name']) : '未知' ?>
            </span>
        </div>
    </div>
</nav>
</header>

<main class="container">
    <h1 class="mt-5">欢迎，<?= htmlspecialchars($admin['name']) ?>!</h1>
    <p>您现在可以使用以下功能：</p>

    <!-- 功能按钮 -->
    <a href="admin_view_returns.php" class="btn btn-custom w-100 mb-2">查看领回单</a>
    <a href="admin_view_complaints.php" class="btn btn-custom w-100 mb-2">查看投诉</a>
    <a href="admin_view_blacklist.php" class="btn btn-custom w-100 mb-2">查看黑名单</a>
    <a href="view_logs.php" class="btn btn-custom w-100 mb-2">查看系统日志</a>
    <a href="view_items_logs.php" class="btn btn-custom w-100 mb-2">查看物品操作日志</a>
    <p class="mt-3 text-center"><a href="logout.php" class="btn btn-secondary w-100">退出登录</a></p>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>