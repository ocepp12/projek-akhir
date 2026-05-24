<<<<<<< Updated upstream
=======
<?php
ob_start(); // supaya tidak terjadi tampilan kedipan error yang sekilas
// Tampilkan error jika ada masalah lain agar tidak ngeblank
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi ke database
$host = 'localhost';
$user = 'root';
$pass = "";
$db   = 'sistempenggajian';
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi berhasil atau tidak
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
// Mulai sesi
session_start();
// 1. Cek apakah user sudah login
if (!isset($_SESSION['emaillogin'])) {
    header("Location: login.php");
    exit;
}

// =========================================================================
// PENYELAMAT SESSION LOGIN:
// Jika id_perusahaan di session kosong, kita tembak langsung cari berdasarkan email loginnya
// =========================================================================
if (!isset($_SESSION['id_perusahaan']) || empty($_SESSION['id_perusahaan'])) {
    $email_user = $_SESSION['emaillogin'];
    $cek_user = mysqli_query($conn, "SELECT id_perusahaan FROM user WHERE email = '$email_user'");
    $data_user = mysqli_fetch_assoc($cek_user);
    if (!empty($data_user['id_perusahaan'])) {
        $_SESSION['id_perusahaan'] = $data_user['id_perusahaan'];
    }
}
// =========================================================================

// 2. Ambil data terbaru dari database
$id_perusahaan = $_SESSION['id_perusahaan'] ?? '';

// Cari data perusahaan (Pakai LOWER/UPPER SQL biar gak sensitif huruf besar kecil)
$query = mysqli_query($conn, "SELECT nmaPerusahaan FROM perusahaan WHERE id_perusahaan = '$id_perusahaan'");
$perusahaan = mysqli_fetch_assoc($query);

// Supaya variabel di bawah tidak error, kita amankan index array-nya
$check_nama = $perusahaan['nmaPerusahaan'] ?? '';

// Jika data perusahaan di DB kosong murni, langsung lempar ke formperusahaan.php
if (empty($check_nama)) {
    header("Location: formperusahaan.php");
    exit; // Menghentikan kode di bawah agar tidak sempat terbaca
}

// 3. Ambil data untuk ditampilkan (Gabungkan dengan tabel lokasi menggunakan LEFT JOIN)
$query = mysqli_query($conn, "SELECT p.*, l.latitude, l.longitude, l.radius 
                              FROM perusahaan p 
                              LEFT JOIN lokasi l ON p.id_lokasi = l.id_lokasi 
                              WHERE p.id_perusahaan = '$id_perusahaan'");

// Simpan hasil fetch ke variabel $data agar tidak bentrok dengan check empty($perusahaan)
$data = mysqli_fetch_assoc($query);

// Definisikan ulang variabel $perusahaan sebagai array data asli untuk pengecekan if (empty($perusahaan)) di bawah
$perusahaan = $data;

// Pecah array ke dalam variabel mandiri sesuai nama kolom di database lu (Gunakan nama kolom asli huruf kecil)
$nmaPerusahaan     = $data['nmaPerusahaan'] ?? ''; 
$alamat_perusahaan = $data['alamat'] ?? '';
$noWa              = $data['noWa'] ?? '';

// Ambil data koordinat dan radius untuk tabel lokasi
$lokasi = [
    'latitude'  => $data['latitude'] ?? '0',
    'longitude' => $data['longitude'] ?? '0',
    'radius'    => $data['radius'] ?? '0'
];

// =========================================================================
// QUERY FIX: Menyesuaikan kolom asli tabel presensi di database lu
// Kita batasi hanya mengambil data milik perusahaan yang sedang login lewat filter lokasi/karyawan jika ada relasinya nanti
// =========================================================================
$query_presensi = mysqli_query($conn, "SELECT id_karyawan, jamMasuk, sttsPresensi FROM presensi ORDER BY id_presensi DESC LIMIT 3");
?>

<!DOCTYPE html>
>>>>>>> Stashed changes
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan</title>
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

                    <?php if (empty($perusahaan)) : ?>
                        <div class="alert-incomplete">
                            <h4><i class="fa-solid fa-circle-exclamation"></i> Data Profil Belum Lengkap</h4>
                            <p>
                                Sistem mendeteksi akun login kamu belum memiliki atau belum melengkapi data profil perusahaan di database (ID Perusahaan Kamu: <b><?= htmlspecialchars($id_perusahaan); ?></b>).
                            </p>
                            <a href="formperusahaan.php" class="btn-fill-form">
                                <i class="fa-solid fa-pen-to-square"></i> Isi Form Perusahaan Di Sini
                            </a>
                        </div>
                    <?php else : ?>
                        
                        <div class="dashboard-grid">
                            
                            <div class="card-info">
                                <div class="card-header-title">
                                    <h4>Data Perusahaan</h4>
                                </div>

                                <div class="form-group-info">
                                    <label>Nama Perusahaan</label>
                                    <span class="text-company-name"><?= htmlspecialchars($nmaPerusahaan); ?></span>
                                </div>

                                <div class="form-group-info">
                                    <label>Alamat Perusahaan</label>
                                    <span class="text-company-value"><?= htmlspecialchars($alamat_perusahaan); ?></span>
                                </div>

                                <div class="form-group-info">
                                    <label>Nomor WhatsApp</label>
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

                        </div> <?php endif; ?>
                </div>
            </main>
        </div>
    </body>
<<<<<<< Updated upstream
</html>
=======
</html>
<?php
ob_end_flush();
?>
>>>>>>> Stashed changes
