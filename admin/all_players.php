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

// Define variables for search and pagination
$search = "";
$school_filter = "";
$jabatan_filter = "";
$page = 1;
$records_per_page = 10;

// Process search query if submitted
if(isset($_GET["search"])){
    $search = sanitize_input($_GET["search"]);
}

// Process school filter if submitted
if(isset($_GET["school_id"]) && !empty($_GET["school_id"])){
    $school_filter = sanitize_input($_GET["school_id"]);
}

// Process jabatan filter if submitted
if(isset($_GET["jabatan"]) && !empty($_GET["jabatan"])){
    $jabatan_filter = sanitize_input($_GET["jabatan"]);
}

// Process pagination
if(isset($_GET["page"]) && is_numeric($_GET["page"])){
    $page = $_GET["page"];
}

// Calculate the offset for SQL LIMIT
$offset = ($page - 1) * $records_per_page;

// Prepare SQL query with search, filter, and pagination
$sql = "SELECT p.*, s.nama_sekolah, s.npsn FROM players p 
        JOIN schools s ON p.school_id = s.id";
$conditions = array();
$params = array();
$types = "";

if(!empty($search)){
    $conditions[] = "(p.nama_pemain LIKE ? OR p.nik LIKE ? OR s.nama_sekolah LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if(!empty($school_filter)){
    $conditions[] = "p.school_id = ?";
    $params[] = $school_filter;
    $types .= "i";
}

if(!empty($jabatan_filter)){
    $conditions[] = "p.jabatan = ?";
    $params[] = $jabatan_filter;
    $types .= "s";
}

