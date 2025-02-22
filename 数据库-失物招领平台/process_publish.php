<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 检查数据库连接是否成功
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 获取表单数据并清理
$item_type = $_POST['item_type'];
$category = htmlspecialchars($_POST['category']);
$item_name = htmlspecialchars($_POST['item_name']);
$description = htmlspecialchars($_POST['description']);
$location = htmlspecialchars($_POST['location']);
$datetime = htmlspecialchars($_POST['datetime']);
$owner_id = htmlspecialchars($_POST['owner_id']);
$poster_id = htmlspecialchars($_POST['poster_id']);

// 插入物品信息到 items 表
$sql = "
    INSERT INTO items (item_type, category, item_name, description, location, datetime, owner_id, poster_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

// 绑定参数并执行查询
if (!$stmt || !$stmt->bind_param("sssssssi", $item_type, $category, $item_name, $description, $location, $datetime, $owner_id, $poster_id)) {
    die("Error in preparing or binding statement: (" . $conn->errno . ") " . $conn->error);
}

if (!$stmt->execute()) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}

echo "物品信息发布成功！<br>";
echo "<a href='welcome.php'>返回首页</a>";

$stmt->close();
$conn->close();
?>