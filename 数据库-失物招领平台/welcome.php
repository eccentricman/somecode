<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 查询用户信息（如果需要）
$stmt = $conn->prepare("SELECT name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// 检查用户是否在失信名单中
$stmt_blacklist = $conn->prepare("SELECT user_id FROM blacklist WHERE user_id=?");
$stmt_blacklist->bind_param("i", $user_id);
$stmt_blacklist->execute();
$result_blacklist = $stmt_blacklist->get_result();
$blacklist_status = $result_blacklist->fetch_assoc();

if ($blacklist_status) {
    echo "<!DOCTYPE html>
    <html lang='zh-CN'>
    <head>
    <meta charset='UTF-8'>
    <title>账号已被冻结</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding-top: 5rem; background-color: #f8f9fa; }
        .container { max-width: 400px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        h2 { color: #dc3545; }
    </style>
    </head>
    <body>
    <header>
    <nav class='navbar navbar-expand-md navbar-dark bg-dark fixed-top'>
        <div class='container-fluid'>
            <a class='navbar-brand' href='#'>物品管理平台</a>
        </div>
    </nav>
    </header>
    <main class='container'>
        <h2>您的账号已被冻结。</h2>
        <p>由于您被列入了失信名单，您的账号已被冻结。请联系管理员以获取更多信息。</p>
        <p><a href='logout.php' class='btn btn-danger'>退出登录</a></p>
    </main>
    </body>
    </html>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>欢迎页面</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 600px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    .button-group { text-align: center; margin-top: 20px; }
    .button { margin: 5px; }
    a { text-decoration: none; }
    .links { text-align: center; margin-top: 20px; }
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
    <h2>欢迎，<?= htmlspecialchars($user['name']) ?>!</h2>
    <p>您现在可以使用以下功能：</p>

    <!-- 创建发布物品的按钮 -->
    <div class="button-group">
        <a href="publish_item.php" class="btn btn-success button">发布丢失或拾取物品</a>
        <a href="browse_items.php" class="btn btn-primary button">浏览丢失或拾取物品</a>
        <a href="my_returns.php" class="btn btn-info button">领回单</a>
    </div>

    <div class="links">
        <p><a href="logout.php">退出登录</a></p>
    </div>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>