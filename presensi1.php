<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set zona waktu
date_default_timezone_set('Asia/Jakarta'); 

// ==========================================
// KONEKSI DATABASE
// ==========================================
$host = 'localhost';
$user = 'root';
$pass = "";
$db   = 'sistempenggajian';
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Ambil data presensi di-join dengan tabel userkaryawan
// Diurutkan berdasarkan Nama Karyawan (A-Z), lalu Tanggal (Terbaru ke Terlama)
$query_rekap = "SELECT u.nmakaryawan, p.tglPresensi, p.jamMasuk, p.jamKeluar, p.sttsPresensi, p.catatan 
                FROM presensi p 
                JOIN userkaryawan u ON p.id_karyawan = u.id_karyawan 
                ORDER BY u.nmakaryawan ASC, p.tglPresensi DESC";
$result_rekap = mysqli_query($conn, $query_rekap);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan - Presensi</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inherit">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            
            <aside class="sidebar">
                <a href="index.php" class="brand">
                    <img src="assets/logoputih.svg" class="logo" alt="logo">
                </a>
                
                <nav class="nav-menu">
                    <a href="dashboardperusahaan.php" class="nav-item">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <a href="presensi1.php" class="nav-item active">
                        <i class="fa-solid fa-square-check"></i> Presensi
                    </a>
                    <a href="biodata.php" class="nav-item">
                        <i class="fa-solid fa-id-card"></i> Data Karyawan
                    </a>
                    <a href="gaji.php" class="nav-item">
                        <i class="fa-solid fa-calendar-days"></i> Gaji
                    </a>
                    <a href="penjualan.php" class="nav-item">
                        <i class="fa-solid fa-chart-line"></i> Penjualan
                    </a>
                </nav>
                <div class="sidebar-footer">
                    <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Apakah anda yakin ingin logout?');">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </aside>

            <main class="main-content">
                
                <header class="topbar">
                    <div class="toggle-btn">
                        <i class="fa-solid fa-bars"></i>
                    </div>
                    <div class="topbar-right">
                        <div class="search-wrapper">
                            <input type="text" class="search-input" placeholder="Cari...">
                            <i class="fa-solid fa-magnifying-glass icon-btn search-toggle"></i>
                        </div>
                        
                        <span class="user-name">
                            <?php 
                            $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                            $bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                            
                            $indeks_hari = date('w');
                            $indeks_bulan = date('n');
                            
                            echo $hari[$indeks_hari] . ", " . date('j') . " " . $bulan[$indeks_bulan] . " " . date('Y'); 
                            ?>
                        </span>
                    </div>
                </header>

                <div class="content-body">
                    <div class="table-container">
                        <div class="card-header-title">
                            <h3>Rekap Absensi Karyawan</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_rekap && mysqli_num_rows($result_rekap) > 0) {
                                    $karyawan_aktif = ""; // Variabel penanda untuk buat grup
                                    
                                    while ($row = mysqli_fetch_assoc($result_rekap)) {
                                        // LOGIKA KATEGORI: Jika nama karyawan beda dengan baris sebelumnya, buat header nama baru
                                        if ($karyawan_aktif != $row['nmakaryawan']) {
                                            $karyawan_aktif = $row['nmakaryawan'];
                                            echo "<tr style='background-color: #f1f5f9;'>";
                                            echo "<td colspan='5' style='text-align: left; font-weight: 700; color: #1e293b; padding: 12px 15px;'>";
                                            echo "<i class='fa-solid fa-user-tie' style='margin-right: 8px; color: #3e9c35;'></i> " . htmlspecialchars($karyawan_aktif);
                                            echo "</td>";
                                            echo "</tr>";
                                        }

                                        // Format Data Baris
                                        $tgl = date('d F Y', strtotime($row['tglPresensi']));
                                        
                                        // Pengecekan validasi jam (Bypass default MySQL)
                                        $jamMasukValid = !empty($row['jamMasuk']) && $row['jamMasuk'] != '0000-00-00 00:00:00' && $row['jamMasuk'] != '00:00:00';
                                        $jamKeluarValid = !empty($row['jamKeluar']) && $row['jamKeluar'] != '0000-00-00 00:00:00' && $row['jamKeluar'] != '00:00:00';
                                        
                                        $jam_masuk = $jamMasukValid ? date('H:i:s', strtotime($row['jamMasuk'])) : '-';
                                        $jam_keluar = $jamKeluarValid ? date('H:i:s', strtotime($row['jamKeluar'])) : '-';
                                        
                                        $status = htmlspecialchars($row['sttsPresensi'] ?? 'Hadir');
                                        $catatan = !empty($row['catatan']) ? htmlspecialchars($row['catatan']) : '-';

                                        // Pewarnaan status
                                        if (strtolower($status) == 'hadir' || strtolower($status) == 'tepat waktu') {
                                            $statusClass = 'text-success font-weight-bold';
                                        } else {
                                            $statusClass = 'text-warning font-weight-bold';
                                        }

                                        // Print Baris Absen per harinya
                                        echo "<tr>";
                                        echo "<td class='text-bold' style='padding-left: 40px;'>{$tgl}</td>"; // Indentasi ke dalam agar terlihat sub-item
                                        echo "<td>{$jam_masuk}</td>";
                                        echo "<td>{$jam_keluar}</td>";
                                        echo "<td><span class='{$statusClass}'>{$status}</span></td>";
                                        echo "<td>{$catatan}</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Belum ada satupun data presensi karyawan masuk ke database.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>

        </div>
    </body>
</html>