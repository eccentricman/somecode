<?php
session_start();
require 'db_connect.php'; // 数据库连接文件

// 检查数据库连接是否成功
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 确保用户已登录并且有权限进行操作
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$return_request_id = isset($_POST['return_request_id']) ? intval($_POST['return_request_id']) : 0;

if ($return_request_id <= 0) {
    die("Invalid return request ID.");
}

// 开始事务
$conn->begin_transaction();

try {
    // 获取领回单信息以验证所有权
    $sql_get_info = "
        SELECT r.item_id, i.poster_id
        FROM return_requests r
        JOIN items i ON r.item_id = i.id
        WHERE r.id = ?
    ";

    $stmt_get_info = $conn->prepare($sql_get_info);
    $stmt_get_info->bind_param("i", $return_request_id);
    $stmt_get_info->execute();
    $result = $stmt_get_info->get_result();
    $row = $result->fetch_assoc();

    if (!$row || $row['poster_id'] != $user_id) {
        throw new Exception("You do not have permission to claim this item.");
    }

    // 删除领回单记录
    $sql_delete_request = "DELETE FROM return_requests WHERE id = ?";
    $stmt_delete_request = $conn->prepare($sql_delete_request);
    $stmt_delete_request->bind_param("i", $return_request_id);

    if (!$stmt_delete_request->execute()) {
        throw new Exception("Failed to delete return request.");
    }

    // 删除物品记录
    $item_id = $row['item_id'];
    $sql_delete_item = "DELETE FROM items WHERE id = ?";
    $stmt_delete_item = $conn->prepare($sql_delete_item);
    $stmt_delete_item->bind_param("i", $item_id);

    if (!$stmt_delete_item->execute()) {
        throw new Exception("Failed to delete item record.");
    }

    // 提交事务
    $conn->commit();

    echo "物品领回成功！";

} catch (Exception $e) {
    // 回滚事务
    $conn->rollback();
    die("Error: " . $e->getMessage());
} finally {
    // 关闭语句和连接
    if (isset($stmt_get_info)) $stmt_get_info->close();
    if (isset($stmt_delete_request)) $stmt_delete_request->close();
    if (isset($stmt_delete_item)) $stmt_delete_item->close();
    $conn->close();
}
?>