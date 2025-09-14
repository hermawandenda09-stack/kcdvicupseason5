<?php
error_reporting(0);
	
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'soccer');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, DB_NAME);
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

// Define constants for file paths
define('LOGO_UPLOAD_PATH', '../uploads/logos/');
define('PLAYER_PHOTO_PATH', '../uploads/player_photos/');
define('PLAYER_ID_PATH', '../uploads/player_ids/');

// Set session timeout (30 minutes)
ini_set('session.gc_maxlifetime', 1800);
session_set_cookie_params(1800);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to generate random string for file names
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION["admin"]) && $_SESSION["admin"] === true;
}

// Function to redirect to a URL
function redirect($url) {
    header("Location: $url");
    exit;
}
?>