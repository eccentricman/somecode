<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 确保只有管理员可以访问此页面
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$complaint_id = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
$admin_comment = isset($_POST['admin_comment']) ? htmlspecialchars($_POST['admin_comment']) : '';

// 检查必填字段
if ($complaint_id <= 0 || empty($admin_comment)) {
    die("Invalid input.");
}

// 更新投诉信息到 complaints 表
$sql_update_complaint = "
    UPDATE complaints 
    SET admin_comment = ?
    WHERE id = ?
";

$stmt_update_complaint = $conn->prepare($sql_update_complaint);

// 绑定参数并执行查询
if (!$stmt_update_complaint || !$stmt_update_complaint->bind_param("si", $admin_comment, $complaint_id)) {
    die("Error in preparing or binding statement for complaint update: (" . $conn->errno . ") " . $conn->error);
}

if (!$stmt_update_complaint->execute()) {
    die("Execute failed for complaint update: (" . $stmt_update_complaint->errno . ") " . $stmt_update_complaint->error);
}

echo "处理意见提交成功！";
header("refresh:2;url=admin_view_complaints.php");

$stmt_update_complaint->close();
$conn->close();
?>