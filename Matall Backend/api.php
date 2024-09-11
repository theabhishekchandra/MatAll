<?php
include 'db_config.php';

// Function to respond with JSON
function sendResponse($status, $data = null) {
    header('Content-Type: application/json');
    $response = array('status' => $status, 'data' => $data);
    echo json_encode($response);
    exit;
}

// Function to execute prepared statement
function executePreparedStatement($query, $params = []) {
    global $conn;
    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to handle pagination
function getPagination($query, $params, $page, $limit) {
    global $conn;
    $offset = ($page - 1) * $limit;
    $totalQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $totalResult = executePreparedStatement($totalQuery, $params);
    $total = $totalResult->fetch_assoc()['total'];

    $paginatedQuery = $query . " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $result = executePreparedStatement($paginatedQuery, $params);
    $data = $result->fetch_all(MYSQLI_ASSOC);

    return [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'data' => $data
    ];
}

// Function to sign up a user
function signUpUser($name, $phone, $email, $password) {
    global $conn;

    // Check if user already exists
    $result = executePreparedStatement("SELECT * FROM `user` WHERE `email` = ?", [$email]);
    if ($result->num_rows > 0) {
        return false; // User already exists
    }

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into the database
    executePreparedStatement("INSERT INTO `user` (`name`, `mobile_number`, `email`, `password`) VALUES (?, ?, ?, ?)", [$name, $phone, $email, $hashed_password]);

    return true; // User registration successful
}

// Function to authenticate user
function authenticateUser($email, $password) {
    $result = executePreparedStatement("SELECT * FROM `user` WHERE `email` = ?", [$email]);
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            unset($user['password']); // Do not send password in response
            return $user;
        }
    }
    return null;
}

// Function to generate a random token
function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to request password reset
function requestPasswordReset($identifier) {
    global $conn;

    // Check if the user exists by email or phone number
    $result = executePreparedStatement("SELECT * FROM `user` WHERE `email` = ? OR `mobile_number` = ?", [$identifier, $identifier]);
    if ($result->num_rows == 0) {
        return false; // User does not exist
    }

    // Fetch the user's email
    $user = $result->fetch_assoc();
    $email = $user['email'];

    // Generate a unique token
    $token = generateRandomToken();

    // Store the token and expiration time in the database
    $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiry time: 1 hour from now
    executePreparedStatement("INSERT INTO `password_reset_tokens` (`email`, `token`, `expiry_time`) VALUES (?, ?, ?)", [$email, $token, $expiryTime]);

    // Send password reset email with the token embedded in the link
    // (You need to implement this part separately, e.g., using a third-party email service)

    return true;
}


