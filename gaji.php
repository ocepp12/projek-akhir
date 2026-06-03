<?php
// 1. KONEKSI DATABASE
// Sesuaikan username 'root' dan password '' dengan konfigurasi server lokalmu.
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistempenggajian";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan - Gaji</title>
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
                    <a href="presensi1.php" class="nav-item">
                        <i class="fa-solid fa-square-check"></i> Presensi
                    </a>
                    <a href="biodata.php" class="nav-item">
                        <i class="fa-solid fa-id-card"></i> Data Karyawan
                    </a>
                    <a href="gaji.php" class="nav-item active"> <i class="fa-solid fa-calendar-days"></i> Gaji
                    </a>
                    <a href="penjualan.php" class="nav-item">
                        <i class="fa-solid fa-chart-line"></i> Penjualan
                    </a>
                </nav>
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
                            date_default_timezone_set('Asia/Jakarta'); 
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
                    <div class="content-header">
                        <h3>Rekap Data Gaji Karyawan</h3>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Gaji</th>
                                    <th>Gaji Pokok</th>
                                    <th>Total Tunjangan</th>
                                    <th>Total Potongan</th>
                                    <th>Gaji Bersih</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 3. QUERY MENGGABUNGKAN (JOIN) TABEL GAJI DAN TUNJANGAN
                                $query = "SELECT g.*, t.* FROM gaji g 
                                          LEFT JOIN tunjangan t ON g.id_tunjangan = t.id_tunjangan";
                                
                                $result = mysqli_query($koneksi, $query);
                                $no = 1;

                                if (mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        
                                        // 4. PERHITUNGAN MATEMATIKA GAJI
                                        // Pastikan dikonversi ke integer untuk menghindari error jika data kosong (NULL)
                                        $gapok = (int)$row['gapok'];
                                        
                                        // Total Tunjangan
                                        $total_tunjangan = (int)$row['makan'] + (int)$row['transport'] + (int)$row['uangLembur'] + 
                                                           (int)$row['insentifPenjualan'] + (int)$row['tunJabatan'] + 
                                                           (int)$row['kompensasi'] + (int)$row['THR'] + (int)$row['BPJS'] + (int)$row['BAT'];
                                        
                                        // Total Potongan
                                        $total_potongan = (int)$row['potPajak'] + (int)$row['potKehadiran'] + (int)$row['pinjaman'];
                                        
                                        // Gaji Bersih = (Gaji Pokok + Tunjangan) - Potongan
                                        $gaji_bersih = ($gapok + $total_tunjangan) - $total_potongan;

                                        // 5. TAMPILKAN BARIS DATA
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td class='text-bold'>" . htmlspecialchars($row['id_gaji']) . "</td>";
                                        echo "<td>Rp " . number_format($gapok, 0, ',', '.') . "</td>";
                                        echo "<td>Rp " . number_format($total_tunjangan, 0, ',', '.') . "</td>";
                                        echo "<td>Rp " . number_format($total_potongan, 0, ',', '.') . "</td>";
                                        echo "<td class='text-company-name'>Rp " . number_format($gaji_bersih, 0, ',', '.') . "</td>";
                                        echo "<td>
                                                <a href='#' class='btn-action-edit'><i class='fa-solid fa-circle-info'></i> Detail</a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    // Jika data di database kosong, tampilkan baris informasi ini
                                    echo "<tr>
                                            <td colspan='7' class='text-center text-muted' style='padding: 30px;'>
                                                <i class='fa-solid fa-folder-open' style='font-size: 24px; margin-bottom: 10px; color: #cbd5e0; display: block;'></i>
                                                Belum ada data gaji yang tersedia di database.
                                            </td>
                                          </tr>";
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