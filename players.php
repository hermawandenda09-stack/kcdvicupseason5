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

// Get school ID
$school_id = $_SESSION["id"];

// Define variables for search and pagination
$search = "";
$page = 1;
$records_per_page = 10;

// Process search query if submitted
if(isset($_GET["search"])){
    $search = sanitize_input($_GET["search"]);
}

// Process pagination
if(isset($_GET["page"]) && is_numeric($_GET["page"])){
    $page = $_GET["page"];
}

// Calculate the offset for SQL LIMIT
$offset = ($page - 1) * $records_per_page;

// Prepare SQL query with search and pagination
$sql = "SELECT * FROM players WHERE school_id = ?";
if(!empty($search)){
    $sql .= " AND (nama_pemain LIKE ? OR nik LIKE ? OR jabatan LIKE ?)";
}
$sql .= " ORDER BY created_at DESC LIMIT ?, ?";

// Prepare statement
if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    if(!empty($search)){
        $search_param = "%" . $search . "%";
        mysqli_stmt_bind_param($stmt, "isssi", $school_id, $search_param, $search_param, $search_param, $offset, $records_per_page);
    } else {
        mysqli_stmt_bind_param($stmt, "iii", $school_id, $offset, $records_per_page);
    }
    
    // Attempt to execute the prepared statement
    $players = array();
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        // Fetch all players
        while($row = mysqli_fetch_array($result)){
            $players[] = $row;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Count total records for pagination
$total_records = 0;
$sql_count = "SELECT COUNT(*) as total FROM players WHERE school_id = ?";
if(!empty($search)){
    $sql_count .= " AND (nama_pemain LIKE ? OR nik LIKE ? OR jabatan LIKE ?)";
}

if($stmt_count = mysqli_prepare($conn, $sql_count)){
    // Bind variables to the prepared statement as parameters
    if(!empty($search)){
        $search_param = "%" . $search . "%";
        mysqli_stmt_bind_param($stmt_count, "isss", $school_id, $search_param, $search_param, $search_param);
    } else {
        mysqli_stmt_bind_param($stmt_count, "i", $school_id);
    }
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt_count)){
        $result_count = mysqli_stmt_get_result($stmt_count);
        $row_count = mysqli_fetch_array($result_count);
        $total_records = $row_count['total'];
    }
    
    // Close statement
    mysqli_stmt_close($stmt_count);
}

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pemain - Mini Soccer Registration</title>
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
        <h1>Daftar Pemain</h1>
        
        <?php
        // Display success message if any
        if(isset($_GET["success"])){
            if($_GET["success"] == "added"){
                echo '<div class="alert alert-success">Pemain berhasil ditambahkan!</div>';
            } elseif($_GET["success"] == "updated"){
                echo '<div class="alert alert-success">Data pemain berhasil diperbarui!</div>';
            } elseif($_GET["success"] == "deleted"){
                echo '<div class="alert alert-success">Pemain berhasil dihapus!</div>';
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Daftar Pemain Terdaftar</h2>
                    <div>
                        <a href="add_player.php" class="btn btn-success">Tambah Pemain</a>
                        <a href="export.php" class="btn">Export XLS</a>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" style="margin-top: 15px;">
                    <div style="display: flex;">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, atau jabatan..." value="<?php echo $search; ?>" style="margin-right: 10px;">
                        <button type="submit" class="btn">Cari</button>
                        <?php if(!empty($search)): ?>
                            <a href="players.php" class="btn btn-secondary" style="margin-left: 10px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if(count($players) > 0): ?>
                    <table>
                        <tr>
                            <th>Nama Pemain</th>
                            <th>NIK</th>
                            <th>Tempat, Tanggal Lahir</th>
                            <th>Jabatan</th>
                            <th>Foto</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach($players as $player): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($player['nama_pemain']); ?></td>
                                <td><?php echo htmlspecialchars($player['nik']); ?></td>
                                <td><?php echo htmlspecialchars($player['tempat_lahir']) . ', ' . date('d-m-Y', strtotime($player['tanggal_lahir'])); ?></td>
                                <td><?php echo htmlspecialchars($player['jabatan']); ?></td>
                                <td><img src="<?php echo $player['foto_path']; ?>" alt="Foto Pemain" class="thumbnail"></td>
                                <td>
                                    <a href="view_player.php?id=<?php echo $player['id']; ?>" class="btn btn-info btn-sm">Lihat</a>
                                    <a href="edit_player.php?id=<?php echo $player['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <a href="delete_player.php?id=<?php echo $player['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pemain ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">&laquo; Prev</a>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if($i == $page): ?>
                                        <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                            <p style="margin-top: 10px;">Menampilkan <?php echo count($players); ?> dari <?php echo $total_records; ?> pemain</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Belum ada pemain yang terdaftar<?php echo !empty($search) ? ' dengan kriteria pencarian tersebut' : ''; ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close connection
mysqli_close($conn);
?>