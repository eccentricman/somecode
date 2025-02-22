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

// 获取用户选择的物品类型，默认为 'lost'
$item_type = isset($_GET['type']) && in_array($_GET['type'], ['lost', 'found']) ? $_GET['type'] : 'lost';

// 查询所有物品信息，使用视图 view_items_with_user_info
$stmt = $conn->prepare("SELECT * FROM view_items_with_user_info WHERE item_type = ?");
$stmt->bind_param("s", $item_type);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>查询浏览丢失或拾取物品</title>
<!-- 引入 Bootstrap CSS -->
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
                    <a class="nav-link" href="welcome.php">首页</a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="?type=lost">查看丢失物品</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="?type=found">查看拾取物品</a>
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
    <h1 class="mt-5">查询浏览<?= htmlspecialchars($item_type == 'lost' ? '丢失' : '拾取') ?>物品</h1>

    <!-- 物品列表 -->
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">物品类别</th>
                <th scope="col">物品名</th>
                <th scope="col">描述</th>
                <th scope="col">地点</th>
                <th scope="col">时间</th>
                <th scope="col">发布者</th>
                <th scope="col">联系方式</th>
                <th scope="col">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <th scope="row"><?= htmlspecialchars($row['id']) ?></th>
                <td><?= htmlspecialchars($row['item_type'] == 'lost' ? '丢失物品' : '拾取物品') ?></td>
                <td><?= htmlspecialchars($row['item_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= htmlspecialchars($row['datetime']) ?></td>
                <td><?= htmlspecialchars($row['poster_name']) ?></td>
                <td><?= htmlspecialchars($row['poster_contact']) ?></td>
                <td>
                    <a href="request_return.php?item_id=<?= urlencode($row['id']) ?>" class="btn btn-custom">发起领回单</a>
                </td>
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