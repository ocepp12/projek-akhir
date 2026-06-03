<?php
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi ke database
$host = 'localhost';
$user = 'root';
$pass = "";
$db   = 'sistempenggajian';
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

session_start();

// Validasi Session Login Karyawan
if (!isset($_SESSION['loginKaryawan'])) {
    header("Location: loginkaryawan.php");
    exit;
}

$id_karyawan = mysqli_real_escape_string($conn, $_SESSION['id_karyawan'] ?? '');

// Ambil data profil langsung dari tabel userkaryawan berdasarkan id_karyawan yang sedang login
$query_user = mysqli_query($conn, "SELECT * FROM userkaryawan WHERE id_karyawan = '$id_karyawan'");
$data_user = mysqli_fetch_assoc($query_user);

// Mapping data profil karyawan
$nmaKaryawan = $data_user['nmakaryawan'] ?? $data_user['nmaKaryawan'] ?? 'Karyawan'; 
$alamat      = $data_user['alamat'] ?? 'Belum diatur';
$status_kerja = $data_user['status'] ?? 'Aktif'; 

// Mengambil dan memformat Tanggal Gabung ke format Indonesia
$tglGabung = 'Belum diketahui';
if (!empty($data_user['tglGabung'])) {
    $time = strtotime($data_user['tglGabung']);
    $bln = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $tglGabung = date('j', $time) . ' ' . $bln[date('n', $time)] . ' ' . date('Y', $time);
}

// =========================================================================
// SCRIPT AMBIL DATA RINGKASAN GAJI BULAN INI (KOTAK 1)
// =========================================================================
$gaji_pokok = 0;
$tunjangan = 0;
$bonus_penjualan = 0;

// 1. Ambil data Gaji Pokok & Tunjangan berdasarkan id_gaji karyawan
$id_gaji_user = $data_user['id_gaji'] ?? '';
if (!empty($id_gaji_user)) {
    $query_gaji = mysqli_query($conn, "SELECT * FROM gaji WHERE id_gaji = '$id_gaji_user'");
    if ($query_gaji && mysqli_num_rows($query_gaji) > 0) {
        $data_gaji = mysqli_fetch_assoc($query_gaji);
        $gaji_pokok = $data_gaji['gaji_pokok'] ?? $data_gaji['nominal'] ?? $data_gaji['gapok'] ?? 0;
        $tunjangan = $data_gaji['tunjangan'] ?? 0;
    }
}

// Fallback: Jika relasi id_gaji kosong, coba tarik dari tabel jabatan sebagai alternatif
if ($gaji_pokok == 0) {
    $id_jabatan_user = $data_user['id_jabatan'] ?? '';
    $query_jabatan_gaji = mysqli_query($conn, "SELECT * FROM jabatan WHERE id_jabatan = '$id_jabatan_user'");
    if ($query_jabatan_gaji && mysqli_num_rows($query_jabatan_gaji) > 0) {
        $data_jg = mysqli_fetch_assoc($query_jabatan_gaji);
        $gaji_pokok = $data_jg['gaji_pokok'] ?? $data_jg['gapok'] ?? 0;
        $tunjangan = $data_jg['tunjangan'] ?? 0;
    }
}

// 2. Hitung Bonus Penjualan dari total transaksi sales karyawan di bulan berjalan
$bln_ini = date('m');
$thn_ini = date('Y');
$query_transaksi = mysqli_query($conn, "SELECT SUM(total) AS total_sales FROM transaksi WHERE id_karyawan = '$id_karyawan' AND MONTH(tanggal) = '$bln_ini' AND YEAR(tanggal) = '$thn_ini'");
if (!$query_transaksi) {
    $query_transaksi = mysqli_query($conn, "SELECT SUM(total_harga) AS total_sales FROM transaksi WHERE id_karyawan = '$id_karyawan' AND MONTH(tgl_transaksi) = '$bln_ini' AND YEAR(tgl_transaksi) = '$thn_ini'");
}

if ($query_transaksi && $row_t = mysqli_fetch_assoc($query_transaksi)) {
    $total_sales = $row_t['total_sales'] ?? 0;
    $bonus_penjualan = $total_sales * 0.02; 
}

