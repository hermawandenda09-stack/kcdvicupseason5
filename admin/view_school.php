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

// Check if school ID is provided
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: schools.php");
    exit;
}

// Get school ID
$school_id = sanitize_input($_GET["id"]);

// Prepare a select statement to get school details
$sql = "SELECT * FROM schools WHERE id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            // Fetch school data
            $school = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else{
            // School not found
            header("location: schools.php");
            exit;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Get players count for this school
$players_count = 0;
$sql_count = "SELECT COUNT(*) as total FROM players WHERE school_id = ?";
if($stmt_count = mysqli_prepare($conn, $sql_count)){
    mysqli_stmt_bind_param($stmt_count, "i", $school_id);
    
    if(mysqli_stmt_execute($stmt_count)){
        $result_count = mysqli_stmt_get_result($stmt_count);
        $row_count = mysqli_fetch_array($result_count);
        $players_count = $row_count['total'];
    }
    
    mysqli_stmt_close($stmt_count);
}


// Get players for this school
$players = array();
$sql_players = "SELECT * FROM players WHERE school_id = ? ORDER BY created_at DESC";
if($stmt_players = mysqli_prepare($conn, $sql_players)){
    mysqli_stmt_bind_param($stmt_players, "i", $school_id);
    
    if(mysqli_stmt_execute($stmt_players)){
        $result_players = mysqli_stmt_get_result($stmt_players);
        
        while($row = mysqli_fetch_array($result_players)){
            $players[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt_players);
}
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Sekolah - Admin Dashboard</title>
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
        .school-logo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .player-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
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
            <h1>Detail Sekolah</h1>
            <div>
                <a href="schools.php" class="btn">Kembali ke Daftar Sekolah</a>
                <a href="index.php" class="btn">Kembali ke Dashboard</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo htmlspecialchars($school['nama_sekolah']); ?></h2>
                    <div>
                        <?php if($school['status'] == 'pending'): ?>
                            <a href="approve_school.php?id=<?php echo $school['id']; ?>" class="btn btn-success">Setujui Sekolah</a>
                            <a href="reject_school.php?id=<?php echo $school['id']; ?>" class="btn btn-danger">Tolak Sekolah</a>
                        <?php elseif($school['status'] == 'rejected'): ?>
                            <a href="approve_school.php?id=<?php echo $school['id']; ?>" class="btn btn-success">Setujui Sekolah</a>
                        <?php elseif($school['status'] == 'approved'): ?>
                            <a href="reject_school.php?id=<?php echo $school['id']; ?>" class="btn btn-danger">Tolak Sekolah</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px; padding-right: 20px;">
                        <div style="text-align: center;">
                            <img src="../<?php echo $school['logo_path']; ?>" alt="Logo Sekolah" class="school-logo">
                            <h3><?php echo htmlspecialchars($school['nama_sekolah']); ?></h3>
                            <?php if($school['status'] == 'approved'): ?>
                                <span class="status-badge status-approved">Disetujui</span>
                            <?php elseif($school['status'] == 'rejected'): ?>
                                <span class="status-badge status-rejected">Ditolak</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Menunggu Persetujuan</span>
                            <?php endif; ?>
                        </div>
                        
                        <table style="margin-top: 20px;">
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
                                <th>Tanggal Pendaftaran</th>
                                <td><?php echo date('d-m-Y H:i', strtotime($school['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Terakhir Diperbarui</th>
                                <td><?php echo date('d-m-Y H:i', strtotime($school['updated_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Jumlah Pemain</th>
                                <td><?php echo $players_count; ?> pemain</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style="flex: 2; min-width: 300px;">
                        <h3>Daftar Pemain</h3>
                        <?php if(count($players) > 0): ?>
                            <table>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nama Pemain</th>
                                    <th>NIK</th>
                                    <th>Jabatan</th>
                                    <th>Tempat, Tanggal Lahir</th>
                                    <th>Aksi</th>
                                </tr>
                                <?php foreach($players as $player): ?>
                                    <tr>
                                        <td><img src="../<?php echo $player['foto_path']; ?>" alt="Foto Pemain" class="player-photo"></td>
                                        <td><?php echo htmlspecialchars($player['nama_pemain']); ?></td>
                                        <td><?php echo htmlspecialchars($player['nik']); ?></td>
                                        <td><?php echo htmlspecialchars($player['jabatan']); ?></td>
                                        <td><?php echo htmlspecialchars($player['tempat_lahir']) . ', ' . date('d-m-Y', strtotime($player['tanggal_lahir'])); ?></td>
                                        <td>
                                            <a href="view_player.php?id=<?php echo $player['id']; ?>" class="btn btn-info btn-sm">Lihat</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p>Belum ada pemain yang terdaftar untuk sekolah ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="export_school.php?id=<?php echo $school['id']; ?>" class="btn">Export Data Pemain (XLS)</a>
              <div class="form-group">
                        <label>Masukan Password Baru</label>
                        <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
            <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Simpan Perubahan">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
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