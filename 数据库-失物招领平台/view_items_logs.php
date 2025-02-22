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

// 查询所有日志信息
$stmt = $conn->prepare("SELECT * FROM item_logs ORDER BY log_time DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>查看物品操作日志</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; }
    .container { max-width: 960px; }
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
                登录用户：<?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : '未知' ?>
            </span>
        </div>
    </div>
</nav>
</header>

<main class="container">
    <h1 class="mt-5">查看物品操作日志</h1>

    <!-- 日志列表 -->
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">物品ID</th>
                <th scope="col">操作类型</th>
                <th scope="col">描述</th>
                <th scope="col">时间</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <th scope="row"><?= htmlspecialchars($row['log_id']) ?></th>
                <td><?= htmlspecialchars($row['item_id']) ?></td>
                <td><?= htmlspecialchars($row['action']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['log_time']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
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