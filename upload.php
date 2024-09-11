<?php

// Check if a file was uploaded
if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // Get the uploaded file
    $file = $_FILES['file']['tmp_name'];
    
    // Process the CSV file here (example code)
    // For demonstration purposes, just display the file name and size
    echo 'Uploaded file: ' . $_FILES['file']['name'] . ' (' . $_FILES['file']['size'] . ' bytes)';
} else {
    // Handle file upload error
    echo 'Error uploading file: ' . $_FILES['file']['error'];
}

?>