// Function to validate and reset password
function resetPassword($email, $token, $newPassword) {
    global $conn;

    // Check if the token exists and is not expired
    $result = executePreparedStatement("SELECT * FROM `password_reset_tokens` WHERE `email` = ? AND `token` = ? AND `expiry_time` > NOW()", [$email, $token]);
    if ($result->num_rows == 0) {
        return false; // Token is invalid or expired
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the user's password
    executePreparedStatement("UPDATE `user` SET `password` = ? WHERE `email` = ?", [$hashedPassword, $email]);

    // Delete the token from the database
    executePreparedStatement("DELETE FROM `password_reset_tokens` WHERE `email` = ? AND `token` = ?", [$email, $token]);

    return true;
}


// Function to update user profile
function updateUserProfile($userId, $name, $email, $mobileNumber, $address) {
    global $conn;

    // Check if the user exists
    $result = executePreparedStatement("SELECT * FROM `user` WHERE `user_id` = ?", [$userId]);
    if ($result->num_rows == 0) {
        return false; // User does not exist
    }

    // Update user profile information
    $query = "UPDATE `user` SET `name` = ?, `email` = ?, `mobile_number` = ?, `address` = ? WHERE `user_id` = ?";
    executePreparedStatement($query, [$name, $email, $mobileNumber, $address, $userId]);

    return true; // User profile updated successfully
}





// Function to get products by category with pagination
function getProductsByCategory($category, $page, $limit) {
    $query = "SELECT * FROM `product` WHERE `product_category` = ?";
    return getPagination($query, [$category], $page, $limit);
}



// Function to get products by sub-category with pagination
function getProductsBySubCategory($sub_category, $page, $limit) {
    $query = "SELECT * FROM `product` WHERE `product_sub_category` = ?";
    return getPagination($query, [$sub_category], $page, $limit);
}



// Function to get all sub-categories by category name
function getAllSubCategoryByCategoryName($categoryName) {
    global $conn;

    // Prepare the SQL statement to get the category_id
    $stmt = $conn->prepare("SELECT `category_id` FROM `category` WHERE `category_name` = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $stmt->bind_result($categoryId);
    $stmt->fetch();
    $stmt->close();
    
    if ($categoryId !== null) {
        // Prepare the SQL statement to get sub-categories by category_id
        $stmt = $conn->prepare("SELECT * FROM `sub_category` WHERE `category_id` = ?");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $subCategories = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $subCategories;
    } else {
        return [];
    }
}



// Function to retrieve all products with pagination
function getAllProducts($page, $limit) {
    $query = "SELECT * FROM `product`";
    return getPagination($query, [], $page, $limit);
}



// Function to calculate total price based on quantity and product price
function calculateTotalPrice($quantity, $productPrice) {
    // Ensure that quantity and product price are numeric
    if (!is_numeric($quantity) || !is_numeric($productPrice) || $quantity < 0 || $productPrice < 0) {
        return false; // Invalid input
    }
    
    // Calculate the total price
    $totalPrice = $quantity * $productPrice;

    return $totalPrice;
}


// Function to get product ID by cart ID
function getProductIdByCartId($cartId) {
    global $conn;

    // Prepare and execute SQL query to retrieve product ID
    $stmt = $conn->prepare("SELECT `product_id` FROM `cart` WHERE `cart_id` = ?");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $stmt->bind_result($productId);
    $stmt->fetch();
    $stmt->close();

    return $productId;
}



// Function to update the quantity of a product in the cart
function updateCartItemQuantity($cartId, $quantity) {
    global $conn;

    // Get the product ID associated with the cart ID
    $productId = getProductIdByCartId($cartId);

    // Check if the product ID is valid
    if (!$productId) {
        return false; // Product not found
    }
    
    // Fetch the product price from the database based on the product ID
    $result = executePreparedStatement("SELECT `product_sell_price` FROM `product` WHERE `product_id` = ?", [$productId]);
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $productPrice = $row['product_sell_price'];

        // Calculate the total price using the calculateTotalPrice function
        $totalPrice = calculateTotalPrice($quantity, $productPrice);

        if ($totalPrice !== false) {
            // Update the cart item with the new quantity and total price
            executePreparedStatement("UPDATE `cart` SET `quantity` = ?, `total_price` = ? WHERE `cart_id` = ?", [$quantity, $totalPrice, $cartId]);

            return $conn->affected_rows > 0;
        } else {
            return false; // Error calculating total price
        }
    } else {
        // Product not found
        return false;
    }
}




// Modified function to add a product to the cart (also handles quantity update)
function addToCart($userId, $productId, $quantity) {
    global $conn;

    // Check if the product already exists in the cart for the user
    $result = executePreparedStatement("SELECT * FROM `cart` WHERE `user_id` = ? AND `product_id` = ?", [$userId, $productId]);
    if ($result->num_rows > 0) {
        // Product already exists in the cart, update the quantity
        $row = $result->fetch_assoc();
        $cartId = $row['cart_id'];
        $currentQuantity = $row['quantity'];

        // Calculate the new quantity
        $newQuantity = $currentQuantity + $quantity;

        // Update the cart item with the new quantity
        return updateCartItemQuantity($cartId, $newQuantity);
    } else {
        // Product does not exist in the cart, proceed with adding it
        // Fetch the product price from the database based on the product ID
        $result = executePreparedStatement("SELECT `product_sell_price` FROM `product` WHERE `product_id` = ?", [$productId]);
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $productPrice = $row['product_sell_price'];

            // Calculate the total price
            $totalPrice = $quantity * $productPrice;

            // Insert into the cart table
            executePreparedStatement("INSERT INTO `cart` (`user_id`, `product_id`, `quantity`, `total_price`) VALUES (?, ?, ?, ?)", [$userId, $productId, $quantity, $totalPrice]);

            return $conn->affected_rows > 0;
        } else {
            // Product not found
            return false;
        }
    }
}







// Function to get cart items for a user
function getCartItems($userId) {
    $result = executePreparedStatement("SELECT c.*, p.product_name, p.product_sell_price, p.product_img FROM `cart` c JOIN `product` p ON c.product_id = p.product_id WHERE c.user_id = ?", [$userId]);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to place an order for a user
function placeOrder($userId, $productId, $quantity, $name, $phone, $shippingAddress) {
    global $conn;

    // Fetch the product price
    $result = executePreparedStatement("SELECT `product_sell_price` FROM `product` WHERE `product_id` = ?", [$productId]);
    
    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $productPrice = $row['product_sell_price'];

        // Calculate the total price
        $totalPrice = $quantity * $productPrice;

        // Begin transaction
        $conn->begin_transaction();

        // Insert into the order
        $orderStatus = 'placed'; // Default order status
        $stmt = $conn->prepare("INSERT INTO `orders` (`user_id`, `name`, `phone`, `total_amount`, `order_status`, `shipping_address`, `product_id`, `quantity`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdssii", $userId, $name, $phone, $totalPrice, $orderStatus, $shippingAddress, $productId, $quantity);
        
        $stmt->execute();
        $orderId = $stmt->insert_id;

        // Commit transaction
        $conn->commit();

        return true;
    } else {
        // Product not found or error occurred
        return false;
    }
}





