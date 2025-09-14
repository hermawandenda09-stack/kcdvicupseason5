<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}
 
// Include config file
require_once "includes/config.php";
 
// Define variables and initialize with empty values
$npsn = $password = "";
$npsn_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if npsn is empty
    if(empty(trim($_POST["npsn"]))){
        $npsn_err = "Silakan masukkan NPSN.";
    } else{
        $npsn = trim($_POST["npsn"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Silakan masukkan password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($npsn_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, nama_sekolah, npsn, password, status FROM schools WHERE npsn = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_npsn);
            
            // Set parameters
            $param_npsn = $npsn;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if npsn exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $nama_sekolah, $npsn, $hashed_password, $status);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Check if account is approved
                            if($status == "approved"){
                                // Password is correct, so start a new session
                                session_start();
                                
                                // Store data in session variables
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["npsn"] = $npsn;
                                $_SESSION["nama_sekolah"] = $nama_sekolah;
                                
                                // Redirect user to dashboard page
                                header("location: dashboard.php");
                            } else {
                                $login_err = "Akun Anda belum disetujui oleh admin. Silakan tunggu persetujuan.";
                            }
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "NPSN atau password tidak valid.";
                        }
                    }
                } else{
                    // NPSN doesn't exist, display a generic error message
                    $login_err = "NPSN atau password tidak valid.";
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
    <title>Login - Mini Soccer Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">Mini Soccer Registration</a>
            <ul class="navbar-nav">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Login Sekolah</h2>
                <p>Silakan masukkan NPSN dan password untuk login.</p>
            </div>
            <div class="card-body">
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }
                
                // Check if registration was successful
                if(isset($_GET["registration"]) && $_GET["registration"] == "success"){
                    echo '<div class="alert alert-success">Pendaftaran berhasil! Silakan tunggu persetujuan dari admin.</div>';
                }
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>NPSN</label>
                        <input type="text" name="npsn" class="form-control <?php echo (!empty($npsn_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $npsn; ?>">
                        <span class="invalid-feedback"><?php echo $npsn_err; ?></span>
                    </div>    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Login">
                    </div>
                    <p>Belum memiliki akun? <a href="index.php">Daftar sekarang</a>.</p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>