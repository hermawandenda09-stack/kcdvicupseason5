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
 
// Define variables and initialize with empty values
$current_password = $new_password = $confirm_password = "";
$current_password_err = $new_password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate current password
    if(empty(trim($_POST["current_password"]))){
        $current_password_err = "Silakan masukkan password saat ini.";     
    } else{
        $current_password = trim($_POST["current_password"]);
    }
    
    // Validate new password
    if(empty(trim($_POST["new_password"]))){
        $new_password_err = "Silakan masukkan password baru.";     
    } elseif(strlen(trim($_POST["new_password"])) < 6){
        $new_password_err = "Password harus memiliki minimal 6 karakter.";
    } else{
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Silakan konfirmasi password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($new_password_err) && ($new_password != $confirm_password)){
            $confirm_password_err = "Password tidak cocok.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)){
        // Prepare a select statement to get current password
        $sql = "SELECT password FROM admins WHERE id = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            
            // Set parameters
            $param_id = $_SESSION["admin_id"];
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if admin exists
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($current_password, $hashed_password)){
                            // Current password is correct, prepare an update statement
                            $sql = "UPDATE admins SET password = ? WHERE id = ?";
                            
                            if($stmt_update = mysqli_prepare($conn, $sql)){
                                // Bind variables to the prepared statement as parameters
                                mysqli_stmt_bind_param($stmt_update, "si", $param_password, $param_id);
                                
                                // Set parameters
                                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                                $param_id = $_SESSION["admin_id"];
                                
                                // Attempt to execute the prepared statement
                                if(mysqli_stmt_execute($stmt_update)){
                                    // Password updated successfully. Display success message
                                    $success_message = "Password berhasil diubah.";
                                } else{
                                    echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
                                }

                                // Close statement
                                mysqli_stmt_close($stmt_update);
                            }
                        } else{
                            // Display an error message if current password is not valid
                            $current_password_err = "Password saat ini tidak valid.";
                        }
                    }
                } else{
                    // Display an error message if admin doesn't exist
                    echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
                }
            } else{
                echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #343a40;
            color: white;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-title {
            font-size: 24px;
            font-weight: bold;
        }
        .admin-user {
            display: flex;
            align-items: center;
        }
        .admin-user span {
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-title">Admin Dashboard</div>
            <div class="admin-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                <a href="logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Ubah Password</h1>
            <a href="index.php" class="btn">Kembali ke Dashboard</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Ubah Password Admin</h2>
                <p>Silakan isi formulir di bawah ini untuk mengubah password Anda.</p>
            </div>
            <div class="card-body">
                <?php 
                if(isset($success_message)){
                    echo '<div class="alert alert-success">' . $success_message . '</div>';
                }
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
                    <div class="form-group">
                        <label>Password Saat Ini</label>
                        <input type="password" name="current_password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Ubah Password">
                        <a class="btn btn-secondary" href="index.php">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>    
</body>
</html>