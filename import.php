<?php

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$database = "matall";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSV file path
$csv_file = "C:\\xampp\\htdocs\\matall\\ProductData.csv";

// Open the CSV file for reading
$file_handle = fopen($csv_file, 'r');

// Prepare a statement for insertion
$stmt = $conn->prepare("INSERT INTO product (product_name, product_category, product_sub_category, product_brand, product_mrp_price, product_sell_price, product_img, product_code, product_description, discount_percentage, sku, gst_rate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Bind parameters to the prepared statement
$stmt->bind_param("sssssssssssi", $product_name, $product_category, $product_sub_category, $product_brand, $product_mrp_price, $product_sell_price, $product_img, $product_code, $product_description, $discount_percentage, $sku, $gst_rate);

// CSV column mapping (CSV column name => CSV column index)
$column_mapping = array(
    'Name' => 1, // CSV column index for product_name
    'Category' => 5, // CSV column index for product_category
    'SubCategory' => 6, // CSV column index for product_sub_category
    'Brand' => 2, // CSV column index for product_brand
    'MRP' => 10, // CSV column index for product_mrp_price
    'SellPrice' => 18, // CSV column index for product_sell_price
    'Image' => 4, // CSV column index for product_img
    'Code' => 7, // CSV column index for product_code
    'Description' => 3, // CSV column index for product_description
    'DiscountPercentage' => 14, // CSV column index for discount_percentage
    'SKU' => 8, // CSV column index for sku
    'GSTRate' => 20, // CSV column index for gst_rate
);

// Flag to keep track of duplicate
$duplicate_encountered = false;

// Loop through each line of the CSV file
while (!feof($file_handle)) {
    // Read a line from the CSV file
    $line = fgetcsv($file_handle);
    
    // Skip empty lines
    if ($line === false || $line === null) {
        continue;
    }
    
    // Extract data from the CSV line based on column mapping
    $product_name = $line[$column_mapping['Name']];
    $product_category = $line[$column_mapping['Category']];
    $product_sub_category = $line[$column_mapping['SubCategory']];
    $product_brand = $line[$column_mapping['Brand']];
    $product_mrp_price = $line[$column_mapping['MRP']];
    $product_sell_price = $line[$column_mapping['SellPrice']];
    $product_img = $line[$column_mapping['Image']];
    $product_code = $line[$column_mapping['Code']];
    $product_description = $line[$column_mapping['Description']];
    $discount_percentage = $line[$column_mapping['DiscountPercentage']];
    $sku = $line[$column_mapping['SKU']];
    $gst_rate = $line[$column_mapping['GSTRate']];
    
    // Check if the product_code already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE product_code = ?");
    $check_stmt->bind_param("s", $product_code);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close(); // Close the statement after fetching
    
    // Check if the product code is unique
    if ($count > 0 && !$duplicate_encountered) {
        echo "Duplicate entry for product code: $product_code. Skipping insertion.<br>";
        $duplicate_encountered = true;
        continue; // Skip this line
    }
    
    // If duplicate not encountered or already encountered and this is not a duplicate, execute the prepared statement for insertion
    if (!$duplicate_encountered || $count == 0) {
        if (!$stmt->execute()) {
            echo "Error: " . $stmt->error;
        } else {
            echo "Record inserted successfully for product code: $product_code<br>";
        }
    }
}

// Close the prepared statement 
$stmt->close();

// Close the file handle
fclose($file_handle);

// Close the database connection
$conn->close();

?>
