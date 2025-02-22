<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 确保只有管理员可以访问此页面
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("location: login.php");
    exit();
}

// 获取日志信息
$stmt = $conn->prepare("SELECT * FROM user_actions ORDER BY action_time DESC LIMIT 100");
if ($stmt === false) {
    die("Error preparing the statement: " . htmlspecialchars($conn->error));
}

if (!$stmt->execute()) {
    die("Error executing the statement: " . htmlspecialchars($stmt->error));
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>系统日志</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 800px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    table { width: 100%; }
    th, td { vertical-align: middle; }
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
    <h2>系统日志</h2>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">时间戳</th>
                    <th scope="col">用户ID</th>
                    <th scope="col">操作类型</th>
                    <th scope="col">详细信息</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['action_time']) ?></td>
                        <td><?= htmlspecialchars($row['user_id']) ?></td>
                        <td><?= htmlspecialchars($row['action']) ?></td>
                        <td><?= htmlspecialchars($row['details']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>当前没有日志记录。</p>
    <?php endif; ?>

    <p class="mt-3 text-center"><a href="admin_welcome.php" class="btn btn-secondary w-100">返回管理员主页</a></p>
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