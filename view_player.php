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

// Get player ID
$player_id = sanitize_input($_GET["id"]);
$school_id = $_SESSION["id"];

// Prepare a select statement to get player details
$sql = "SELECT * FROM players WHERE id = ? AND school_id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "ii", $player_id, $school_id);
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            // Fetch player data
            $player = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else{
            // Player not found or doesn't belong to this school
            header("location: players.php");
            exit;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemain - Mini Soccer Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .player-photo {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .id-photo {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .player-details {
            display: flex;
            flex-wrap: wrap;
        }
        
        .player-info {
            flex: 1;
            min-width: 300px;
            padding-right: 20px;
        }
        
        .player-documents {
            flex: 1;
            min-width: 300px;
        }
        
        @media (max-width: 768px) {
            .player-details {
                flex-direction: column;
            }
            
            .player-info, .player-documents {
                width: 100%;
                padding-right: 0;
            }
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
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Detail Pemain</h2>
                    <div>
                        <a href="edit_player.php?id=<?php echo $player['id']; ?>" class="btn btn-primary">Edit</a>
                        <a href="delete_player.php?id=<?php echo $player['id']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pemain ini?')">Hapus</a>
                        <a href="players.php" class="btn">Kembali</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="player-details">
                    <div class="player-info">
                        <div style="text-align: center;">
                            <img src="<?php echo $player['foto_path']; ?>" alt="Foto Pemain" class="player-photo">
                            <h3><?php echo htmlspecialchars($player['nama_pemain']); ?></h3>
                            <p><strong><?php echo htmlspecialchars($player['jabatan']); ?></strong></p>
                        </div>
                        
                        <table style="margin-top: 20px;">
                            <tr>
                                <th>NIK</th>
                                <td><?php echo htmlspecialchars($player['nik']); ?></td>
                            </tr>
                            <?php if(!empty($player['nip'])): ?>
                            <tr>
                                <th>NIP</th>
                                <td><?php echo htmlspecialchars($player['nip']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if(!empty($player['nuptk'])): ?>
                            <tr>
                                <th>NUPTK</th>
                                <td><?php echo htmlspecialchars($player['nuptk']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Tempat, Tanggal Lahir</th>
                                <td><?php echo htmlspecialchars($player['tempat_lahir']) . ', ' . date('d-m-Y', strtotime($player['tanggal_lahir'])); ?></td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td><?php echo htmlspecialchars($player['alamat']); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <?php if($player['status'] == 'active'): ?>
                                        <span style="color: green; font-weight: bold;">Aktif</span>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Terdaftar Pada</th>
                                <td><?php echo date('d-m-Y H:i', strtotime($player['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="player-documents">
                        <h3>Dokumen KTP</h3>
                        <?php
                        $file_extension = pathinfo($player['ktp_path'], PATHINFO_EXTENSION);
                        if(strtolower($file_extension) == 'pdf'): ?>
                            <p>
                                <a href="<?php echo $player['ktp_path']; ?>" target="_blank" class="btn">Lihat KTP (PDF)</a>
                            </p>
                        <?php else: ?>
                            <img src="<?php echo $player['ktp_path']; ?>" alt="KTP Pemain" class="id-photo">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close connection
mysqli_close($conn);
?>