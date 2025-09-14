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

// Get school ID and name
$school_id = $_SESSION["id"];
$school_name = $_SESSION["nama_sekolah"];

// Prepare SQL query to get all players
$sql = "SELECT * FROM players WHERE school_id = ? ORDER BY nama_pemain ASC";

// Prepare statement
if($stmt = mysqli_prepare($conn, $sql)){
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    
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
        exit;
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// Close connection
mysqli_close($conn);

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Data_Pemain_' . str_replace(' ', '_', $school_name) . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Output Excel content
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Pemain <?php echo $school_name; ?></title>
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
    <div class="header">Data Pemain <?php echo $school_name; ?></div>
    <div class="subheader">Tanggal Export: <?php echo date('d-m-Y H:i'); ?></div>
    
    <table>
        <tr>
            <th>No</th>
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