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
$sql = "SELECT * FROM schools WHERE status = 'pending'";
$params = array();
$types = "";

if(!empty($search)){
    $sql .= " AND (nama_sekolah LIKE ? OR npsn LIKE ? OR kabupaten LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY created_at ASC LIMIT ?, ?";
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
$sql_count = "SELECT COUNT(*) as total FROM schools WHERE status = 'pending'";
$count_params = array();
$count_types = "";

if(!empty($search)){
    $sql_count .= " AND (nama_sekolah LIKE ? OR npsn LIKE ? OR kabupaten LIKE ?)";
    $search_param = "%" . $search . "%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
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
    <title>Persetujuan Sekolah - Admin Dashboard</title>
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
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
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
            <h1>Persetujuan Sekolah</h1>
            <div>
                <a href="index.php" class="btn">Kembali ke Dashboard</a>
            </div>
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
                <h2>Daftar Sekolah Menunggu Persetujuan</h2>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
                    <div style="display: flex;">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, NPSN, atau kabupaten..." value="<?php echo $search; ?>" style="margin-right: 10px;">
                        <button type="submit" class="btn">Cari</button>
                        <?php if(!empty($search)): ?>
                            <a href="pending_schools.php" class="btn btn-secondary" style="margin-left: 10px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if(count($schools) > 0): ?>
                    <table>
                        <tr>
                            <th>Logo</th>
                            <th>Nama Sekolah</th>
                            <th>NPSN</th>
                            <th>Nomor HP</th>
                            <th>Kabupaten</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach($schools as $school): ?>
                            <tr>
                                <td><img src="../<?php echo $school['logo_path']; ?>" alt="Logo Sekolah" class="school-logo"></td>
                                <td><?php echo htmlspecialchars($school['nama_sekolah']); ?></td>
                                <td><?php echo htmlspecialchars($school['npsn']); ?></td>
                                <td><?php echo htmlspecialchars($school['nomor_hp']); ?></td>
                                <td><?php echo htmlspecialchars($school['kabupaten']); ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($school['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <a href="view_school.php?id=<?php echo $school['id']; ?>" class="btn btn-info btn-sm">Lihat</a>
                                    <a href="approve_school.php?id=<?php echo $school['id']; ?>" class="btn btn-success btn-sm">Setujui</a>
                                    <a href="reject_school.php?id=<?php echo $school['id']; ?>" class="btn btn-danger btn-sm">Tolak</a>
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
                            <p style="margin-top: 10px;">Menampilkan <?php echo count($schools); ?> dari <?php echo $total_records; ?> sekolah</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Tidak ada sekolah yang menunggu persetujuan<?php echo !empty($search) ? ' dengan kriteria pencarian tersebut' : ''; ?>.</p>
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