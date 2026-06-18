<?php 
session_start(); // Start session
define('ACCESS', true);
require_once '../utils/apikey.php';  // Load API Key
require_once '../auth/auth_check.php';
include '../utils/db.php'; // Use connection from db.php

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template</title>
    <style>
      
        

    </style>
    <?php include '../utils/link.php'; ?>
</head>

<body>
    <br>
    <div class="container">
        <div class="row justify-content-center">
            

        </div>
    </div>

</body>

</html>