<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include config file
require_once "includes/config.php";

// Check if player ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: players.php");
    exit;
}

// Get player ID and school ID
$player_id = sanitize_input($_GET["id"]);
$school_id = $_SESSION["id"];

// First, get the player's file paths to delete the files
$sql_select = "SELECT foto_path, ktp_path FROM players WHERE id = ? AND school_id = ?";
if($stmt_select = mysqli_prepare($conn, $sql_select)){
    mysqli_stmt_bind_param($stmt_select, "ii", $player_id, $school_id);
    
    if(mysqli_stmt_execute($stmt_select)){
        $result = mysqli_stmt_get_result($stmt_select);
        
        if(mysqli_num_rows($result) == 1){
            $player = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $foto_path = $player['foto_path'];
            $ktp_path = $player['ktp_path'];
            
            // Prepare a delete statement
            $sql = "DELETE FROM players WHERE id = ? AND school_id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ii", $player_id, $school_id);
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Delete the files if they exist
                    if(!empty($foto_path) && file_exists($foto_path)){
                        unlink($foto_path);
                    }
                    
                    if(!empty($ktp_path) && file_exists($ktp_path)){
                        unlink($ktp_path);
                    }
                    
                    // Redirect to players page
                    header("location: players.php?success=deleted");
                    exit;
                } else{
                    echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        } else{
            // Player not found or doesn't belong to this school
            header("location: players.php");
            exit;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt_select);
}

// Close connection
mysqli_close($conn);
?>