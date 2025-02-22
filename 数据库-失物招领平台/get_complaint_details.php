<?php
require 'db_connect.php';

if (isset($_GET['return_request_id'])) {
    $return_request_id = $_GET['return_request_id'];
    
    $sql = "SELECT complaint_type, complaint_reason, admin_comment 
            FROM complaints 
            WHERE return_request_id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(['error' => 'Prepare failed']);
        exit();
    }
    
    if (!$stmt->bind_param("s", $return_request_id)) {
        echo json_encode(['error' => 'Binding parameters failed']);
        exit();
    }
    
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Execute failed']);
        exit();
    }
    
    $result = $stmt->get_result();
    $complaint = $result->fetch_assoc();
    
    if ($complaint) {
        echo json_encode($complaint);
    } else {
        echo json_encode(['error' => 'No complaint found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}

$conn->close();
?>