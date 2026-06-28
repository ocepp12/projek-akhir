<?php
ob_start(); 
session_start();
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

// Validasi Session Login Karyawan
if (!isset($_SESSION['loginKaryawan'])) {
    header("Location: loginkaryawan.php");
    exit;
}

$id_karyawan = mysqli_real_escape_string($conn, $_SESSION['id_karyawan'] ?? '');

// ==============================================================================
// 1. Ambil data profil (Data Kamu) dengan JOIN ke tabel Jabatan
// ==============================================================================
$query_user = mysqli_query($conn, "
    SELECT u.*, j.nmaJabatan 
    FROM userkaryawan u 
    LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan 
    WHERE u.id_karyawan = '$id_karyawan'
");
$data_user = mysqli_fetch_assoc($query_user);

$nmaKaryawan = $data_user['nmakaryawan'] ?? $data_user['nmaKaryawan'] ?? 'Karyawan'; 
$alamat      = $data_user['alamat'] ?? '-';
$status      = $data_user['status'] ?? '-';
$jmlAnak     = $data_user['jmlAnak'] ?? '0';
$tglGabung   = $data_user['tglGabung'] ?? '-';
$namaJabatan = $data_user['nmaJabatan'] ?? 'Belum Ada Jabatan';

// ==============================================================================
// 2. Ambil data Gaji Terakhir dari admin (rekapgaji)
// ==============================================================================
$query_gaji = mysqli_query($conn, "
    SELECT * FROM rekapgaji 
    WHERE id_karyawan = '$id_karyawan' 
    ORDER BY tahun DESC, 
             FIELD(periodebulan, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari') DESC, 
             id_rekap DESC 
    LIMIT 1
");
$data_gaji = mysqli_fetch_assoc($query_gaji);

// ==============================================================================
// 3. Ambil data Presensi Hari Ini (Untuk widget presensi)
// ==============================================================================
$tgl_hari_ini = date('Y-m-d');
$query_absen_hari_ini = mysqli_query($conn, "
    SELECT * FROM presensi 
    WHERE id_karyawan = '$id_karyawan' AND tglPresensi = '$tgl_hari_ini'
");
$data_absen = mysqli_fetch_assoc($query_absen_hari_ini);

$jam_masuk = '-';
$jam_pulang = '-';
$status_absen = 'Belum Presensi';
$color_status = '#64748b'; // Default abu-abu

if ($data_absen) {
    if (!empty($data_absen['jamMasuk']) && $data_absen['jamMasuk'] != '00:00:00' && $data_absen['jamMasuk'] != '0000-00-00 00:00:00') {
        $jam_masuk = date('H:i', strtotime($data_absen['jamMasuk']));
    }
    if (!empty($data_absen['jamKeluar']) && $data_absen['jamKeluar'] != '00:00:00' && $data_absen['jamKeluar'] != '0000-00-00 00:00:00') {
        $jam_pulang = date('H:i', strtotime($data_absen['jamKeluar']));
    }
    $status_absen = $data_absen['sttsPresensi'] ?? 'Hadir';
    
    if (strtolower($status_absen) == 'hadir' || strtolower($status_absen) == 'tepat waktu') {
        $color_status = '#16a34a'; // Hijau
    } else {
        $color_status = '#eab308'; // Kuning/Warning
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar active">
                <a href="dashboardkaryawan.php" class="brand"><img src="assets/logoputih.svg" class="logo" alt="logo"></a>
                <nav class="nav-menu">
                    <a href="dashboardkaryawan.php" class="nav-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
                    <a href="presensikaryawan.php" class="nav-item"><i class="fa-solid fa-square-check"></i> Presensi</a>
                    <a href="gajikaryawan.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Gaji</a>
                </nav>
                <div class="sidebar-footer">
                    <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Apakah anda yakin ingin logout?');">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </aside>

        <main class="main-content sidebar-active">
            <header class="topbar">
                <div class="toggle-btn"><i class="fa-solid fa-bars"></i></div>
                <div class="topbar-right">
                    <span class="user-name">
                        <?php 
                        $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                        $bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                        echo $hari[date('w')] . ", " . date('j') . " " . $bulan[date('n')] . " " . date('Y'); 
                        ?>
                    </span>
                </div>
            </header>

            <div class="content-body">

                <div class="table-container" style="margin-bottom: 25px;">
                    <div class="card-header-title" style="margin-bottom: 15px;">
                        <h3><i class="fa-solid fa-id-card"></i> Data Kamu</h3>
                    </div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <div>
                            <span style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Nama Lengkap</span>
                            <strong style="color: #1e293b; font-size: 16px;"><?= htmlspecialchars($nmaKaryawan) ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Jabatan Saat Ini</span>
                            <strong style="color: #0ea5e9; font-size: 16px;"><i class="fa-solid fa-briefcase"></i> <?= htmlspecialchars($namaJabatan) ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Tanggal Bergabung</span>
                            <strong style="color: #1e293b;"><?= ($tglGabung != '-' && $tglGabung != '0000-00-00') ? date('d M Y', strtotime($tglGabung)) : '-' ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Status</span>
                            <strong style="color: #1e293b;"><?= htmlspecialchars($status) ?> (<?= htmlspecialchars($jmlAnak) ?> Anak)</strong>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <span style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Alamat Rumah</span>
                            <span style="color: #1e293b;"><?= nl2br(htmlspecialchars($alamat)) ?></span>
                        </div>
                    </div>
                </div>

                <div class="table-container" style="margin-bottom: 25px;">
                    <div class="card-header-title" style="margin-bottom: 15px;">
                        <h3><i class="fa-solid fa-wallet"></i> Ringkasan Gaji Terakhir</h3>
                    </div>

                    <?php if ($data_gaji): 
                        // Perhitungan Total
                        $tunjangan_gaji = ($data_gaji['t_jabatan'] ?? 0) + ($data_gaji['t_lain'] ?? 0);
                        $potongan_gaji  = ($data_gaji['p_kehadiran'] ?? 0) + ($data_gaji['p_bpjs'] ?? 0) + ($data_gaji['p_pajak'] ?? 0) + ($data_gaji['p_kasbon'] ?? 0);
                        $gaji_pokok_gaji = $data_gaji['gaji_pokok'] ?? 0;
                        $insentif_gaji   = $data_gaji['insentif'] ?? 0;
                        $gaji_bersih_gaji = $data_gaji['gaji_bersih'] ?? 0;
                        $status_bayar_gaji = $data_gaji['status_bayar'] ?? 'Belum Dibayar';
                    ?>
                        <div class="detail-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            
                            <div class="detail-box" style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc;">
                                <h4 style="margin-bottom: 12px; color: #0284c7; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; font-size: 14px; text-transform: uppercase;">
                                    <i class="fa-solid fa-arrow-trend-up"></i> Komponen Penerimaan (<?= $data_gaji['periodebulan'] . " " . $data_gaji['tahun'] ?>)
                                </h4>
                                <div class="detail-item" style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #475569;">
                                    <span>Gaji Pokok (Base)</span> 
                                    <span>Rp <?= number_format($gaji_pokok_gaji, 0, ',', '.') ?></span>
                                </div>
                                <div class="detail-item" style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #475569;">
                                    <span>Total Tunjangan</span> 
                                    <span>Rp <?= number_format($tunjangan_gaji, 0, ',', '.') ?></span>
                                </div>
                                <div class="detail-item" style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #475569;">
                                    <span>Insentif Penjualan</span> 
                                    <span>Rp <?= number_format($insentif_gaji, 0, ',', '.') ?></span>
                                </div>
                                <div class="detail-item total" style="display: flex; justify-content: space-between; font-weight: bold; margin-top: 12px; border-top: 1px dashed #cbd5e1; padding-top: 10px; color: #0f172a; font-size: 14px;">
                                    <span>Total Penerimaan Kotor</span> 
                                    <span>Rp <?= number_format(($gaji_pokok_gaji + $tunjangan_gaji + $insentif_gaji), 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <div class="detail-box box-danger" style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc;">
                                <h4 style="margin-bottom: 12px; color: #dc2626; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; font-size: 14px; text-transform: uppercase;">
                                    <i class="fa-solid fa-arrow-trend-down"></i> Komponen Potongan & Hasil
                                </h4>
                                <div class="detail-item" style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; color: #475569;">
                                    <span>Total Potongan (Absen, Pajak, Kasbon)</span> 
                                    <span style="color: #dc2626;">- Rp <?= number_format($potongan_gaji, 0, ',', '.') ?></span>
                                </div>
                                
                                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 14px; color: #1e293b; font-weight: bold;">Gaji Bersih (THP)</span>
                                    <span style="font-size: 18px; color: #16a34a; font-weight: bold;">Rp <?= number_format($gaji_bersih_gaji, 0, ',', '.') ?></span>
                                </div>

                                <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: #64748b;">Status Transfer:</span>
                                    <?php if($status_bayar_gaji == 'Sudah Dibayar'): ?>
                                        <span style="background: #10b981; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;"><i class="fa-solid fa-circle-check"></i> Sudah Dibayar</span>
                                    <?php else: ?>
                                        <span style="background: #ef4444; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;"><i class="fa-solid fa-circle-xmark"></i> Belum Dibayar</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: right;">
                            <a href="gajikaryawan.php" class="btn-submit" style="display: inline-block; text-decoration: none; padding: 8px 15px; font-size: 13px; margin:0;">
                                <i class="fa-solid fa-file-lines"></i> Lihat Riwayat Lengkap Slip Gaji
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="padding: 30px 20px; text-align: center; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
                            <i class="fa-solid fa-folder-open" style="font-size: 40px; color: #94a3b8; margin-bottom: 15px; display: block;"></i>
                            <span style="color: #64748b; font-size: 14px;">Belum ada riwayat gaji yang dihitung oleh Admin / HRD untuk Anda.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <div class="card-header-title" style="margin-bottom: 15px;">
                        <h3><i class="fa-solid fa-calendar-check"></i> Presensi Hari Ini</h3>
                    </div>
                    <div class="presence-today-list" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: center;">
                        
                        <div style="display: flex; gap: 100px; flex-wrap: wrap;">
                            <div class="presence-today-item">
                                <label style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Absen Masuk</label>
                            </div>
                            <div class="presence-today-item">
                                <label style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Absen Pulang</label>
                            </div>
                            <div class="presence-today-item item-dashed" style="padding-left: 30px; border-left: 1px dashed #cbd5e1;">
                                <label style="font-size: 13px; color: #64748b; display: block; margin-bottom: 5px;">Status Kehadiran</label>
                            </div>
                        </div>
                        <div style="display: flex; gap: 100px; flex-wrap: wrap;"> 
                            <div class="presence-today-item">
                                <span class="val-masuk" style="font-size: 20px; font-weight: bold; color: #1e293b;"><?= $jam_masuk; ?></span>
                            </div>   
                            <div class="presence-today-item">    
                                <span class="val-pulang" style="font-size: 20px; font-weight: bold; color: #1e293b;"><?= $jam_pulang; ?></span>
                            </div>
                            <div class="presence-today-item">    
                                <span class="status-badge-today" style="font-weight: bold; font-size: 16px; color: <?= $color_status; ?>;">
                                    <?= htmlspecialchars($status_absen); ?>
                                </span>
                            </div>
                        </div>

                        <div class="presence-action">
                            <a href="presensikaryawan.php" class="btn-submit" style="display: inline-block; text-decoration: none; padding: 10px 20px; margin:0;">
                                <i class="fa-solid fa-fingerprint"></i> Lakukan Presensi
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>