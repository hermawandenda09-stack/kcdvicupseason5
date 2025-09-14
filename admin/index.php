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

// Get statistics
$total_schools = 0;
$pending_schools = 0;
$approved_schools = 0;
$total_players = 0;

// Count total schools
$sql_schools = "SELECT COUNT(*) as total FROM schools";
if($result = mysqli_query($conn, $sql_schools)){
    $row = mysqli_fetch_assoc($result);
    $total_schools = $row['total'];
}

// Count pending schools
$sql_pending = "SELECT COUNT(*) as total FROM schools WHERE status = 'pending'";
if($result = mysqli_query($conn, $sql_pending)){
    $row = mysqli_fetch_assoc($result);
    $pending_schools = $row['total'];
}

// Count approved schools
$sql_approved = "SELECT COUNT(*) as total FROM schools WHERE status = 'approved'";
if($result = mysqli_query($conn, $sql_approved)){
    $row = mysqli_fetch_assoc($result);
    $approved_schools = $row['total'];
}

// Count total players
$sql_players = "SELECT COUNT(*) as total FROM players";
if($result = mysqli_query($conn, $sql_players)){
    $row = mysqli_fetch_assoc($result);
    $total_players = $row['total'];
}

// Get recent schools
$recent_schools = array();
$sql_recent = "SELECT * FROM schools ORDER BY created_at DESC LIMIT 5";
if($result = mysqli_query($conn, $sql_recent)){
    while($row = mysqli_fetch_assoc($result)){
        $recent_schools[] = $row;
    }
}
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mini Soccer Registration</title>
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
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            margin-bottom: 10px;
            color: #343a40;
        }
        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .stat-card.primary p {
            color: #007bff;
        }
        .stat-card.success p {
            color: #28a745;
        }
        .stat-card.warning p {
            color: #ffc107;
        }
        .stat-card.danger p {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-title">Admin Dashboard</div>
            <div class="admin-user">
                <span>Welcome admin kcdvi cup</span>
                <a href="logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <h1>Dashboard Admin</h1>
        <p>Selamat datang di panel admin Mini Soccer Registration System.</p>
        
        <div class="stats-container">
            <div class="stat-card primary">
                <h3>Total Sekolah</h3>
                <p><?php echo $total_schools; ?></p>
            </div>
            <div class="stat-card warning">
                <h3>Menunggu Persetujuan</h3>
                <p><?php echo $pending_schools; ?></p>
            </div>
            <div class="stat-card success">
                <h3>Sekolah Disetujui</h3>
                <p><?php echo $approved_schools; ?></p>
            </div>
            <div class="stat-card danger">
                <h3>Total Pemain</h3>
                <p><?php echo $total_players; ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Sekolah Terbaru</h2>
                    <a href="schools.php" class="btn">Lihat Semua Sekolah</a>
                </div>
            </div>
            <div class="card-body">
                <?php if(count($recent_schools) > 0): ?>
                    <table>
                        <tr>
                            <th>Nama Sekolah</th>
                            <th>NPSN</th>
                            <th>Kabupaten</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach($recent_schools as $school): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($school['nama_sekolah']); ?></td>
                                <td><?php echo htmlspecialchars($school['npsn']); ?></td>
                                <td><?php echo htmlspecialchars($school['kabupaten']); ?></td>
                                <td>
                                    <?php 
                                    if($school['status'] == 'approved') {
                                        echo '<span style="color: green; font-weight: bold;">Disetujui</span>';
                                    } elseif($school['status'] == 'rejected') {
                                        echo '<span style="color: red; font-weight: bold;">Ditolak</span>';
                                    } else {
                                        echo '<span style="color: orange; font-weight: bold;">Menunggu</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('d-m-Y H:i', strtotime($school['created_at'])); ?></td>
                                <td>
                                    <a href="view_school.php?id=<?php echo $school['id']; ?>" class="btn btn-info btn-sm">Lihat</a>
                                    <?php if($school['status'] == 'pending'): ?>
                                        <a href="approve_school.php?id=<?php echo $school['id']; ?>" class="btn btn-success btn-sm">Setujui</a>
                                        <a href="reject_school.php?id=<?php echo $school['id']; ?>" class="btn btn-danger btn-sm">Tolak</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>Belum ada sekolah yang terdaftar.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Menu Admin</h2>
                </div>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <a href="schools.php" class="btn" style="text-align: center;">
                        Kelola Sekolah
                    </a>
                    <a href="pending_schools.php" class="btn" style="text-align: center;">
                        Persetujuan Sekolah
                    </a>
                    <a href="all_players.php" class="btn" style="text-align: center;">
                        Data Semua Pemain
                    </a>
                    <a href="export_all.php" class="btn" style="text-align: center;">
                        Export Data
                    </a>
                    <a href="change_password.php" class="btn" style="text-align: center;">
                        Ubah Password
                    </a>
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