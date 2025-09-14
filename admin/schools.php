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
$status_filter = "";
$page = 1;
$records_per_page = 10;

// Process search query if submitted
if(isset($_GET["search"])){
    $search = sanitize_input($_GET["search"]);
}

// Process status filter if submitted
if(isset($_GET["status"]) && in_array($_GET["status"], ['all', 'pending', 'approved', 'rejected'])){
    $status_filter = $_GET["status"];
}

// Process pagination
if(isset($_GET["page"]) && is_numeric($_GET["page"])){
    $page = $_GET["page"];
}

// Calculate the offset for SQL LIMIT
$offset = ($page - 1) * $records_per_page;

// Prepare SQL query with search, filter, and pagination
$sql = "SELECT * FROM schools";
$conditions = array();
$params = array();
$types = "";

if(!empty($search)){
    $conditions[] = "(nama_sekolah LIKE ? OR npsn LIKE ? OR kabupaten LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if(!empty($status_filter) && $status_filter != 'all'){
    $conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if(count($conditions) > 0){
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY created_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Prepare statement
$schools = array();
if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    if(count($params) > 0){
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        // Fetch all schools
        while($row = mysqli_fetch_array($result)){
            $schools[] = $row;
        }
    } else{
        echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Count total records for pagination
$total_records = 0;
$sql_count = "SELECT COUNT(*) as total FROM schools";
$count_conditions = array();
$count_params = array();
$count_types = "";

if(!empty($search)){
    $count_conditions[] = "(nama_sekolah LIKE ? OR npsn LIKE ? OR kabupaten LIKE ?)";
    $search_param = "%" . $search . "%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if(!empty($status_filter) && $status_filter != 'all'){
    $count_conditions[] = "status = ?";
    $count_params[] = $status_filter;
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

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);
?>
 
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sekolah - Admin Dashboard</title>
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
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="admin-title">Admin Dashboard</div>
            <div class="admin-user">
                <span>Welcome Admin kcdvicup</span>
                <a href="logout.php" class="btn btn-sm">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>Kelola Sekolah</h1>
            <a href="index.php" class="btn">Kembali ke Dashboard</a>
        </div>
        
        <?php
        // Display success message if any
        if(isset($_GET["success"])){
            if($_GET["success"] == "approved"){
                echo '<div class="alert alert-success">Sekolah berhasil disetujui!</div>';
            } elseif($_GET["success"] == "rejected"){
                echo '<div class="alert alert-success">Sekolah berhasil ditolak!</div>';
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Daftar Sekolah</h2>
                
                <div class="filter-container">
                    <div class="filter-item">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
                            <div style="display: flex;">
                                <input type="text" name="search" class="form-control" placeholder="Cari nama, NPSN, atau kabupaten..." value="<?php echo $search; ?>" style="margin-right: 10px;">
                                <button type="submit" class="btn">Cari</button>
                                <?php if(!empty($search) || !empty($status_filter)): ?>
                                    <a href="schools.php" class="btn btn-secondary" style="margin-left: 10px;">Reset</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($status_filter)): ?>
                                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="filter-item">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" id="statusForm">
                            <div style="display: flex;">
                                <select name="status" class="form-control" onchange="document.getElementById('statusForm').submit()">
                                    <option value="all" <?php if($status_filter == "all" || empty($status_filter)) echo "selected"; ?>>Semua Status</option>
                                    <option value="pending" <?php if($status_filter == "pending") echo "selected"; ?>>Menunggu Persetujuan</option>
                                    <option value="approved" <?php if($status_filter == "approved") echo "selected"; ?>>Disetujui</option>
                                    <option value="rejected" <?php if($status_filter == "rejected") echo "selected"; ?>>Ditolak</option>
                                </select>
                            </div>
                            
                            <?php if(!empty($search)): ?>
                                <input type="hidden" name="search" value="<?php echo $search; ?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if(count($schools) > 0): ?>
                    <table>
                        <tr>
                            <th>Nama Sekolah</th>
                            <th>NPSN</th>
                            <th>Nomor HP</th>
                            <th>Kabupaten</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach($schools as $school): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($school['nama_sekolah']); ?></td>
                                <td><?php echo htmlspecialchars($school['npsn']); ?></td>
                                <td><?php echo htmlspecialchars($school['nomor_hp']); ?></td>
                                <td><?php echo htmlspecialchars($school['kabupaten']); ?></td>
                                <td>
                                    <?php if($school['status'] == 'approved'): ?>
                                        <span class="status-badge status-approved">Disetujui</span>
                                    <?php elseif($school['status'] == 'rejected'): ?>
                                        <span class="status-badge status-rejected">Ditolak</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Menunggu</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d-m-Y H:i', strtotime($school['created_at'])); ?></td>
                                <td>
                                    <a href="view_school.php?id=<?php echo $school['id']; ?>" class="btn btn-info btn-sm">Lihat</a>
                                    <?php if($school['status'] == 'pending'): ?>
                                        <a href="approve_school.php?id=<?php echo $school['id']; ?>" class="btn btn-success btn-sm">Setujui</a>
                                        <a href="reject_school.php?id=<?php echo $school['id']; ?>" class="btn btn-danger btn-sm">Tolak</a>
                                    <?php elseif($school['status'] == 'rejected'): ?>
                                        <a href="approve_school.php?id=<?php echo $school['id']; ?>" class="btn btn-success btn-sm">Setujui</a>
                                    <?php elseif($school['status'] == 'approved'): ?>
                                        <a href="reject_school.php?id=<?php echo $school['id']; ?>" class="btn btn-danger btn-sm">Tolak</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <div class="pagination">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-sm">&laquo; Prev</a>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if($i == $page): ?>
                                        <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-sm"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>" class="btn btn-sm">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                            <p style="margin-top: 10px;">Menampilkan <?php echo count($schools); ?> dari <?php echo $total_records; ?> sekolah</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Belum ada sekolah yang terdaftar<?php echo !empty($search) || !empty($status_filter) ? ' dengan kriteria pencarian tersebut' : ''; ?>.</p>
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