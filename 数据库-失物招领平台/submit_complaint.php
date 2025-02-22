<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 确保用户已登录
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$return_request_id = isset($_POST['return_request_id']) ? intval($_POST['return_request_id']) : 0;
$complaint_type = htmlspecialchars($_POST['complaint_type']);
$complaint_reason = htmlspecialchars($_POST['complaint_reason']);

// 检查必填字段
if ($return_request_id <= 0 || empty($complaint_type) || empty($complaint_reason)) {
    die("Invalid input.");
}

// 插入投诉信息到 complaints 表
$sql_insert_complaint = "
    INSERT INTO complaints (return_request_id, user_id, complaint_type, complaint_reason)
    VALUES (?, ?, ?, ?)
";

$stmt_insert_complaint = $conn->prepare($sql_insert_complaint);

// 绑定参数并执行查询
if (!$stmt_insert_complaint || !$stmt_insert_complaint->bind_param("iiss", $return_request_id, $user_id, $complaint_type, $complaint_reason)) {
    die("Error in preparing or binding statement for complaint insertion: (" . $conn->errno . ") " . $conn->error);
}

if (!$stmt_insert_complaint->execute()) {
    die("Execute failed for complaint insertion: (" . $stmt_insert_complaint->errno . ") " . $stmt_insert_complaint->error);
}

echo "投诉提交成功！";
header("refresh:2;url=my_returns.php");

$stmt_insert_complaint->close();
$conn->close();
?>