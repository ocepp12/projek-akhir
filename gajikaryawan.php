<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Ambil riwayat gaji dari tabel rekapgaji khusus karyawan yang login
$query_gaji = "SELECT * FROM rekapgaji WHERE id_karyawan = '$id_karyawan' 
               ORDER BY tahun DESC, FIELD(periodebulan, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari') DESC";
$result_gaji = mysqli_query($conn, $query_gaji);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Karyawan - Slip Gaji</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
        <script src="assets/script.js" defer></script> 
    </head>

    <body>
        <div class="dashboard-container">
             <aside class="sidebar active">
                <a href="dashboardkaryawan.php" class="brand"><img src="assets/logoputih.svg" class="logo" alt="logo"></a>
                <nav class="nav-menu">
                    <a href="dashboardkaryawan.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                    <a href="presensikaryawan.php" class="nav-item"><i class="fa-solid fa-square-check"></i> Presensi</a>
                    <a href="gajikaryawan.php" class="nav-item active"><i class="fa-solid fa-calendar-days"></i> Gaji</a>
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
                    <div class="table-container">
                        <div class="card-header-title">
                            <h3><i class="fa-solid fa-file-invoice-dollar"></i> Riwayat Slip Gaji Anda</h3>
                        </div>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Periode</th>
                                    <th>Gaji Pokok</th>
                                    <th>Total Tunjangan</th>
                                    <th>Total Potongan</th>
                                    <th>Gaji Bersih (THP)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($result_gaji) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_gaji)) {
                                        $tunjangan = ($row['t_jabatan'] ?? 0) + ($row['t_lain'] ?? 0);
                                        $potongan  = ($row['p_kehadiran'] ?? 0) + ($row['p_bpjs'] ?? 0) + ($row['p_pajak'] ?? 0) + ($row['p_kasbon'] ?? 0);
                                        $id_toggle = "detail-" . $row['id_rekap'];
                                        ?>
                                        <tr>
                                            <td class="text-bold">
                                                <a href="#" onclick="toggleDetail('<?= $id_toggle ?>'); return false;" style="color: #007bff; text-decoration: none;">
                                                    <i class="fa-solid fa-circle-info"></i> <?= $row['periodebulan'] . " " . $row['tahun'] ?>
                                                </a>
                                            </td>
                                            <td>Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($tunjangan, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($potongan, 0, ',', '.') ?></td>
                                            <td class="text-bold text-company-name">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                                            <td>
                                                <span class="badge-status <?= $row['status_bayar'] == 'Sudah Dibayar' ? 'badge-dibayar' : 'badge-belum' ?>">
                                                    <?= $row['status_bayar'] ?>
                                                </span>
                                            </td>
                                        </tr>

                                        <tr id="<?= $id_toggle ?>" class="detail-row">
                                            <td colspan="6">
                                                <div class="detail-container">
                                                    <div class="detail-header">
                                                        <strong>Rincian Slip Gaji:</strong> <?= $row['periodebulan'] . " " . $row['tahun'] ?>
                                                    </div>
                                                    <div class="detail-grid">
                                                        <div class="detail-box">
                                                            <h4><i class="fa-solid fa-arrow-trend-up"></i> Komponen Penerimaan</h4>
                                                            <div class="detail-item"><span>Gaji Pokok</span> <span>Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item"><span>Tunjangan Jabatan</span> <span>Rp <?= number_format($row['t_jabatan'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item"><span>Tunjangan Lain-lain</span> <span>Rp <?= number_format($row['t_lain'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item"><span>Insentif</span> <span>Rp <?= number_format($row['insentif'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item total"><span>Total Penerimaan</span> <span>Rp <?= number_format(($row['gaji_pokok'] + $tunjangan + $row['insentif']), 0, ',', '.') ?></span></div>
                                                        </div>

                                                        <div class="detail-box box-danger">
                                                            <h4><i class="fa-solid fa-arrow-trend-down"></i> Komponen Potongan</h4>
                                                            <div class="detail-item"><span>Denda Absensi</span> <span>- Rp <?= number_format($row['p_kehadiran'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item"><span>BPJS</span> <span>- Rp <?= number_format($row['p_bpjs'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item"><span>Pajak</span> <span>- Rp <?= number_format($row['p_pajak'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item"><span>Kasbon</span> <span>- Rp <?= number_format($row['p_kasbon'], 0, ',', '.') ?></span></div>
                                                            <div class="detail-item total" style="color: #dc2626;"><span>Total Potongan</span> <span>- Rp <?= number_format($potongan, 0, ',', '.') ?></span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center text-muted' style='padding: 20px;'>Belum ada data gaji yang tersedia.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>

        <script>
            function toggleDetail(rowId) {
                var detailRow = document.getElementById(rowId);
                if(detailRow) {
                    detailRow.classList.toggle('show');
                }
            }
        </script>
    </body>
</html>
<?php ob_end_flush(); ?>