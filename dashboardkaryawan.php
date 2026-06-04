<?php
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set zona waktu di paling atas agar penanggalan SQL & jam sinkron
date_default_timezone_set('Asia/Jakarta'); 

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

// Ambil data profil lengkap langsung dari tabel userkaryawan
$query_user = mysqli_query($conn, "SELECT * FROM userkaryawan WHERE id_karyawan = '$id_karyawan'");
$data_user = mysqli_fetch_assoc($query_user);

// Mapping data profil karyawan
$nmaKaryawan = $data_user['nmakaryawan'] ?? $data_user['nmaKaryawan'] ?? 'Karyawan'; 
$alamat      = $data_user['alamat'] ?? 'Belum diatur';
$status_pernikahan = $data_user['status'] ?? 'Aktif'; 

// Mengambil dan memformat Tanggal Gabung ke format Indonesia
$tglGabung = 'Belum diketahui';
if (!empty($data_user['tglGabung'])) {
    $time = strtotime($data_user['tglGabung']);
    $bln = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    $tglGabung = date('j', $time) . ' ' . $bln[date('n', $time)] . ' ' . date('Y', $time);
}

// =========================================================================
// SCRIPT AMBIL DATA RINGKASAN GAJI (KOTAK 1)
// =========================================================================
$gaji_pokok = 0;
$tunjangan = 0;

$id_gaji_user = $data_user['id_gaji'] ?? '';
if (!empty($id_gaji_user)) {
    $query_gaji = mysqli_query($conn, "SELECT gapok FROM gaji WHERE id_gaji = '$id_gaji_user'");
    if ($query_gaji && mysqli_num_rows($query_gaji) > 0) {
        $data_gaji = mysqli_fetch_assoc($query_gaji);
        $gaji_pokok = $data_gaji['gapok'] ?? 0;
    }
}

$id_tunjangan_user = $data_user['id_tunjangan'] ?? '';
if (!empty($id_tunjangan_user)) {
    $query_tunjangan = mysqli_query($conn, "SELECT * FROM tunjangan WHERE id_tunjangan = '$id_tunjangan_user'");
    if ($query_tunjangan && mysqli_num_rows($query_tunjangan) > 0) {
        $data_tunjangan = mysqli_fetch_assoc($query_tunjangan);
        foreach ($data_tunjangan as $key => $value) {
            if ($key !== 'id_tunjangan' && is_numeric($value) && $value > 0) {
                $tunjangan += $value;
            }
        }
        if ($tunjangan == 0) {
            $tunjangan = $data_tunjangan['tunjangan'] ?? $data_tunjangan['nominal'] ?? 0;
        }
    }
}
$total_gaji = $gaji_pokok + $tunjangan;


// =========================================================================
// SCRIPT AMBIL DATA PRESENSI HARI INI (KOTAK 3) - REPLACEMENT NON-JS
// =========================================================================
$tgl_hari_ini = date('Y-m-d');
$jam_masuk    = '-- : --';
$jam_pulang   = '-- : --';
$status_absen = 'Belum Absen';
$color_status = '#a0aec0'; // Warna abu-abu default bawaan sistem

// Query memeriksa log presensi karyawan bersangkutan di tanggal hari ini
// (Silakan sesuaikan nama tabel/kolom jika berbeda dengan rancangan database lo)
$query_presensi = mysqli_query($conn, "SELECT * FROM presensi WHERE id_karyawan = '$id_karyawan' AND tanggal = '$tgl_hari_ini'");

if ($query_presensi && mysqli_num_rows($query_presensi) > 0) {
    $data_presensi = mysqli_fetch_assoc($query_presensi);
    $jam_masuk    = !empty($data_presensi['jam_masuk']) ? date('H:i', strtotime($data_presensi['jam_masuk'])) : '-- : --';
    $jam_pulang   = !empty($data_presensi['jam_pulang']) ? date('H:i', strtotime($data_presensi['jam_pulang'])) : '-- : --';
    $status_absen = $data_presensi['status'] ?? 'Hadir';
    
    // Logika pewarnaan status teks agar dinamis menyesuaikan keadaan absen
    if (strcasecmp($status_absen, 'Tepat Waktu') == 0 || strcasecmp($status_absen, 'Hadir') == 0) {
        $color_status = '#3e9c35'; // Hijau utama
    } elseif (strcasecmp($status_absen, 'Terlambat') == 0) {
        $color_status = '#dd6b20'; // Orange kecokelatan
    } else {
        $color_status = '#3182ce'; // Biru soft untuk keterangan Sakit/Izin
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
                                <span class="text-company-value">
                                    <i class="fa-solid fa-location-dot icon-location"></i> 
                                    <?= htmlspecialchars($alamat); ?>
                                </span>
                            </div>

                            <div class="form-group-info">
                                <label>Status Pernikahan</label>
                                <span class="text-company-value">
                                    <i class="fa-solid fa-ring icon-status"></i> 
                                    <?= htmlspecialchars($status_pernikahan); ?>
                                </span>
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

                                <div class="salary-item item-dashed">
                                    <label>Tunjangan Jabatan</label>
                                    <span class="val-tunjangan">Rp <?= number_format($tunjangan, 0, ',', '.'); ?></span>
                                </div>

                                <div class="salary-item item-total">
                                    <label>Total Pendapatan</label>
                                    <span class="val-total">Rp <?= number_format($total_gaji, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-stats-container">
                            <div class="card-header-title">
                                <h4>Presensi Hari Ini</h4>
                            </div>
                            
                            <div class="presence-today-list">
                                <div class="presence-today-item">
                                    <label>Absen Masuk</label>
                                    <span class="val-masuk"><?= $jam_masuk; ?></span>
                                </div>

                                <div class="presence-today-item">
                                    <label>Absen Pulang</label>
                                    <span class="val-pulang"><?= $jam_pulang; ?></span>
                                </div>

                                <div class="presence-today-item item-dashed">
                                    <label>Keterangan</label>
                                    <span class="status-badge-today" style="color: <?= $color_status; ?>;">
                                        <?= htmlspecialchars($status_absen); ?>
                                    </span>
                                </div>

                                <div class="presence-action">
                                    <a href="presensikaryawan.php" class="btn-presensi-shortcut">
                                        <i class="fa-solid fa-fingerprint"></i> Lakukan Presensi
                                    </a>
                                </div>
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