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

// ==========================================
// TANGKAP PARAMETER FILTER
// ==========================================
$filter_jabatan = isset($_GET['filter_jabatan']) ? mysqli_real_escape_string($conn, $_GET['filter_jabatan']) : '';
$filter_level   = isset($_GET['filter_level']) ? mysqli_real_escape_string($conn, $_GET['filter_level']) : '';
$filter_bulan   = isset($_GET['filter_bulan']) ? mysqli_real_escape_string($conn, $_GET['filter_bulan']) : '';
$filter_tahun   = isset($_GET['filter_tahun']) ? mysqli_real_escape_string($conn, $_GET['filter_tahun']) : '';

// ==========================================
// AMBIL DATA UNTUK DROPDOWN FILTER
// ==========================================
$q_jabatan = mysqli_query($conn, "SELECT * FROM jabatan ORDER BY nmaJabatan ASC");
$q_level   = mysqli_query($conn, "SELECT * FROM levelkaryawan ORDER BY nmaLevel ASC");

// Ambil data tahun yang tersedia di tabel presensi
$q_tahun_absen = mysqli_query($conn, "SELECT DISTINCT YEAR(tglPresensi) AS tahun FROM presensi WHERE tglPresensi IS NOT NULL ORDER BY tahun DESC");
$years = [date('Y')]; // Default selalu ada tahun ini
if ($q_tahun_absen) {
    while($thn = mysqli_fetch_assoc($q_tahun_absen)) {
        if(!in_array($thn['tahun'], $years) && !empty($thn['tahun'])) {
            $years[] = $thn['tahun'];
        }
    }
}
rsort($years); // Urutkan tahun dari yang terbaru

// ==========================================
// QUERY UTAMA PRESENSI (DENGAN FILTER)
// ==========================================
$query_rekap = "SELECT u.nmakaryawan, p.tglPresensi, p.jamMasuk, p.jamKeluar, p.sttsPresensi, p.catatan,
                       j.nmaJabatan, l.nmaLevel
                FROM presensi p 
                JOIN userkaryawan u ON p.id_karyawan = u.id_karyawan 
                LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan
                LEFT JOIN levelkaryawan l ON u.id_level = l.id_level
                WHERE 1=1 ";

// Logika penambahan filter jika dipilih
if (!empty($filter_jabatan)) {
    $query_rekap .= " AND u.id_jabatan = '$filter_jabatan' ";
}
if (!empty($filter_level)) {
    $query_rekap .= " AND u.id_level = '$filter_level' ";
}
if (!empty($filter_bulan)) {
    $query_rekap .= " AND MONTH(p.tglPresensi) = '$filter_bulan' ";
}
if (!empty($filter_tahun)) {
    $query_rekap .= " AND YEAR(p.tglPresensi) = '$filter_tahun' ";
}

