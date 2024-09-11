<?php

include_once "../config/dbconnect.php";

$order_id = $_POST['record'];

// Prepare and execute the query
$sql = "SELECT payment_status FROM orders WHERE order_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->bind_result($payment_status);
    $stmt->fetch();
    $stmt->close();
}

// Update the payment status based on its current status
$new_payment_status = "";
if ($payment_status == "pending") {
    $new_payment_status = "completed";
} elseif ($payment_status == "completed") {
    $new_payment_status = "failed";
}elseif ($payment_status == "failed") {
     $new_payment_status = "pending";
 }

// Update the payment status in the database
if (!empty($new_payment_status)) {
    $update_sql = "UPDATE orders SET payment_status = ? WHERE order_id = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("si", $new_payment_status, $order_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Close the database connection
$conn->close();
?>
