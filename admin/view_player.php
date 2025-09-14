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

// Check if player ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: all_players.php");
    exit;
}

// Get player ID
$player_id = sanitize_input($_GET["id"]);

// Prepare a select statement to get player details
$sql = "SELECT p.*, s.nama_sekolah, s.npsn, s.kabupaten FROM players p 
        JOIN schools s ON p.school_id = s.id 
        WHERE p.id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $player_id);
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            // Fetch player data
            $player = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else{
            // Player not found
            header("location: all_players.php");
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
    <title>Detail Pemain - Admin Dashboard</title>
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
            <h1>Detail Pemain</h1>
            <div>
                <a href="all_players.php" class="btn">Kembali ke Daftar Pemain</a>
                <a href="view_school.php?id=<?php echo $player['school_id']; ?>" class="btn">Lihat Sekolah</a>
                <a href="index.php" class="btn">Kembali ke Dashboard</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($player['nama_pemain']); ?></h2>
                <p>Sekolah: <?php echo htmlspecialchars($player['nama_sekolah']); ?> (<?php echo htmlspecialchars($player['npsn']); ?>)</p>
            </div>
            <div class="card-body">
                <div class="player-details">
                    <div class="player-info">
                        <div style="text-align: center;">
                            <img src="../<?php echo $player['foto_path']; ?>" alt="Foto Pemain" class="player-photo">
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
                                <th>Sekolah</th>
                                <td><?php echo htmlspecialchars($player['nama_sekolah']); ?></td>
                            </tr>
                            <tr>
                                <th>NPSN Sekolah</th>
                                <td><?php echo htmlspecialchars($player['npsn']); ?></td>
                            </tr>
                            <tr>
                                <th>Kabupaten</th>
                                <td><?php echo htmlspecialchars($player['kabupaten']); ?></td>
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
                                <a href="../<?php echo $player['ktp_path']; ?>" target="_blank" class="btn">Lihat KTP (PDF)</a>
                            </p>
                        <?php else: ?>
                            <img src="../<?php echo $player['ktp_path']; ?>" alt="KTP Pemain" class="id-photo">
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