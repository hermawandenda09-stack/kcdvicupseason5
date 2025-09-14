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

// Get school information
$school_id = $_SESSION["id"];
$sql = "SELECT * FROM schools WHERE id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $school = mysqli_fetch_array($result, MYSQLI_ASSOC);
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

// Count total players
$sql_count = "SELECT COUNT(*) as total_players FROM players WHERE school_id = ?";
if($stmt_count = mysqli_prepare($conn, $sql_count)){
    mysqli_stmt_bind_param($stmt_count, "i", $school_id);
    
    if(mysqli_stmt_execute($stmt_count)){
        $result_count = mysqli_stmt_get_result($stmt_count);
        $row_count = mysqli_fetch_array($result_count, MYSQLI_ASSOC);
        $total_players = $row_count['total_players'];
    } else{
        $total_players = 0;
    }
    
    mysqli_stmt_close($stmt_count);
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
            <a href="dashboard.php" class="navbar-brand">KCDVI Cup season 5 Mini Soccer Registration</a>
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
        <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION["nama_sekolah"]); ?>!</h1>
        <p>Ini adalah halaman dashboard untuk mengelola pendaftaran tim mini soccer Anda.</p>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Pemain</h3>
                <p><?php echo $total_players; ?></p>
            </div>
            <div class="stat-card">
                <h3>Status Pendaftaran</h3>
                <p><?php echo ucfirst($school['status']); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Informasi Sekolah</h2>
            </div>
            <div class="card-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="<?php echo $school['logo_path']; ?>" alt="Logo Sekolah" class="profile-img">
                </div>
                <table>
                    <tr>
                        <th>Nama Sekolah</th>
                        <td><?php echo htmlspecialchars($school['nama_sekolah']); ?></td>
                    </tr>
                    <tr>
                        <th>NPSN</th>
                        <td><?php echo htmlspecialchars($school['npsn']); ?></td>
                    </tr>
                    <tr>
                        <th>Nomor HP</th>
                        <td><?php echo htmlspecialchars($school['nomor_hp']); ?></td>
                    </tr>
                    <tr>
                        <th>Kabupaten</th>
                        <td><?php echo htmlspecialchars($school['kabupaten']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php 
                            if($school['status'] == 'approved') {
                                echo '<span style="color: green; font-weight: bold;">Disetujui</span>';
                            } elseif($school['status'] == 'rejected') {
                                echo '<span style="color: red; font-weight: bold;">Ditolak</span>';
                            } else {
                                echo '<span style="color: orange; font-weight: bold;">Menunggu Persetujuan</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <a href="profile.php" class="btn">Edit Profil</a>
                <a href="pernyataan_pemain.docx" class="btn" style="float:right" download>Unduh File Pernyataan pemain</a>
            </div>
        </div>

    
        
        <div class="card">
            <div class="card-header">
                <h2>Pemain Terbaru</h2>
            </div>
            <div class="card-body">
                <?php
                // Get latest 5 players
                $sql_latest = "SELECT * FROM players WHERE school_id = ? ORDER BY created_at DESC LIMIT 5";
                if($stmt_latest = mysqli_prepare($conn, $sql_latest)){
                    mysqli_stmt_bind_param($stmt_latest, "i", $school_id);
                    
                    if(mysqli_stmt_execute($stmt_latest)){
                        $result_latest = mysqli_stmt_get_result($stmt_latest);
                        
                        if(mysqli_num_rows($result_latest) > 0){
                            echo '<table>';
                            echo '<tr>';
                            echo '<th>Nama Pemain</th>';
                            echo '<th>NIK</th>';
                            echo '<th>Jabatan</th>';
                            echo '<th>Foto</th>';
                            echo '<th>Aksi</th>';
                            echo '</tr>';
                            
                            while($player = mysqli_fetch_array($result_latest)){
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($player['nama_pemain']) . '</td>';
                                echo '<td>' . htmlspecialchars($player['nik']) . '</td>';
                                echo '<td>' . htmlspecialchars($player['jabatan']) . '</td>';
                                echo '<td><img src="' . $player['foto_path'] . '" alt="Foto Pemain" class="thumbnail"></td>';
                                echo '<td>';
                                echo '<a href="view_player.php?id=' . $player['id'] . '" class="btn btn-info btn-sm">Lihat</a> ';
                                echo '<a href="edit_player.php?id=' . $player['id'] . '" class="btn btn-primary btn-sm">Edit</a> ';
                                echo '<a href="delete_player.php?id=' . $player['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Apakah Anda yakin ingin menghapus pemain ini?\')">Hapus</a>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</table>';
                        } else{
                            echo '<p>Belum ada pemain yang terdaftar.</p>';
                        }
                    } else{
                        echo '<p>Terjadi kesalahan saat mengambil data pemain.</p>';
                    }
                    
                    mysqli_stmt_close($stmt_latest);
                }
                ?>
            </div>
            <div class="card-footer">
                <a href="players.php" class="btn">Lihat Semua Pemain</a>
                <a href="add_player.php" class="btn btn-success">Tambah Pemain Baru</a>
                <a href="export.php" class="btn">Export Data Pemain (XLS)</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close connection
mysqli_close($conn);
?>