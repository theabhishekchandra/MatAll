<div class="container">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>S.N.</th>
                <th>Product Image</th>
                <th>Product Name</th>
                <th>Shipping Cost</th>
                <th>Quantity</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <?php
            include_once "../config/dbconnect.php";
            $ID = $_GET['orderID'];
            $sql = "SELECT * FROM orders WHERE order_id = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $ID);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $count = 1;
                
                while ($row = $result->fetch_assoc()) {
                    $p_id = $row['product_id'];
                    $quantity = $row['quantity'];
                    $total_amount = $row['total_amount'];
                    $shipping_cost = $row['shipping_cost'];
                    
                    // Fetch product details
                    $subqry = "SELECT product_img, product_name FROM product WHERE product_id = ?";
                    if ($stmt2 = $conn->prepare($subqry)) {
                        $stmt2->bind_param("i", $p_id);
                        $stmt2->execute();
                        $res = $stmt2->get_result();
                        $row2 = $res->fetch_assoc();
                        $product_img = $row2['product_img'];
                        $product_name = $row2['product_name'];
                        $stmt2->close();
                    }

                    // Fetch size details
                    // $subqry2 = "SELECT s.size_name
                    //             FROM sizes s, product_size_variation v
                    //             WHERE s.size_id = v.size_id AND v.variation_id = ?";
                    // if ($stmt3 = $conn->prepare($subqry2)) {
                    //     $stmt3->bind_param("i", $p_id);
                    //     $stmt3->execute();
                    //     $res2 = $stmt3->get_result();
                    //     $row3 = $res2->fetch_assoc();
                    //     $size_name = $row3['size_name'];
                    //     $stmt3->close();
                    // }
                    
                    // Output table row
        ?>
                    <tr>
                        <td><?= $count ?></td>
                        <td><img height="80px" src="<?= $product_img ?>"></td>
                        <td><?= $product_name ?></td>
                        <td><?$shipping_cost?></td>
                        <td><?= $quantity ?></td>
                        <td><?= $total_amount ?></td>
                    </tr>
        <?php
                    $count++;
                }
                
                $stmt->close();
            } else {
                echo "Error";
            }
        ?>
    </table>
</div>
