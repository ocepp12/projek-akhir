<?php
// 1. Ambil atau hubungkan ke session yang sedang aktif saat ini
session_start();

// 2. Tentukan tujuan default (jaga-jaga kalau ada error)
$halaman_tujuan = "index.php";

// 3. Cek siapa yang lagi login berdasarkan nama session-nya SEBELUM dihapus
if (isset($_SESSION['loginKaryawan']) || isset($_SESSION['id_karyawan'])) {
    // Jika yang terdeteksi adalah session karyawan, arahkan ke form login karyawan
    $halaman_tujuan = "loginkaryawan.php"; 
} elseif (isset($_SESSION['id_perusahaan']) || isset($_SESSION['emaillogin'])) {
    // Jika yang terdeteksi adalah session perusahaan, arahkan ke landing page
    $halaman_tujuan = "index.php"; 
}

// 4. Hapus semua variabel session yang tersimpan
session_unset();

// 5. Hancurkan session total dari server
session_destroy();

// 6. Alihkan user ke halaman yang sudah ditentukan
header("Location: " . $halaman_tujuan);
exit;
?>