// Diurutkan berdasarkan Nama Karyawan (A-Z), lalu Tanggal (Terbaru ke Terlama)
$query_rekap .= " ORDER BY u.nmakaryawan ASC, p.tglPresensi DESC";
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
            
            <aside class="sidebar active">
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
                    <a href="master_gaji.php" class="nav-item">
                        <i class="fa-solid fa-gears"></i> Manajemen Gaji</a>
                </nav>
                <div class="sidebar-footer">
                    <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Apakah anda yakin ingin logout?');">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </aside>

            <main class="main-content sidebar-active">
                
                <header class="topbar">
                    <div class="toggle-btn">
                        <i class="fa-solid fa-bars"></i>
                    </div>
                    <div class="topbar-right">
                        
                        <span class="user-name">
                            <?php 
                            $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                            $bulan_teks = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                            
                            $indeks_hari = date('w');
                            $indeks_bulan = date('n');
                            
                            echo $hari[$indeks_hari] . ", " . date('j') . " " . $bulan_teks[$indeks_bulan] . " " . date('Y'); 
                            ?>
                        </span>
                    </div>
                </header>

                <div class="content-body">
                    
                    <div class="form-container filter-box">
                        <form method="GET" action="presensi1.php" class="filter-form">
                            
                            <div class="filter-group">
                                <label class="filter-label">Filter Jabatan</label>
                                <select name="filter_jabatan" class="form-control filter-select">
                                    <option value="">-- Semua Jabatan --</option>
                                    <?php 
                                    mysqli_data_seek($q_jabatan, 0); // Reset pointer
                                    while($jb = mysqli_fetch_assoc($q_jabatan)) { 
                                        $sel = ($filter_jabatan == $jb['id_jabatan']) ? 'selected' : '';
                                        echo "<option value='".$jb['id_jabatan']."' $sel>".$jb['nmaJabatan']."</option>";
                                    } 
                                    ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Filter Level</label>
                                <select name="filter_level" class="form-control filter-select">
                                    <option value="">-- Semua Level --</option>
                                    <?php 
                                    mysqli_data_seek($q_level, 0); // Reset pointer
                                    while($lv = mysqli_fetch_assoc($q_level)) { 
                                        $sel = ($filter_level == $lv['id_level']) ? 'selected' : '';
                                        echo "<option value='".$lv['id_level']."' $sel>".$lv['nmaLevel']."</option>";
                                    } 
                                    ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Filter Bulan</label>
                                <select name="filter_bulan" class="form-control filter-select">
                                    <option value="">-- Semua Bulan --</option>
                                    <?php 
                                    $bulan_arr = [1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"];
                                    foreach ($bulan_arr as $num => $name) {
                                        $sel = ($filter_bulan == $num) ? 'selected' : '';
                                        echo "<option value='$num' $sel>$name</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">Filter Tahun</label>
                                <select name="filter_tahun" class="form-control filter-select">
                                    <option value="">-- Semua Tahun --</option>
                                    <?php 
                                    foreach($years as $t) {
                                        $sel = ($filter_tahun == $t) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="filter-actions">
                                <button type="submit" class="btn-submit"><i class="fa-solid fa-filter"></i> Terapkan</button>
                                <?php if(!empty($filter_jabatan) || !empty($filter_level) || !empty($filter_bulan) || !empty($filter_tahun)): ?>
                                    <a href="presensi1.php" class="btn-cancel">Reset</a>
                                <?php endif; ?>
                            </div>

                        </form>
                    </div>

                    <div class="table-container">
                        <div class="card-header-title">
                            <h3>Rekap Presensi Karyawan</h3>
                        </div>

                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Pulang</th>
                                    <th>Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result_rekap && mysqli_num_rows($result_rekap) > 0) {
                                    $karyawan_aktif = ""; 
                                    
                                    while ($row = mysqli_fetch_assoc($result_rekap)) {
                                        // LOGIKA KATEGORI: Jika nama karyawan beda, buat header grup baru
                                        if ($karyawan_aktif != $row['nmakaryawan']) {
                                            $karyawan_aktif = $row['nmakaryawan'];
                                            
                                            $info_jabatan = !empty($row['nmaJabatan']) ? $row['nmaJabatan'] : '-';
                                            $info_level   = !empty($row['nmaLevel']) ? $row['nmaLevel'] : '-';
                                            
                                            echo "<tr class='group-header-row'>";
                                            echo "<td colspan='5' class='group-header-cell'>";
                                            echo "<i class='fa-solid fa-user-tie group-header-icon'></i> " . htmlspecialchars($karyawan_aktif) . " <span class='group-header-subtitle'>(" . htmlspecialchars($info_jabatan . " - " . $info_level) . ")</span>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }

                                        // Format Data Baris
                                        $tgl = date('d F Y', strtotime($row['tglPresensi']));
                                        
                                        $jamMasukValid = !empty($row['jamMasuk']) && $row['jamMasuk'] != '0000-00-00 00:00:00' && $row['jamMasuk'] != '00:00:00';
                                        $jamKeluarValid = !empty($row['jamKeluar']) && $row['jamKeluar'] != '0000-00-00 00:00:00' && $row['jamKeluar'] != '00:00:00';
                                        
                                        $jam_masuk = $jamMasukValid ? date('H:i:s', strtotime($row['jamMasuk'])) : '-';
                                        $jam_keluar = $jamKeluarValid ? date('H:i:s', strtotime($row['jamKeluar'])) : '-';
                                        
                                        $status = htmlspecialchars($row['sttsPresensi'] ?? 'Hadir');
                                        $catatan = !empty($row['catatan']) ? htmlspecialchars($row['catatan']) : '-';

                                        // Pewarnaan status bawaan
                                        if (strtolower($status) == 'hadir' || strtolower($status) == 'tepat waktu') {
                                            $statusClass = 'text-success font-weight-bold';
                                        } else {
                                            $statusClass = 'text-warning font-weight-bold';
                                        }

                                        // Print Baris Absen per harinya
                                        echo "<tr>";
                                        echo "<td class='text-bold indent-cell'>{$tgl}</td>"; 
                                        echo "<td>{$jam_masuk}</td>";
                                        echo "<td>{$jam_keluar}</td>";
                                        echo "<td><span class='{$statusClass}'>{$status}</span></td>";
                                        echo "<td>{$catatan}</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>Belum ada satupun data presensi karyawan sesuai filter tersebut.</td></tr>";
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