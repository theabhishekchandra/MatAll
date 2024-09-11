<?php
    include_once "../config/dbconnect.php";
    
    if(isset($_POST['upload']))
    {
       
        $catname = $_POST['c_name'];
        $catimg = $_POST['c_img'];
       
         $insert = mysqli_query($conn,"INSERT INTO category
         (category_name, category_img) 
         VALUES ('$catname','$catimg')");
 
         if(!$insert)
         {
             echo mysqli_error($conn);
             header("Location: ../index.php?category=error");
         }
         else
         {
             echo "Records added successfully.";
             header("Location: ../index.php?category=success");
         }
     
    }
        
?>