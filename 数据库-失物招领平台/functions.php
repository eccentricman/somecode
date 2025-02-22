<?php
function logUserAction($userId, $action, $details = "") {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO user_actions (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>