if(count($conditions) > 0){
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY p.created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Prepare statement
$players = array();
if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    if(count($params) > 0){
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    // Attempt to execute the prepared statement
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
$sql_count = "SELECT COUNT(*) as total FROM players p 
              JOIN schools s ON p.school_id = s.id";
$count_conditions = array();
$count_params = array();
$count_types = "";

if(!empty($search)){
    $count_conditions[] = "(p.nama_pemain LIKE ? OR p.nik LIKE ? OR s.nama_sekolah LIKE ?)";
    $search_param = "%" . $search . "%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if(!empty($school_filter)){
    $count_conditions[] = "p.school_id = ?";
    $count_params[] = $school_filter;
    $count_types .= "i";
}

if(!empty($jabatan_filter)){
    $count_conditions[] = "p.jabatan = ?";
    $count_params[] = $jabatan_filter;
    $count_types .= "s";
}

if(count($count_conditions) > 0){
    $sql_count .= " WHERE " . implode(" AND ", $count_conditions);
}

if($stmt_count = mysqli_prepare($conn, $sql_count)){
    // Bind variables to the prepared statement as parameters
    if(count($count_params) > 0){
        mysqli_stmt_bind_param($stmt_count, $count_types, ...$count_params);
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

// Get all schools for filter dropdown
$schools = array();
$sql_schools = "SELECT id, nama_sekolah FROM schools ORDER BY nama_sekolah ASC";
if($result_schools = mysqli_query($conn, $sql_schools)){
    while($row = mysqli_fetch_assoc($result_schools)){
        $schools[] = $row;
    }
}

// Get all jabatan for filter dropdown
$jabatan_list = array();
$sql_jabatan = "SELECT DISTINCT jabatan FROM players ORDER BY jabatan ASC";
if($result_jabatan = mysqli_query($conn, $sql_jabatan)){
    while($row = mysqli_fetch_assoc($result_jabatan)){
        $jabatan_list[] = $row['jabatan'];
    }
}

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Semua Pemain - Admin Dashboard</title>
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
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-item {
            flex: 1;
            min-width: 200px;
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
            <h1>Data Semua Pemain</h1>
            <div>
                <a href="export_all.php" class="btn">Export Semua Data (XLS)</a>
                <a href="index.php" class="btn">Kembali ke Dashboard</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Daftar Pemain</h2>
                
                <div class="filter-container">
                    <div class="filter-item">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" id="searchForm">
                            <div style="display: flex;">
                                <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, atau sekolah..." value="<?php echo $search; ?>" style="margin-right: 10px;">
                                <button type="submit" class="btn">Cari</button>
                                <?php if(!empty($search) || !empty($school_filter) || !empty($jabatan_filter)): ?>
                                    <a href="all_players.php" class="btn btn-secondary" style="margin-left: 10px;">Reset</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($school_filter)): ?>
                                <input type="hidden" name="school_id" value="<?php echo $school_filter; ?>">
                            <?php endif; ?>
                            
                            <?php if(!empty($jabatan_filter)): ?>
                                <input type="hidden" name="jabatan" value="<?php echo $jabatan_filter; ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="filter-item">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" id="schoolForm">
                            <div style="display: flex;">
                                <select name="school_id" class="form-control" onchange="document.getElementById('schoolForm').submit()">
                                    <option value="">Semua Sekolah</option>
                                    <?php foreach($schools as $school): ?>
                                        <option value="<?php echo $school['id']; ?>" <?php if($school_filter == $school['id']) echo "selected"; ?>>
                                            <?php echo htmlspecialchars($school['nama_sekolah']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if(!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo $search; ?>">
                            <?php endif; ?>
                            
                            <?php if(!empty($jabatan_filter)): ?>
                                <input type="hidden" name="jabatan" value="<?php echo $jabatan_filter; ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="filter-item">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" id="jabatanForm">
                            <div style="display: flex;">
                                <select name="jabatan" class="form-control" onchange="document.getElementById('jabatanForm').submit()">
                                    <option value="">Semua Jabatan</option>
                                    <?php foreach($jabatan_list as $jabatan): ?>
                                        <option value="<?php echo $jabatan; ?>" <?php if($jabatan_filter == $jabatan) echo "selected"; ?>>
                                            <?php echo htmlspecialchars($jabatan); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if(!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo $search; ?>">
                            <?php endif; ?>
                            
                            <?php if(!empty($school_filter)): ?>
                                <input type="hidden" name="school_id" value="<?php echo $school_filter; ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if(count($players) > 0): ?>
                    <table>
                        <tr>
                            <th>Foto</th>
                            <th>Nama Pemain</th>
                            <th>NIK</th>
                            <th>Sekolah</th>
                            <th>Jabatan</th>
                            <th>Tempat, Tanggal Lahir</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach($players as $player): ?>
                            <tr>
                                <td><img src="../<?php echo $player['foto_path']; ?>" alt="Foto Pemain" class="player-photo"></td>
                                <td><?php echo htmlspecialchars($player['nama_pemain']); ?></td>
                                <td><?php echo htmlspecialchars($player['nik']); ?></td>
                                <td><?php echo htmlspecialchars($player['nama_sekolah']) . ' (' . htmlspecialchars($player['npsn']) . ')'; ?></td>
                                <td><?php echo htmlspecialchars($player['jabatan']); ?></td>
                                <td><?php echo htmlspecialchars($player['tempat_lahir']) . ', ' . date('d-m-Y', strtotime($player['tanggal_lahir'])); ?></td>
                                <td>
                                    <a href="view_player.php?id=<?php echo $player['id']; ?>" class="btn btn-info btn-sm">Lihat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($school_filter) ? '&school_id=' . urlencode($school_filter) : ''; ?><?php echo !empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : ''; ?>" class="btn btn-sm">&laquo; Prev</a>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if($i == $page): ?>
                                        <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($school_filter) ? '&school_id=' . urlencode($school_filter) : ''; ?><?php echo !empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : ''; ?>" class="btn btn-sm"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($school_filter) ? '&school_id=' . urlencode($school_filter) : ''; ?><?php echo !empty($jabatan_filter) ? '&jabatan=' . urlencode($jabatan_filter) : ''; ?>" class="btn btn-sm">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                            <p style="margin-top: 10px;">Menampilkan <?php echo count($players); ?> dari <?php echo $total_records; ?> pemain</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Belum ada pemain yang terdaftar<?php echo !empty($search) || !empty($school_filter) || !empty($jabatan_filter) ? ' dengan kriteria pencarian tersebut' : ''; ?>.</p>
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