<?php
session_start();
require 'db_connect.php'; // 数据库连接文件
require 'functions.php';  // 包含记录用户操作的函数

// 确保用户已登录
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 检查数据库连接是否成功
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 获取当前用户作为失主的物品及其相关的领回单信息和投诉信息
$sql = "
    SELECT r.id AS return_request_id, i.item_name, r.return_reason, r.contact_info, r.created_at,
           c.complaint_type, c.complaint_reason, c.admin_comment
    FROM return_requests r
    JOIN items i ON r.item_id = i.id
    LEFT JOIN complaints c ON r.id = c.return_request_id
    WHERE i.poster_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);

// 检查 prepare 是否成功
if ($stmt === false) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

// 绑定参数并执行查询
if (!$stmt->bind_param("i", $user_id)) {  // 注意：poster_id 应该是整数类型
    die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>我的物品领回单</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { padding-top: 5rem; background-color: #f8f9fa; }
    .container { max-width: 800px; margin-top: 50px; padding: 20px; background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #007bff; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    tr:hover { background-color: #f5f5f5; }
    .button { display: inline-block; padding: 5px 10px; background-color: #4CAF50; color: white; text-align: center; text-decoration: none; font-size: 14px; border-radius: 5px; cursor: pointer; }
    .button:hover { background-color: #45a049; }
    .complain-button { background-color: #d9534f; }
    .complain-button:hover { background-color: #c9302c; }
    .view-complaint-button { background-color: #5bc0de; }
    .view-complaint-button:hover { background-color: #46b8da; }
    .modal-content { background-color: white; padding: 20px; border-radius: 5px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { margin: 0; }
    .modal-close { cursor: pointer; }
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
    <h2>我的物品领回单</h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>领回单编号</th>
                    <th>物品名</th>
                    <th>领回原因</th>
                    <th>联系方式</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['return_request_id']) ?></td>
                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                    <td><?= htmlspecialchars($row['return_reason']) ?></td>
                    <td><?= htmlspecialchars($row['contact_info']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <!-- 领回按钮 -->
                        <form action="process_claim.php" method="post" onsubmit="return confirm('确定要领回此物品吗？')">
                            <input type="hidden" name="return_request_id" value="<?= htmlspecialchars($row['return_request_id']) ?>">
                            <button type="submit" class="btn btn-success">领回</button>
                        </form>
                        <!-- 投诉按钮 -->
                        <button onclick="showComplaintForm(<?= json_encode($row['return_request_id']) ?>)" class="btn complain-button">投诉</button>
                        <!-- 查看投诉按钮 -->
                        <?php if (!empty($row['complaint_type'])): ?>
                            <button onclick="showComplaintDetails(<?= json_encode($row['return_request_id']) ?>)" class="btn view-complaint-button">查看投诉</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>您还没有任何物品领回单。</p>
    <?php endif; ?>

    <!-- 投诉模态框 -->
    <div id="complaintModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>提交投诉</h3>
                    <span class="modal-close" onclick="hideComplaintForm()">&times;</span>
                </div>
                <form id="complaintForm" action="submit_complaint.php" method="post">
                    <input type="hidden" id="complaintReturnRequestId" name="return_request_id" value="">
                    <div class="mb-3">
                        <label for="complaintType" class="form-label">投诉类别:</label>
                        <select id="complaintType" name="complaint_type" class="form-select" required>
                            <option value="">请选择...</option>
                            <option value="虚假信息">虚假信息</option>
                            <option value="冒领">冒领</option>
                            <option value="其他">其他</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="complaintReason" class="form-label">投诉原因:</label>
                        <textarea id="complaintReason" name="complaint_reason" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="text-center">
                        <input type="submit" value="提交投诉" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 查看投诉详情模态框 -->
    <div id="complaintDetailsModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>投诉详情</h3>
                    <span class="modal-close" onclick="hideComplaintDetails()">&times;</span>
                </div>
                <div id="complaintDetailsContent" class="modal-body"></div>
            </div>
        </div>
    </div>

    <script>
        function showComplaintForm(returnRequestId) {
            document.getElementById('complaintReturnRequestId').value = returnRequestId;
            document.getElementById('complaintModal').style.display = 'block';
        }

        function hideComplaintForm() {
            document.getElementById('complaintModal').style.display = 'none';
        }

        function showComplaintDetails(returnRequestId) {
            fetch(`get_complaint_details.php?return_request_id=${encodeURIComponent(returnRequestId)}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('complaintDetailsContent').innerHTML = `
                        <p>投诉类别: ${data.complaint_type}</p>
                        <p>投诉原因: ${data.complaint_reason}</p>
                        <p>处理意见: ${data.admin_comment || '暂无处理意见'}</p>
                    `;
                    document.getElementById('complaintDetailsModal').style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }

        function hideComplaintDetails() {
            document.getElementById('complaintDetailsModal').style.display = 'none';
        }
    </script>

    <p class="mt-3 text-center"><a href="welcome.php" class="btn btn-secondary">返回首页</a></p>
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