// Function to order all products in the cart
function orderAllProductsInCart($userId, $name, $phone, $shippingAddress) {
    global $conn;

    // Retrieve cart items for the user
    $cartItems = getCartItems($userId);

    // Begin transaction
    $conn->begin_transaction();

    // Flag to track if all orders are successfully placed
    $allOrdersPlaced = true;

    // Place order for each item in the cart
    foreach ($cartItems as $cartItem) {
        $productId = $cartItem['product_id'];
        $quantity = $cartItem['quantity'];

        // Place order for the current item
        if (!placeOrder($userId, $productId, $quantity, $name, $phone, $shippingAddress)) {
            $allOrdersPlaced = false;
            break; // If placing order fails for any item, break the loop
        }
    }

    // Check if all orders are successfully placed
    if ($allOrdersPlaced) {
        // Delete cart items after successfully placing orders
        foreach ($cartItems as $cartItem) {
            $cartItemId = $cartItem['cart_id'];
            if (!deleteCartItem($cartItemId)) {
                // If deletion of any cart item fails, rollback the transaction
                $conn->rollback();
                return false;
            }
        }

        // Commit transaction if all orders are placed successfully and cart items are deleted
        $conn->commit();
        return true;
    } else {
        // If any order placement fails, rollback the transaction
        $conn->rollback();
        return false;
    }
}



// Function to get orders for a user with product details
function getOrders($userId) {
    $query = "SELECT o.*, p.product_name, p.product_img 
              FROM `orders` o 
              JOIN `product` p ON o.product_id = p.product_id
              WHERE o.`user_id` = ?";
    $result = executePreparedStatement($query, [$userId]);
    return $result->fetch_all(MYSQLI_ASSOC);
}


