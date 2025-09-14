<?php
// Include config file
require_once "config.php";

// SQL to create schools table
$sql_schools = "CREATE TABLE IF NOT EXISTS schools (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nama_sekolah VARCHAR(255) NOT NULL,
    npsn VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nomor_hp VARCHAR(20) NOT NULL,
    kabupaten VARCHAR(100) NOT NULL,
    logo_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// SQL to create players table
$sql_players = "CREATE TABLE IF NOT EXISTS players (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    nama_pemain VARCHAR(255) NOT NULL,
    nik VARCHAR(20) NOT NULL,
    nip VARCHAR(50),
    nuptk VARCHAR(50),
    tempat_lahir VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    jabatan VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    foto_path VARCHAR(255),
    ktp_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
)";

// SQL to create admins table
$sql_admins = "CREATE TABLE IF NOT EXISTS admins (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Execute the SQL statements
if (mysqli_query($conn, $sql_schools)) {
    echo "Table 'schools' created successfully<br>";
} else {
    echo "Error creating table 'schools': " . mysqli_error($conn) . "<br>";
}

if (mysqli_query($conn, $sql_players)) {
    echo "Table 'players' created successfully<br>";
} else {
    echo "Error creating table 'players': " . mysqli_error($conn) . "<br>";
}

if (mysqli_query($conn, $sql_admins)) {
    echo "Table 'admins' created successfully<br>";
} else {
    echo "Error creating table 'admins': " . mysqli_error($conn) . "<br>";
}

// Create default admin user if not exists
$admin_username = "admin";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Default password: admin123

$check_admin = "SELECT * FROM admins WHERE username = '$admin_username'";
$result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($result) == 0) {
    $sql_insert_admin = "INSERT INTO admins (username, password) VALUES ('$admin_username', '$admin_password')";
    if (mysqli_query($conn, $sql_insert_admin)) {
        echo "Default admin user created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "Error creating default admin user: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Admin user already exists<br>";
}

// Close connection
mysqli_close($conn);
?>