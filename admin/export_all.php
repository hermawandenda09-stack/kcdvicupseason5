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

// Get filter parameters if any
$search = isset($_GET["search"]) ? sanitize_input($_GET["search"]) : "";
$school_filter = isset($_GET["school_id"]) ? sanitize_input($_GET["school_id"]) : "";
$jabatan_filter = isset($_GET["jabatan"]) ? sanitize_input($_GET["jabatan"]) : "";

// Prepare SQL query with filters
$sql = "SELECT p.*, s.nama_sekolah, s.npsn, s.kabupaten FROM players p 
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

$sql .= " ORDER BY s.nama_sekolah ASC, p.nama_pemain ASC";

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
        exit;
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Close connection
mysqli_close($conn);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Data_Semua_Pemain_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Semua Pemain</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subheader {
            font-size: 14px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">Data Semua Pemain Mini Soccer Registration</div>
    <div class="subheader">Tanggal Export: <?php echo date('d-m-Y H:i'); ?></div>
    
    <table>
        <tr>
            <th>No</th>
            <th>Nama Sekolah</th>
            <th>NPSN</th>
            <th>Kabupaten</th>
            <th>Nama Pemain</th>
            <th>NIK</th>
            <th>NIP</th>
            <th>NUPTK</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Jabatan</th>
            <th>Alamat</th>
            <th>Status</th>
            <th>Tanggal Pendaftaran</th>
        </tr>
        <?php 
        $no = 1;
        foreach($players as $player): 
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $player['nama_sekolah']; ?></td>
            <td><?php echo $player['npsn']; ?></td>
            <td><?php echo $player['kabupaten']; ?></td>
            <td><?php echo $player['nama_pemain']; ?></td>
            <td><?php echo $player['nik']; ?></td>
            <td><?php echo $player['nip']; ?></td>
            <td><?php echo $player['nuptk']; ?></td>
            <td><?php echo $player['tempat_lahir']; ?></td>
            <td><?php echo date('d-m-Y', strtotime($player['tanggal_lahir'])); ?></td>
            <td><?php echo $player['jabatan']; ?></td>
            <td><?php echo $player['alamat']; ?></td>
            <td><?php echo ($player['status'] == 'active') ? 'Aktif' : 'Tidak Aktif'; ?></td>
            <td><?php echo date('d-m-Y', strtotime($player['created_at'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>