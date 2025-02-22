<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 检查是否是管理员登录
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("location: login.php");
    exit();
}

// 处理解除黑名单的请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove'])) {
    $blacklist_id = intval($_POST['blacklist_id']);
    
    // 删除黑名单记录
    $stmt = $conn->prepare("DELETE FROM blacklist WHERE id=?");
    $stmt->bind_param("i", $blacklist_id);

    if ($stmt->execute()) {
        // 记录解除黑名单的操作
        logUserAction($_SESSION['user_id'], 'remove_from_blacklist', json_encode(['blacklist_id' => $blacklist_id]));
        
        echo "<script>alert('已成功解除黑名单');</script>";
    } else {
        echo "<script>alert('解除黑名单失败: " . htmlspecialchars($stmt->error) . "');</script>";
    }

    $stmt->close();
    // 重新加载页面以反映更改
    header("Refresh:0");
    exit();
}

// 查询黑名单信息
$stmt = $conn->prepare("SELECT * FROM blacklist");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>黑名单列表</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 800px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    table { width: 100%; }
    th, td { vertical-align: middle; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    form { display: inline-block; }
    .btn-danger { margin-left: 10px; }
    .alert { margin-top: 10px; }
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
    <h2>黑名单列表</h2>
    
    <?php if ($result->num_rows > 0): ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">用户ID</th>
                    <th scope="col">添加时间</th>
                    <th scope="col">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['user_id']) ?></td>
                        <td><?= htmlspecialchars($row['added_at']) ?></td>
                        <td>
                            <form action="" method="post" onsubmit="return confirm('确定要解除此用户的黑名单吗？')">
                                <input type="hidden" name="blacklist_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <button type="submit" name="remove" class="btn btn-danger">解除黑名单</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>当前没有用户被列入黑名单。</p>
    <?php endif; ?>

    <p class="mt-3 text-center"><a href="admin_welcome.php" class="btn btn-secondary w-100">返回欢迎页面</a></p>
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