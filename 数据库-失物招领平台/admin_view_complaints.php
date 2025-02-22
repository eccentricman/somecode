<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 检查是否是管理员登录
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("location: login.php");
    exit();
}

// 获取所有投诉信息
$sql_get_complaints = "
    SELECT c.id AS complaint_id, c.return_request_id, c.user_id, c.complaint_type, c.complaint_reason, c.admin_comment, c.created_at,
           i.item_name, u.name AS user_name
    FROM complaints c
    JOIN return_requests r ON c.return_request_id = r.id
    JOIN items i ON r.item_id = i.id -- 假设 return_requests 表有 item_id 字段指向 items 表
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
";

$result = $conn->query($sql_get_complaints);

// 检查查询是否成功执行
if ($result === false) {
    die("数据库查询失败: " . htmlspecialchars($conn->error));
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>管理投诉</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 800px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    table { width: 100%; }
    th, td { vertical-align: middle; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    form { display: inline-block; }
    textarea { width: 100%; height: 100px; }
    .btn-success { margin-left: 10px; }
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
    <h2>管理投诉</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">投诉编号</th>
                    <th scope="col">物品名</th>
                    <th scope="col">投诉用户</th>
                    <th scope="col">投诉类别</th>
                    <th scope="col">投诉原因</th>
                    <th scope="col">时间</th>
                    <th scope="col">处理意见</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['complaint_id']) ?></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['complaint_type']) ?></td>
                        <td><?= htmlspecialchars($row['complaint_reason']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <?php if (!empty($row['admin_comment'])): ?>
                                <p><?= htmlspecialchars($row['admin_comment']) ?></p>
                            <?php else: ?>
                                <form action="process_admin_complaint.php" method="post">
                                    <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($row['complaint_id']) ?>">
                                    <textarea name="admin_comment" placeholder="请输入处理意见..."></textarea><br>
                                    <button type="submit" class="btn btn-success mt-2">提交意见</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>当前没有投诉。</p>
    <?php endif; ?>

    <p class="mt-3 text-center"><a href="admin_welcome.php" class="btn btn-secondary w-100">返回管理面板</a></p>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>