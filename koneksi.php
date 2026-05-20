<?php
$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "db_absensi"; 

$kon = mysqli_connect($host, $user, $pass, $db);

if (!$kon) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>