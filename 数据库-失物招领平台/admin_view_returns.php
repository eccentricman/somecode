<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 检查是否是管理员登录
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取领回单信息
$stmt = $conn->prepare("SELECT r.id, i.item_name, u.name AS user_name, u.id AS user_id, r.return_reason, r.contact_info, r.created_at FROM return_requests r JOIN items i ON r.item_id = i.id JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

// 处理将用户加入冒领失信名单的操作
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_blacklist'])) {
    $return_request_id = intval($_POST['return_request_id']);
    $user_id_to_blacklist = intval($_POST['user_id']);

    // 插入到冒领失信名单表
    $blacklistStmt = $conn->prepare("INSERT INTO blacklist (user_id) VALUES (?)");
    $blacklistStmt->bind_param("i", $user_id_to_blacklist);

    if ($blacklistStmt->execute()) {
        // 记录加入黑名单的操作
        logUserAction($user_id, 'add_to_blacklist', json_encode(['return_request_id' => $return_request_id, 'user_id' => $user_id_to_blacklist]));

        // 删除对应的领回单
        $deleteStmt = $conn->prepare("DELETE FROM return_requests WHERE id=?");
        $deleteStmt->bind_param("i", $return_request_id);
        $deleteStmt->execute();

        echo "<script>alert('用户已成功加入冒领失信名单');</script>";
    } else {
        echo "<script>alert('加入冒领失信名单失败: " . htmlspecialchars($blacklistStmt->error) . "');</script>";
    }

    $blacklistStmt->close();
    $deleteStmt->close();
    
    // 重新加载页面以反映更改
    header("Refresh:0");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>管理员浏览领回单</title>
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
    <h2>管理员浏览领回单</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">领回单编号</th>
                    <th scope="col">物品名</th>
                    <th scope="col">发起人</th>
                    <th scope="col">领回原因</th>
                    <th scope="col">联系方式</th>
                    <th scope="col">时间</th>
                    <th scope="col">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['return_reason']) ?></td>
                        <td><?= htmlspecialchars($row['contact_info']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <form action="" method="post" onsubmit="return confirm('确定要将此用户加入冒领失信名单吗？')">
                                <input type="hidden" name="return_request_id" value="<?= htmlspecialchars($row['id']) ?>">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['user_id']) ?>">
                                <button type="submit" name="add_to_blacklist" class="btn btn-danger">加入冒领失信名单</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>当前没有领回单。</p>
    <?php endif; ?>

    <p class="mt-3 text-center"><a href="admin_welcome.php" class="btn btn-secondary w-100">返回管理员首页</a></p>
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