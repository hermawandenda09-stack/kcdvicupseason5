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
$nama_pemain = $nik = $nip = $nuptk = $tempat_lahir = $tanggal_lahir = $jabatan = $alamat = "";
$nama_pemain_err = $nik_err = $tempat_lahir_err = $tanggal_lahir_err = $jabatan_err = $alamat_err = $foto_err = $ktp_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate nama pemain
    if(empty(trim($_POST["nama_pemain"]))){
        $nama_pemain_err = "Silakan masukkan nama pemain.";
    } else{
        $nama_pemain = sanitize_input($_POST["nama_pemain"]);
    }
    
    // Validate NIK
    if(empty(trim($_POST["nik"]))){
        $nik_err = "Silakan masukkan NIK.";
    } elseif(strlen(trim($_POST["nik"])) != 16){
        $nik_err = "NIK harus terdiri dari 16 digit.";
    } else{
        // Check if NIK already exists
        $sql = "SELECT id FROM players WHERE nik = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_nik);
            
            $param_nik = trim($_POST["nik"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $nik_err = "NIK ini sudah terdaftar.";
                } else{
                    $nik = sanitize_input($_POST["nik"]);
                }
            } else{
                echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate NIP (optional)
    if(!empty(trim($_POST["nip"]))){
        $nip = sanitize_input($_POST["nip"]);
    }
    
    // Validate NUPTK (optional)
    if(!empty(trim($_POST["nuptk"]))){
        $nuptk = sanitize_input($_POST["nuptk"]);
    }
    
    // Validate tempat lahir
    if(empty(trim($_POST["tempat_lahir"]))){
        $tempat_lahir_err = "Silakan masukkan tempat lahir.";
    } else{
        $tempat_lahir = sanitize_input($_POST["tempat_lahir"]);
    }
    
    // Validate tanggal lahir
    if(empty(trim($_POST["tanggal_lahir"]))){
        $tanggal_lahir_err = "Silakan masukkan tanggal lahir.";
    } else{
        $tanggal_lahir = sanitize_input($_POST["tanggal_lahir"]);
    }
    
    // Validate jabatan
    if(empty(trim($_POST["jabatan"]))){
        $jabatan_err = "Silakan masukkan jabatan.";
    } else{
        $jabatan = sanitize_input($_POST["jabatan"]);
    }
    
    // Validate alamat
    if(empty(trim($_POST["alamat"]))){
        $alamat_err = "Silakan masukkan alamat.";
    } else{
        $alamat = sanitize_input($_POST["alamat"]);
    }
    
    // Validate foto upload
    $foto_path = "";
    if(isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0){
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["foto"]["name"];
        $filetype = $_FILES["foto"]["type"];
        $filesize = $_FILES["foto"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $foto_err = "Format file tidak valid. Silakan upload file dengan format JPG, JPEG, PNG, atau GIF.";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $foto_err = "Ukuran file terlalu besar. Maksimal 5MB.";
        }
        
        // Verify MIME type of the file
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it
            $new_filename = generate_random_string() . "." . $ext;
            $target_file = "uploads/player_photos/" . $new_filename;
            
            if(move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)){
                $foto_path = $target_file;
            } else{
                $foto_err = "Terjadi kesalahan saat mengupload file.";
            }
        } else{
            $foto_err = "Terjadi kesalahan saat mengupload file. Format file tidak valid.";
        }
    } else {
        $foto_err = "Silakan pilih foto pemain.";
    }
    
    // Validate KTP upload
    $ktp_path = "";
    if(isset($_FILES["ktp"]) && $_FILES["ktp"]["error"] == 0){
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png", "pdf" => "application/pdf");
        $filename = $_FILES["ktp"]["name"];
        $filetype = $_FILES["ktp"]["type"];
        $filesize = $_FILES["ktp"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $ktp_err = "Format file tidak valid. Silakan upload file dengan format JPG, JPEG, PNG, GIF, atau PDF.";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $ktp_err = "Ukuran file terlalu besar. Maksimal 5MB.";
        }
        
        // Verify MIME type of the file
        if(in_array($filetype, $allowed)){
            // Check whether file exists before uploading it
            $new_filename = generate_random_string() . "." . $ext;
            $target_file = "uploads/player_ids/" . $new_filename;
            
            if(move_uploaded_file($_FILES["ktp"]["tmp_name"], $target_file)){
                $ktp_path = $target_file;
            } else{
                $ktp_err = "Terjadi kesalahan saat mengupload file.";
            }
        } else{
            $ktp_err = "Terjadi kesalahan saat mengupload file. Format file tidak valid.";
        }
    } else {
        $ktp_err = "Silakan pilih KTP pemain.";
    }
    
    // Check input errors before inserting in database
    if(empty($nama_pemain_err) && empty($nik_err) && empty($tempat_lahir_err) && empty($tanggal_lahir_err) && empty($jabatan_err) && empty($alamat_err) && empty($foto_err) && empty($ktp_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO players (school_id, nama_pemain, nik, nip, nuptk, tempat_lahir, tanggal_lahir, jabatan, alamat, foto_path, ktp_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "issssssssss", $param_school_id, $param_nama_pemain, $param_nik, $param_nip, $param_nuptk, $param_tempat_lahir, $param_tanggal_lahir, $param_jabatan, $param_alamat, $param_foto_path, $param_ktp_path);
            
            // Set parameters
            $param_school_id = $_SESSION["id"];
            $param_nama_pemain = $nama_pemain;
            $param_nik = $nik;
            $param_nip = $nip;
            $param_nuptk = $nuptk;
            $param_tempat_lahir = $tempat_lahir;
            $param_tanggal_lahir = $tanggal_lahir;
            $param_jabatan = $jabatan;
            $param_alamat = $alamat;
            $param_foto_path = $foto_path;
            $param_ktp_path = $ktp_path;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to players page
                header("location: players.php?success=added");
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
    <title>Tambah Pemain - Mini Soccer Registration</title>
    <link rel="stylesheet" href="css/style.css">
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
                <h2>Tambah Pemain Baru</h2>
                <p>Silakan isi formulir di bawah ini untuk mendaftarkan pemain baru.</p>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nama Pemain</label>
                        <input type="text" name="nama_pemain" class="form-control <?php echo (!empty($nama_pemain_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nama_pemain; ?>">
                        <span class="invalid-feedback"><?php echo $nama_pemain_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" name="nik" class="form-control <?php echo (!empty($nik_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $nik; ?>">
                        <span class="invalid-feedback"><?php echo $nik_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>NIP (Nomor Induk Pegawai) - Opsional</label>
                        <input type="text" name="nip" class="form-control" value="<?php echo $nip; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>NUPTK (Nomor Unik Pendidik dan Tenaga Kependidikan) - Opsional</label>
                        <input type="text" name="nuptk" class="form-control" value="<?php echo $nuptk; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control <?php echo (!empty($tempat_lahir_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $tempat_lahir; ?>">
                        <span class="invalid-feedback"><?php echo $tempat_lahir_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control <?php echo (!empty($tanggal_lahir_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $tanggal_lahir; ?>">
                        <span class="invalid-feedback"><?php echo $tanggal_lahir_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Jabatan</label>
                        <select name="jabatan" class="form-control <?php echo (!empty($jabatan_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Pilih Jabatan</option>
                            <option value="Guru" <?php if($jabatan == "Guru") echo "selected"; ?>>Guru</option>
                            <option value="Tenaga Kependidikan" <?php if($jabatan == "Tenaga Kependidikan") echo "selected"; ?>>Tenaga Kependidikan</option>
                            </select>
                        <span class="invalid-feedback"><?php echo $jabatan_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control <?php echo (!empty($alamat_err)) ? 'is-invalid' : ''; ?>" rows="3"><?php echo $alamat; ?></textarea>
                        <span class="invalid-feedback"><?php echo $alamat_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>Foto Pemain</label>
                        <input type="file" name="foto" class="form-control <?php echo (!empty($foto_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $foto_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>KTP Pemain</label>
                        <input type="file" name="ktp" class="form-control <?php echo (!empty($ktp_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $ktp_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Tambah Pemain">
                        <a href="players.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>