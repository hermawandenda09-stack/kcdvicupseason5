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

// Define variables and initialize with empty values
$nama_pemain = $nik = $nip = $nuptk = $tempat_lahir = $tanggal_lahir = $jabatan = $alamat = $foto_path = $ktp_path = "";
$nama_pemain_err = $nik_err = $tempat_lahir_err = $tanggal_lahir_err = $jabatan_err = $alamat_err = $foto_err = $ktp_err = "";

// Fetch player data
$sql = "SELECT * FROM players WHERE id = ? AND school_id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $player_id, $school_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $player = mysqli_fetch_array($result, MYSQLI_ASSOC);
            
            // Set form values
            $nama_pemain = $player['nama_pemain'];
            $nik = $player['nik'];
            $nip = $player['nip'];
            $nuptk = $player['nuptk'];
            $tempat_lahir = $player['tempat_lahir'];
            $tanggal_lahir = $player['tanggal_lahir'];
            $jabatan = $player['jabatan'];
            $alamat = $player['alamat'];
            $foto_path = $player['foto_path'];
            $ktp_path = $player['ktp_path'];
        } else{
            // Player not found or doesn't belong to this school
            header("location: players.php");
            exit;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    mysqli_stmt_close($stmt);
}

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
        // Check if NIK already exists (excluding current player)
        $sql = "SELECT id FROM players WHERE nik = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_nik, $player_id);
            
            $param_nik = trim($_POST["nik"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $nik_err = "NIK ini sudah terdaftar untuk pemain lain.";
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
    
    // Handle foto upload (if provided)
    $new_foto_path = $foto_path; // Default to existing path
    if(isset($_FILES["foto"]) && $_FILES["foto"]["error"] != 4){ // 4 means no file was uploaded
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
        if(in_array($filetype, $allowed) && empty($foto_err)){
            // Check whether file exists before uploading it
            $new_filename = generate_random_string() . "." . $ext;
            $target_file = "uploads/player_photos/" . $new_filename;
            
            if(move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)){
                $new_foto_path = $target_file;
                
                // Delete old file if it exists and is different
                if(!empty($foto_path) && file_exists($foto_path) && $foto_path != $new_foto_path){
                    unlink($foto_path);
                }
            } else{
                $foto_err = "Terjadi kesalahan saat mengupload file.";
            }
        } elseif($_FILES["foto"]["error"] != 4) { // Only show error if file was actually uploaded
            $foto_err = "Terjadi kesalahan saat mengupload file. Format file tidak valid.";
        }
    }
    
    // Handle KTP upload (if provided)
    $new_ktp_path = $ktp_path; // Default to existing path
    if(isset($_FILES["ktp"]) && $_FILES["ktp"]["error"] != 4){ // 4 means no file was uploaded
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
        if(in_array($filetype, $allowed) && empty($ktp_err)){
            // Check whether file exists before uploading it
            $new_filename = generate_random_string() . "." . $ext;
            $target_file = "uploads/player_ids/" . $new_filename;
            
            if(move_uploaded_file($_FILES["ktp"]["tmp_name"], $target_file)){
                $new_ktp_path = $target_file;
                
                // Delete old file if it exists and is different
                if(!empty($ktp_path) && file_exists($ktp_path) && $ktp_path != $new_ktp_path){
                    unlink($ktp_path);
                }
            } else{
                $ktp_err = "Terjadi kesalahan saat mengupload file.";
            }
        } elseif($_FILES["ktp"]["error"] != 4) { // Only show error if file was actually uploaded
            $ktp_err = "Terjadi kesalahan saat mengupload file. Format file tidak valid.";
        }
    }
    
    // Check input errors before updating the database
    if(empty($nama_pemain_err) && empty($nik_err) && empty($tempat_lahir_err) && empty($tanggal_lahir_err) && empty($jabatan_err) && empty($alamat_err) && empty($foto_err) && empty($ktp_err)){
        
        // Prepare an update statement
        $sql = "UPDATE players SET nama_pemain = ?, nik = ?, nip = ?, nuptk = ?, tempat_lahir = ?, tanggal_lahir = ?, jabatan = ?, alamat = ?, foto_path = ?, ktp_path = ? WHERE id = ? AND school_id = ?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssssssssii", $param_nama_pemain, $param_nik, $param_nip, $param_nuptk, $param_tempat_lahir, $param_tanggal_lahir, $param_jabatan, $param_alamat, $param_foto_path, $param_ktp_path, $param_id, $param_school_id);
            
            // Set parameters
            $param_nama_pemain = $nama_pemain;
            $param_nik = $nik;
            $param_nip = $nip;
            $param_nuptk = $nuptk;
            $param_tempat_lahir = $tempat_lahir;
            $param_tanggal_lahir = $tanggal_lahir;
            $param_jabatan = $jabatan;
            $param_alamat = $alamat;
            $param_foto_path = $new_foto_path;
            $param_ktp_path = $new_ktp_path;
            $param_id = $player_id;
            $param_school_id = $school_id;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to players page
                header("location: players.php?success=updated");
                exit;
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
    <title>Edit Pemain - Mini Soccer Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .current-image {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 10px;
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
                <h2>Edit Data Pemain</h2>
                <p>Silakan edit data pemain di bawah ini.</p>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $player_id); ?>" method="post" enctype="multipart/form-data">
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
                            <option value="Pelatih" <?php if($jabatan == "Pelatih") echo "selected"; ?>>Pelatih</option>
                            <option value="Asisten Pelatih" <?php if($jabatan == "Asisten Pelatih") echo "selected"; ?>>Asisten Pelatih</option>
                            <option value="Pemain" <?php if($jabatan == "Pemain") echo "selected"; ?>>Pemain</option>
                            <option value="Manajer Tim" <?php if($jabatan == "Manajer Tim") echo "selected"; ?>>Manajer Tim</option>
                            <option value="Dokter Tim" <?php if($jabatan == "Dokter Tim") echo "selected"; ?>>Dokter Tim</option>
                            <option value="Fisioterapis" <?php if($jabatan == "Fisioterapis") echo "selected"; ?>>Fisioterapis</option>
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
                        <?php if(!empty($foto_path)): ?>
                            <div>
                                <p>Foto saat ini:</p>
                                <img src="<?php echo $foto_path; ?>" alt="Foto Pemain" class="current-image">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto" class="form-control <?php echo (!empty($foto_err)) ? 'is-invalid' : ''; ?>">
                        <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah foto.</small>
                        <span class="invalid-feedback"><?php echo $foto_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label>KTP Pemain</label>
                        <?php if(!empty($ktp_path)): ?>
                            <div>
                                <p>KTP saat ini:</p>
                                <?php
                                $file_extension = pathinfo($ktp_path, PATHINFO_EXTENSION);
                                if(strtolower($file_extension) == 'pdf'): ?>
                                    <p><a href="<?php echo $ktp_path; ?>" target="_blank" class="btn btn-sm">Lihat KTP (PDF)</a></p>
                                <?php else: ?>
                                    <img src="<?php echo $ktp_path; ?>" alt="KTP Pemain" class="current-image">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="ktp" class="form-control <?php echo (!empty($ktp_err)) ? 'is-invalid' : ''; ?>">
                        <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah KTP.</small>
                        <span class="invalid-feedback"><?php echo $ktp_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Simpan Perubahan">
                        <a href="view_player.php?id=<?php echo $player_id; ?>" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>