$total_gaji = $gaji_pokok + $tunjangan + $bonus_penjualan;
// =========================================================================

// Query Grafik Komposisi Jabatan (KOTAK 2)
$query_grafik = mysqli_query($conn, "SELECT jb.nmaJabatan, COUNT(ky.id_karyawan) AS jumlah 
                                     FROM userkaryawan ky 
                                     JOIN jabatan jb ON ky.id_jabatan = jb.id_jabatan 
                                     GROUP BY ky.id_jabatan");
$labels_grafik = [];
$data_grafik = [];

if ($query_grafik) {
    while ($row = mysqli_fetch_assoc($query_grafik)) {
        $labels_grafik[] = $row['nmaJabatan'];
        $data_grafik[] = $row['jumlah'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Karyawan</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            
            <aside class="sidebar">
                <a href="dashboardkaryawan.php" class="brand">
                    <img src="assets/logoputih.svg" class="logo" alt="logo">
                </a>
                
                <nav class="nav-menu">
                    <a href="dashboardkaryawan.php" class="nav-item active">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <a href="presensikaryawan.php" class="nav-item">
                        <i class="fa-solid fa-square-check"></i> Presensi
                    </a>
                    <a href="gajikaryawan.php" class="nav-item">
                        <i class="fa-solid fa-calendar-days"></i> Gaji
                    </a>
                    <a href="ordersales.php" class="nav-item">
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
                    <div class="dashboard-grid">
                        
                        <div class="card-info">
                            <div class="card-header-title">
                                <h4>Data kamu</h4>
                            </div>

                            <div class="form-group-info">
                                <label>Nama</label>
                                <span class="text-company-name"><?= htmlspecialchars($nmaKaryawan); ?></span>
                            </div>

                            <div class="form-group-info">
                                <label>Alamat</label>
                                <span class="text-company-value"><?= htmlspecialchars($alamat); ?></span>
                            </div>

                            <div class="form-group-info">
                                <label>Status</label>
                                <span class="text-company-value"><?= htmlspecialchars($status_kerja); ?></span>
                            </div>

                            <div class="form-group-info">
                                <label>Tanggal Gabung</label>
                                <span class="text-company-value">
                                    <i class="fa-solid fa-calendar-check icon-date"></i> 
                                    <?= htmlspecialchars($tglGabung); ?>
                                </span>
                            </div>
                        </div>  

                        <div class="card-stats-container">
                            <div class="card-header-title">
                                <h4>Ringkasan Gaji Bulan Ini</h4>
                            </div>
                            
                            <div class="salary-list">
                                <?php
                                $nama_bulan_ini = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"][date('n')];
                                ?>
                                <div class="salary-item">
                                    <label>Periode Bulan</label>
                                    <span class="val-periode"><?= $nama_bulan_ini . ' ' . date('Y'); ?></span>
                                </div>

                                <div class="salary-item">
                                    <label>Gaji Pokok</label>
                                    <span class="val-gapok">Rp <?= number_format($gaji_pokok, 0, ',', '.'); ?></span>
                                </div>

                                <div class="salary-item">
                                    <label>Tunjangan</label>
                                    <span class="val-tunjangan">Rp <?= number_format($tunjangan, 0, ',', '.'); ?></span>
                                </div>

                                <div class="salary-item item-dashed">
                                    <label>Bonus Komisi Sales</label>
                                    <span class="val-bonus">Rp <?= number_format($bonus_penjualan, 0, ',', '.'); ?></span>
                                </div>

                                <div class="salary-item item-total">
                                    <label>Total Pendapatan</label>
                                    <span class="val-total">Rp <?= number_format($total_gaji, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-stats-container">
                            <div class="card-header-title">
                                <h4>Komposisi Jabatan</h4>
                            </div>
                            
                            <div class="chart-wrapper">
                                <?php if (empty($data_grafik)) : ?>
                                    <p class="text-empty-presence">Belum ada data karyawan.</p>
                                <?php else : ?>
                                    <canvas id="grafikJabatan" 
                                            data-labels='<?= json_encode($labels_grafik); ?>' 
                                            data-values='<?= json_encode($data_grafik); ?>'>
                                    </canvas>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
<?php
ob_end_flush();
?>