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

// Define variables and initialize with empty values
$nama_sekolah = $nomor_hp = $kabupaten = $current_password = $new_password = $confirm_password = "";
$nama_sekolah_err = $nomor_hp_err = $kabupaten_err = $current_password_err = $new_password_err = $confirm_password_err = $logo_err = "";

// Get school ID
$school_id = $_SESSION["id"];

// Fetch school data
$sql = "SELECT * FROM schools WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $school = mysqli_fetch_array($result, MYSQLI_ASSOC);
            
            // Set form values
            $nama_sekolah = $school['nama_sekolah'];
            $nomor_hp = $school['nomor_hp'];
            $kabupaten = $school['kabupaten'];
            $logo_path = $school['logo_path'];
        } else{
            // School not found
            header("location: logout.php");
            exit;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    mysqli_stmt_close($stmt);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate nama sekolah
    if(empty(trim($_POST["nama_sekolah"]))){
        $nama_sekolah_err = "Silakan masukkan nama sekolah.";
    } else{
        $nama_sekolah = sanitize_input($_POST["nama_sekolah"]);
    }
    
    // Validate nomor HP
    if(empty(trim($_POST["nomor_hp"]))){
        $nomor_hp_err = "Silakan masukkan nomor handphone.";
    } else{
        $nomor_hp = sanitize_input($_POST["nomor_hp"]);
    }
    
    // Validate kabupaten
    if(empty(trim($_POST["kabupaten"]))){
        $kabupaten_err = "Silakan masukkan kabupaten.";
    } else{
        $kabupaten = sanitize_input($_POST["kabupaten"]);
    }
    
    // Validate current password if changing password
    if(!empty(trim($_POST["new_password"])) || !empty(trim($_POST["confirm_password"]))){
        if(empty(trim($_POST["current_password"]))){
            $current_password_err = "Silakan masukkan password saat ini.";
        } else{
            $current_password = trim($_POST["current_password"]);
            
            // Verify current password
            $sql = "SELECT password FROM schools WHERE id = ?";
            if($stmt = mysqli_prepare($conn, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $school_id);
                
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_store_result($stmt);
                    
                    if(mysqli_stmt_num_rows($stmt) == 1){
                        mysqli_stmt_bind_result($stmt, $hashed_password);
                        if(mysqli_stmt_fetch($stmt)){
                            if(!password_verify($current_password, $hashed_password)){
                                $current_password_err = "Password yang Anda masukkan tidak valid.";
                            }
                        }
                    }
                }
                
                mysqli_stmt_close($stmt);
            }
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
    }
    
    // Handle logo upload (if provided)
    $new_logo_path = $logo_path; // Default to existing path
    if(isset($_FILES["logo"]) && $_FILES["logo"]["error"] != 4){ // 4 means no file was uploaded
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["logo"]["name"];
        $filetype = $_FILES["logo"]["type"];
        $filesize = $_FILES["logo"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $logo_err = "Format file tidak valid. Silakan upload file dengan format JPG, JPEG, PNG, atau GIF.";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $logo_err = "Ukuran file terlalu besar. Maksimal 5MB.";
        }
        
        // Verify MIME type of the file
        if(in_array($filetype, $allowed) && empty($logo_err)){
            // Check whether file exists before uploading it
            $new_filename = generate_random_string() . "." . $ext;
            $target_file = "uploads/logos/" . $new_filename;
            
            if(move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)){
                $new_logo_path = $target_file;
                
                // Delete old file if it exists and is different
                if(!empty($logo_path) && file_exists($logo_path) && $logo_path != $new_logo_path){
                    unlink($logo_path);
                }
            } else{
                $logo_err = "Terjadi kesalahan saat mengupload file.";
            }
        } elseif($_FILES["logo"]["error"] != 4) { // Only show error if file was actually uploaded
            $logo_err = "Terjadi kesalahan saat mengupload file. Format file tidak valid.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($nama_sekolah_err) && empty($nomor_hp_err) && empty($kabupaten_err) && empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err) && empty($logo_err)){
        
        // Prepare an update statement
        if(!empty($new_password)){
            // Update with new password
            $sql = "UPDATE schools SET nama_sekolah = ?, nomor_hp = ?, kabupaten = ?, password = ?, logo_path = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sssssi", $param_nama_sekolah, $param_nomor_hp, $param_kabupaten, $param_password, $param_logo_path, $param_id);
                
                // Set parameters
                $param_nama_sekolah = $nama_sekolah;
                $param_nomor_hp = $nomor_hp;
                $param_kabupaten = $kabupaten;
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                $param_logo_path = $new_logo_path;
                $param_id = $school_id;
            }
        } else {
            // Update without changing password
            $sql = "UPDATE schools SET nama_sekolah = ?, nomor_hp = ?, kabupaten = ?, logo_path = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "ssssi", $param_nama_sekolah, $param_nomor_hp, $param_kabupaten, $param_logo_path, $param_id);
                
                // Set parameters
                $param_nama_sekolah = $nama_sekolah;
                $param_nomor_hp = $nomor_hp;
                $param_kabupaten = $kabupaten;
                $param_logo_path = $new_logo_path;
                $param_id = $school_id;
            }
        }
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt)){
            // Update session variable
            $_SESSION["nama_sekolah"] = $nama_sekolah;
            
            // Set success message
            $success_message = "Profil berhasil diperbarui!";
        } else{
            echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
        }

        // Close statement
        mysqli_stmt_close($stmt);
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
    <title>Profil Sekolah - Mini Soccer Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .current-logo {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">Mini Soccer Registration</a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="players.php">Daftar Pemain</a></li>
                <li><a href="add_player.php">Tambah Pemain</a></li>
                <li><a href="profile.php">Profil Sekolah</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Profil Sekolah</h2>
                <p>Silakan edit informasi profil sekolah Anda di bawah ini.</p>
            </div>
            <div class="card-body">
                <?php 
                if(isset($success_message)){
                    echo '<div class="alert alert-success">' . $success_message . '</div>';
                }
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>NPSN</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($school['npsn']); ?>" disabled>
                        <small class="form-text text-muted">NPSN tidak dapat diubah.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Sekolah</label>
                        <input type="text" name="nama_sekolah" class="form-control <?php echo (!empty($nama_sekolah_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nama_sekolah; ?>">
                        <span class="invalid-feedback"><?php echo $nama_sekolah_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Nomor Handphone</label>
                        <input type="text" name="nomor_hp" class="form-control <?php echo (!empty($nomor_hp_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nomor_hp; ?>">
                        <span class="invalid-feedback"><?php echo $nomor_hp_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Kabupaten</label>
                        <input type="text" name="kabupaten" class="form-control <?php echo (!empty($kabupaten_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $kabupaten; ?>">
                        <span class="invalid-feedback"><?php echo $kabupaten_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Logo Sekolah</label>
                        <?php if(!empty($logo_path)): ?>
                            <div>
                                <p>Logo saat ini:</p>
                                <img src="<?php echo $logo_path; ?>" alt="Logo Sekolah" class="current-logo">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control <?php echo (!empty($logo_err)) ? 'is-invalid' : ''; ?>">
                        <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah logo.</small>
                        <span class="invalid-feedback"><?php echo $logo_err; ?></span>
                    </div>
                    
                    <hr>
                    
                    <h3>Ubah Password</h3>
                    <p>Biarkan kosong jika tidak ingin mengubah password.</p>
                    
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
                        <input type="submit" class="btn btn-primary" value="Simpan Perubahan">
                        <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>