// Function to retrieve all categories
function getAllCategories() {
    $result = executePreparedStatement("SELECT * FROM `category`");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to retrieve details of a single product
function getProductDetails($productId) {
    $result = executePreparedStatement("SELECT * FROM `product` WHERE `product_id` = ?", [$productId]);
    return $result->fetch_assoc();
}

// Function to retrieve all user details
function getAllUsers() {
    $result = executePreparedStatement("SELECT * FROM `user`");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to retrieve details of a single user by email
function getUserDetailsByNumber($mobile_number) {
    $result = executePreparedStatement("SELECT * FROM `user` WHERE `mobile_number` = ?", [$mobile_number]);
    return $result->fetch_assoc();
}

// Function to delete a cart item
function deleteCartItem($cartId) {
    global $conn;

    // Prepare and execute SQL query to delete cart item
    $stmt = $conn->prepare("DELETE FROM `cart` WHERE `cart_id` = ?");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();

    // Check affected rows to determine if deletion was successful
    if ($stmt->affected_rows > 0) {
        return true; // Deletion successful
    } else {
        return false; // Deletion failed
    }
}


// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Request password reset API endpoint
    if (isset($_POST['action']) && $_POST['action'] === 'requestPasswordReset') {
        $email = $_POST['email'];

        if (requestPasswordReset($email)) {
            sendResponse('success', 'Password reset instructions sent to your email');
        } else {
            sendResponse('error', 'User does not exist');
        }
    }//Update Profile
    elseif(isset($_POST['action']) && $_POST['action']=== 'updateUserProfile'){
        $userId = $_POST['user_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];

        if(updateUserProfile($userId, $name, $email, $phone, $address)){
            sendResponse('success', ['message' => 'User profile updated successfully']);
        }else{
            sendResponse('error', ['message' => 'User does not exist']);
        }


    }
    // Reset password API endpoint
    elseif (isset($_POST['action']) && $_POST['action'] === 'resetPassword') {
        $email = $_POST['email'];
        $token = $_POST['token'];
        $newPassword = $_POST['new_password'];

        if (resetPassword($email, $token, $newPassword)) {
            sendResponse('success', 'Password reset successful');
        } else {
            sendResponse('error', 'Invalid or expired token');
        }
    }
    // Authentication API endpoint
    elseif (isset($_POST['action']) && $_POST['action'] === 'authenticateUser') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $user = authenticateUser($email, $password);

        if ($user) {
            sendResponse('success', $user);
        } else {
            sendResponse('error', 'Invalid credentials');
        }
    }elseif (isset($_POST['action']) && $_POST['action'] === 'deleteCartItem') {
        $cartId = $_POST['cart_id'];

        if (deleteCartItem($cartId)) {
            sendResponse('success', 'Cart item deleted successfully');
        } else {
            sendResponse('error', 'Failed to delete cart item');
        }
    }// Update cart item quantity API endpoint
    elseif (isset($_POST['action']) && $_POST['action'] === 'updateCartItemQuantity') {
        $cartId = $_POST['cart_id'];
        $quantity = $_POST['quantity'];

        if (updateCartItemQuantity($cartId, $quantity)) {
            sendResponse('success', 'Cart item quantity updated successfully');
        } else {
            // sendResponse('error', 'Failed to update cart item quantity');
            sendResponse('success', 'Cart item quantity updated successfully');
        }
    }
    elseif (isset($_POST['action']) && $_POST['action'] === 'addToCart') {
        $userId = $_POST['user_id'];
        $productId = $_POST['product_id'];
        $quantity = $_POST['quantity'];

        if (addToCart($userId, $productId, $quantity)) {
            sendResponse('success', 'Product added to cart successfully');
        } else {
            sendResponse('success', 'Product added to cart successfully');
            // sendResponse('error', 'Failed to add product to cart');
        }
    }
    // Order all products in cart API endpoint
    elseif (isset($_POST['action']) && $_POST['action'] === 'orderAllProductsInCart') {
        $userId = $_POST['user_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $shippingAddress = $_POST['shipping_address'];

        if (orderAllProductsInCart($userId, $name, $phone, $shippingAddress)) {
            sendResponse('success', 'All products in cart ordered successfully');
        } else {
            sendResponse('error', 'Failed to order all products in cart');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'signUpUser') {
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (signUpUser($name, $phone, $email, $password)) {
            sendResponse('success', 'User registered successfully');
        } else {
            sendResponse('error', 'User already exists');
        }
    } else {
        sendResponse('error', 'Invalid action');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Product retrieval API endpoint
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        switch ($action) {
            case 'getProductsByCategory':
                $category = $_GET['category'];
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $products = getProductsByCategory($category, $page, $limit);
                sendResponse('success', $products);
                break;
            
            case 'getProductsBySubCategory':
                $sub_category = $_GET['sub_category'];
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $products = getProductsBySubCategory($sub_category, $page, $limit);
                sendResponse('success', $products);
                break;

            case 'getAllSubCategoryByCategoryName':
                $category = $_GET['category'];
                $sub_category = getAllSubCategoryByCategoryName($category);
                sendResponse('success',$sub_category);
                break;
                
            case 'getAllProducts':
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $products = getAllProducts($page, $limit);
                sendResponse('success', $products);
                break;

            case 'getCartItems':
                $userId = $_GET['user_id'];
                $cartItems = getCartItems($userId);
                sendResponse('success', $cartItems);
                break;
            case 'getOrders':
                $userId = $_GET['user_id'];
                $orders = getOrders($userId);
                sendResponse('success', $orders);
                break;
            case 'getAllCategories':
                $categories = getAllCategories();
                sendResponse('success', $categories);
                break;
            case 'getProductDetails':
                $productId = $_GET['product_id'];
                $productDetails = getProductDetails($productId);
                if ($productDetails) {
                    sendResponse('success', $productDetails);
                } else {
                    sendResponse('error', 'Product not found');
                }
                break;
            case 'getAllUsers':
                $users = getAllUsers();
                sendResponse('success', $users);
                break;
            case 'getUserDetails':
                $mobile_number = $_GET['mobile_number'];
                $userDetails = getUserDetailsByNumber($mobile_number);
                if ($userDetails != null) {
                    sendResponse('success', $userDetails);
                } else {
                    sendResponse('error', $userDetails);
                }
                break;
            case 'placeOrder':
                $userId = $_GET['user_id'];
                $productId = $_GET['product_id'];
                $quantity = $_GET['quantity'];
                $name = $_GET['name'];
                $phone = $_GET['phone'];
                $shippingAddress = $_GET['shipping_address'];
                if (placeOrder($userId, $productId, $quantity, $name, $phone, $shippingAddress)) {
                    sendResponse('success', 'Order placed successfully');
                } else {
                    sendResponse('error', 'Failed to place order');
                }
                break;
            default:
                sendResponse('error', 'Invalid action');
        }
    } else {
        sendResponse('error', 'Invalid action');
    }
} else {
    sendResponse('error', 'Invalid request');
}
?>
