<?php
// 1. Ambil atau hubungkan ke session yang sedang aktif saat ini
session_start();

// 2. Hapus semua variabel session yang tersimpan (email, id_perusahaan, dll)
session_unset();

// 3. Hancurkan/pemberangusan session total dari memori server
session_destroy();

// 4. Setelah bersih, tendang user kembali ke landingpage biar gak bisa akses dashboard lagi
header("Location: index.php");
exit;
?>