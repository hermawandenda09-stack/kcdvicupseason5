<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Check if school ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: schools.php");
    exit;
}

// Get school ID
$school_id = sanitize_input($_GET["id"]);

// Prepare an update statement
$sql = "UPDATE schools SET status = 'approved' WHERE id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        // Redirect to schools page with success message
        header("location: schools.php?success=approved");
        exit;
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Close connection
mysqli_close($conn);
?>