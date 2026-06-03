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

if (!isset($_SESSION['emaillogin'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['id_karyawan']) || empty($_SESSION['id_karyawan'])) {
    $email_user = mysqli_real_escape_string($conn, $_SESSION['emaillogin']);
    $cek_user = mysqli_query($conn, "SELECT id_karyawan FROM user WHERE email = '$email_user'");
    $data_user = mysqli_fetch_assoc($cek_user);
    if (!empty($data_user['id_karyawan'])) {
        $_SESSION['id_karyawan'] = $data_user['id_karyawan'];
    }
}

$id_karyawan = mysqli_real_escape_string($conn, $_SESSION['id_karyawan'] ?? '');

// Ambil nama perusahaan
$query = mysqli_query($conn, "SELECT nmaKaryawan FROM perusahaan WHERE id_karyawan = '$id_karyawan'");
$userkaryawan = mysqli_fetch_assoc($query);
$check_nama = $userkaryawan['nmaKaryawan'] ?? '';

if (empty($check_nama)) {
    header("Location: formperusahaan.php");
    exit; 
}

// Ambil data profil & lokasi perusahaan
$query = mysqli_query($conn, "SELECT p.*, l.latitude, l.longitude, l.radius 
                              FROM perusahaan p 
                              LEFT JOIN lokasi l ON p.id_lokasi = l.id_lokasi 
                              WHERE p.id_karyawan = '$id_karyawan'");
$data = mysqli_fetch_assoc($query);
$userkaryawan = $data;

$nmaKaryawan     = $data['nmaKaryawan'] ?? ''; 
$alamat = $data['alamat'] ?? '';
$noWa              = $data['noWa'] ?? '';

$lokasi = [
    'latitude'  => $data['latitude'] ?? '0',
    'longitude' => $data['longitude'] ?? '0',
    'radius'    => $data['radius'] ?? '0'
];

//Presensi
$query_presensi = mysqli_query($conn, "SELECT p.id_karyawan, p.jamMasuk, p.sttsPresensi 
                                       FROM presensi p
                                       JOIN userkaryawan ky ON p.id_karyawan = ky.id_karyawan
                                       WHERE ky.id_karyawan = '$id_karyawan'
                                       ORDER BY p.id_presensi DESC LIMIT 3");

//Data Karyawan
$query_grafik = mysqli_query($conn, "SELECT jb.nmaJabatan, COUNT(ky.id_karyawan) AS jumlah 
                                     FROM userkaryawan ky 
                                     JOIN jabatan jb ON ky.id_jabatan = jb.id_jabatan 
                                     WHERE ky.id_karyawan = '$id_karyawan'
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
                    <a href="presensi.php" class="nav-item">
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
                                    <span class="text-company-value"><?= htmlspecialchars($noWa); ?></span>
                                </div>

                                <div class="form-group-info">
                                    <label>Koordinat Titik Presensi (Lat, Long)</label>
                                    <span class="text-coordinate">
                                        <?= htmlspecialchars($lokasi['latitude'] ?? '0'); ?>, <?= htmlspecialchars($lokasi['longitude'] ?? '0'); ?>
                                    </span>
                                </div>

                                <div class="form-group-info">
                                    <label class="label-radius-title">Radius Jangkauan Absensi</label>
                                    <span class="text-radius">
                                        <i class="fa-solid fa-location-crosshairs icon-radius"></i> <?= htmlspecialchars($lokasi['radius'] ?? '0'); ?> Meter
                                    </span>
                                </div>

                                <div class="container-btn-ubah">
                                    <a href="formperusahaan.php?action=edit" class="btn-ubah">
                                        <i class="fa-solid fa-pen-to-square"></i> Ubah Data
                                    </a>
                                </div>
                            </div>  

                            <div class="card-stats-container">
                                <div class="card-header-title">
                                    <h4>Aktivitas Presensi Terbaru</h4>
                                </div>
                                
                                <div class="presence-list">
                                    <?php if (mysqli_num_rows($query_presensi) == 0) : ?>
                                        <p class="text-empty-presence">Belum ada aktivitas presensi saat ini.</p>
                                    <?php else : ?>
                                        <?php while ($row_p = mysqli_fetch_assoc($query_presensi)) : ?>
                                            <div class="presence-item">
                                                <div class="presence-badge">
                                                    <i class="fa-solid fa-user-check"></i>
                                                </div>
                                                <div class="presence-details">
                                                    <span class="p-name">Karyawan ID: <?= htmlspecialchars($row_p['id_karyawan'] ?? '-'); ?></span>
                                                    <span class="p-time">
                                                        <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($row_p['jamMasuk'] ?? '--:--'); ?> WIB
                                                        • <b class="status-badge"><?= htmlspecialchars($row_p['sttsPresensi'] ?? '-'); ?></b>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
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
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </body>
</html>
<?php
ob_end_flush();
?>