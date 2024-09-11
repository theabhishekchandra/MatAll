<?php

include_once "../config/dbconnect.php";

$order_id = $_POST['record'];

// Prepare and execute the query
$sql = "SELECT order_status FROM orders WHERE order_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();
}

// Update the order status based on its current status
switch ($status) {
    case "placed":
        $new_status = "processing";
        break;
    case "processing":
        $new_status = "shipped";
        break;
    case "shipped":
        $new_status = "delivered";
        break;
    case "delivered":
        $new_status = "cancelled";
        break;
    case "cancelled":
        $new_status = "placed";
        break;
    default:
        // Handle unknown status
        break;
}

// Update the order status in the database
$update_sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
if ($stmt = $conn->prepare($update_sql)) {
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
}

// Close the database connection
$conn->close();
?>
