<?php
// Include config file
require_once "includes/config.php";

// Define variables and initialize with empty values
$nama_sekolah = $npsn = $password = $confirm_password = $nomor_hp = $kabupaten = "";
$nama_sekolah_err = $npsn_err = $password_err = $confirm_password_err = $nomor_hp_err = $kabupaten_err = $logo_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate nama sekolah
    if(empty(trim($_POST["nama_sekolah"]))){
        $nama_sekolah_err = "Silakan masukkan nama sekolah.";
    } else{
        $nama_sekolah = sanitize_input($_POST["nama_sekolah"]);
    }
    
    // Validate NPSN
    if(empty(trim($_POST["npsn"]))){
        $npsn_err = "Silakan masukkan NPSN.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM schools WHERE npsn = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_npsn);
            
            // Set parameters
            $param_npsn = trim($_POST["npsn"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $npsn_err = "NPSN ini sudah terdaftar.";
                } else{
                    $npsn = sanitize_input($_POST["npsn"]);
                }
            } else{
                echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Silakan masukkan password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password harus memiliki minimal 6 karakter.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Silakan konfirmasi password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password tidak cocok.";
        }
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
    
    // Validate logo upload
    $logo_path = "";
    if(isset($_FILES["logo"]) && $_FILES["logo"]["error"] == 0){
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
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it
            $new_filename = generate_random_string() . "." . $ext;
            $target_file = "uploads/logos/" . $new_filename;
            
            if(move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)){
                $logo_path = $target_file;
            } else{
                $logo_err = "Terjadi kesalahan saat mengupload file.";
            }
        } else{
            $logo_err = "Terjadi kesalahan saat mengupload file. Format file tidak valid.";
        }
    } else {
        $logo_err = "Silakan pilih logo sekolah.";
    }
    
    // Check input errors before inserting in database
    if(empty($nama_sekolah_err) && empty($npsn_err) && empty($password_err) && empty($confirm_password_err) && empty($nomor_hp_err) && empty($kabupaten_err) && empty($logo_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO schools (nama_sekolah, npsn, password, nomor_hp, kabupaten, logo_path) VALUES (?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssss", $param_nama_sekolah, $param_npsn, $param_password, $param_nomor_hp, $param_kabupaten, $param_logo_path);
            
            // Set parameters
            $param_nama_sekolah = $nama_sekolah;
            $param_npsn = $npsn;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_nomor_hp = $nomor_hp;
            $param_kabupaten = $kabupaten;
            $param_logo_path = $logo_path;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: login.php?registration=success");
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
    <title>KCDVI Cup season 5 Mini Soccer Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
		<img src="img/kcdcup.jpeg" style="width:5rem; margin-left: 0px">
            <a href="index.php" class="navbar-brand" style="margin-left: -250px"><marquee>KCDVI Cup season 5 Mini Soccer Registration</marquee></a>
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
                <h2>Selamat datang di Aplikasi sistem Pendaftaran Mini soccer</h2>
                <p>Silakan isi formulir di bawah ini untuk mendaftar.</p>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nama Sekolah</label>
                        <input type="text" name="nama_sekolah" class="form-control <?php echo (!empty($nama_sekolah_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nama_sekolah; ?>">
                        <span class="invalid-feedback"><?php echo $nama_sekolah_err; ?></span>
                    </div>    
                    <div class="form-group">
                        <label>NPSN (Nomor Pokok Sekolah Nasional)</label>
                        <input type="text" name="npsn" class="form-control <?php echo (!empty($npsn_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $npsn; ?>">
                        <span class="invalid-feedback"><?php echo $npsn_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
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
                        <input type="file" name="logo" class="form-control <?php echo (!empty($logo_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $logo_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Daftar">
                        <input type="reset" class="btn btn-secondary ml-2" value="Reset">
                    </div>
                    <p>Sudah memiliki akun? <a href="login.php">Login di sini</a>.</p>
                </form>
            </div>
        </div>    
    </div>
</body>
</html>