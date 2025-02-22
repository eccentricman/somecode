<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

// 获取物品信息以供显示
$stmt = $conn->prepare("SELECT * FROM items WHERE id=?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

// 检查物品是否存在
if (!$item) {
    echo "<!DOCTYPE html>
    <html lang='zh-CN'>
    <head>
    <meta charset='UTF-8'>
    <title>物品信息不存在</title>
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
        <h2>找不到该物品信息。</h2>
        <p><button onclick=\"history.back()\" class='btn btn-secondary'>返回</button></p>
    </main>
    </body>
    </html>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $return_reason = trim($_POST['return_reason']);
    $contact_info = trim($_POST['contact_info']);

    // 调用存储过程来插入领回单信息到数据库
    $stmt = $conn->prepare("CALL add_return_request(?, ?, ?, ?)");
    $stmt->bind_param("iiss", $item_id, $user_id, $return_reason, $contact_info);

    if ($stmt->execute()) {
        // 记录发起领回单的操作
        logUserAction($user_id, 'request_return', json_encode($_POST));

        echo "<script>alert('领回单提交成功。'); window.location.href='browse_items.php';</script>";
    } else {
        echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>发起物品领回单</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 600px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    ul.item-details { list-style-type: none; padding: 0; margin-bottom: 20px; }
    ul.item-details li { margin-bottom: 5px; }
    .form-group { margin-bottom: 15px; }
    .btn-primary { width: 100%; }
    .alert { margin-top: 10px; }
    footer { margin-top: 2rem; text-align: center; }
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
    <h2>发起物品领回单</h2>
    <p>您正在为以下物品发起领回单：</p>
    <ul class="item-details">
        <li>物品编号: <?= htmlspecialchars($item['id']) ?></li>
        <li>物品名: <?= htmlspecialchars($item['item_name']) ?></li>
        <li>描述: <?= htmlspecialchars($item['description']) ?></li>
        <li>地点: <?= htmlspecialchars($item['location']) ?></li>
        <li>时间: <?= htmlspecialchars($item['datetime']) ?></li>
    </ul>

    <form action="request_return.php?item_id=<?= urlencode($item['id']) ?>" method="post">
        <div class="form-group">
            <label for="return_reason">领回原因:</label>
            <textarea class="form-control" id="return_reason" name="return_reason" rows="4" required></textarea>
        </div>
        <div class="form-group">
            <label for="contact_info">联系方式:</label>
            <input type="text" class="form-control" id="contact_info" name="contact_info" required>
        </div>
        <button type="submit" class="btn btn-primary">提交领回单</button>
    </form>

    <footer>
        <a href="browse_items.php" class="btn btn-secondary">取消并返回浏览页面</a>
    </footer>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>