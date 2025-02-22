<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 确保用户已登录
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>发布失物招领信息</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 600px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
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
    <h2>发布失物招领信息</h2>

    <form action="process_publish.php" method="post">
        <div class="form-group">
            <label for="item_type">物品类别:</label>
            <select class="form-select" name="item_type" id="item_type" required>
                <option value="lost">丢失物品</option>
                <option value="found">拾取物品</option>
            </select>
        </div>

        <div class="form-group">
            <label for="category">物品分类:</label>
            <input type="text" class="form-control" id="category" name="category" required>
        </div>

        <div class="form-group">
            <label for="item_name">物品名:</label>
            <input type="text" class="form-control" id="item_name" name="item_name" required>
        </div>

        <div class="form-group">
            <label for="description">物品描述:</label>
            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label for="location">失/拾地点:</label>
            <input type="text" class="form-control" id="location" name="location" required>
        </div>

        <div class="form-group">
            <label for="datetime">失/拾时间:</label>
            <input type="datetime-local" class="form-control" id="datetime" name="datetime" required>
        </div>

        <div class="form-group">
            <label for="owner_id">失/拾主证件号:</label>
            <input type="text" class="form-control" id="owner_id" name="owner_id" required>
        </div>

        <!-- 隐藏字段用于存储发布者的 ID -->
        <input type="hidden" name="poster_id" value="<?php echo htmlspecialchars($user_id); ?>">

        <button type="submit" class="btn btn-primary">发布</button>
    </form>

    <div class="links">
        <p><a href="welcome.php">返回首页</a></p>
    </div>
</main>

<!-- 引入 Bootstrap JS 和依赖 -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>