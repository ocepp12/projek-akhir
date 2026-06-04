<?php
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set zona waktu
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

// Ambil data profil lengkap untuk memfilter gaji
$query_user = mysqli_query($conn, "SELECT * FROM userkaryawan WHERE id_karyawan = '$id_karyawan'");
$data_user = mysqli_fetch_assoc($query_user);

// Ambil ID Tunjangan milik karyawan yang sedang login
$id_tunjangan_user = $data_user['id_tunjangan'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gaji Karyawan</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            
            <aside class="sidebar">
                <a href="dashboardkaryawan.php" class="brand">
                    <img src="assets/logoputih.svg" class="logo" alt="logo">
                </a>
                
                <nav class="nav-menu">
                    <a href="dashboardkaryawan.php" class="nav-item">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <a href="presensikaryawan.php" class="nav-item">
                        <i class="fa-solid fa-square-check"></i> Presensi
                    </a>
                    <a href="gajikaryawan.php" class="nav-item active">
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
                    <div class="content-header">
                        <h3>Rincian Gaji & Tunjangan Anda</h3>
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
                                $query = "SELECT g.*, t.* FROM gaji g 
                                          LEFT JOIN tunjangan t ON g.id_tunjangan = t.id_tunjangan
                                          WHERE g.id_tunjangan = '$id_tunjangan_user'";
                                
                                $result = mysqli_query($conn, $query);
                                $no = 1;

                                if ($result && mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        
                                        $gapok = (int)$row['gapok'];
                                        
                                        // Rincian Tunjangan
                                        $makan = (int)$row['makan'];
                                        $transport = (int)$row['transport'];
                                        $uangLembur = (int)$row['uangLembur'];
                                        $insentif = (int)$row['insentifPenjualan'];
                                        $tunJabatan = (int)$row['tunJabatan'];
                                        $kompensasi = (int)$row['kompensasi'];
                                        $thr = (int)$row['THR'];
                                        $bpjs = (int)$row['BPJS'];
                                        $bat = (int)$row['BAT'];

                                        $total_tunjangan = $makan + $transport + $uangLembur + $insentif + $tunJabatan + $kompensasi + $thr + $bpjs + $bat;
                                        
                                        // Rincian Potongan
                                        $potPajak = (int)$row['potPajak'];
                                        $potKehadiran = (int)$row['potKehadiran'];
                                        $pinjaman = (int)$row['pinjaman'];

                                        $total_potongan = $potPajak + $potKehadiran + $pinjaman;
                                        
                                        // Gaji Bersih
                                        $gaji_bersih = ($gapok + $total_tunjangan) - $total_potongan;
                                        
                                        // ID unik untuk baris detail
                                        $detail_id = "detail-" . $no;

                                        echo "<tr>";
                                        echo "<td>" . $no . "</td>";
                                        echo "<td class='text-bold'>" . htmlspecialchars($row['id_gaji']) . "</td>";
                                        echo "<td>Rp " . number_format($gapok, 0, ',', '.') . "</td>";
                                        echo "<td><span class='val-tunjangan'>Rp " . number_format($total_tunjangan, 0, ',', '.') . "</span></td>";
                                        echo "<td><span class='val-potongan'>Rp " . number_format($total_potongan, 0, ',', '.') . "</span></td>";
                                        echo "<td class='text-company-name'>Rp " . number_format($gaji_bersih, 0, ',', '.') . "</td>";
                                        echo "<td>
                                                <a href='#' class='btn-action-edit' onclick=\"toggleDetail('$detail_id'); return false;\"><i class='fa-solid fa-circle-info'></i> Detail</a>
                                              </td>";
                                        echo "</tr>";

                                        // --- BARIS DETAIL ---
                                        echo "<tr id='$detail_id' class='detail-row'>";
                                        echo "<td colspan='7' class='cell-padding-large'>";
                                        echo "  <div class='detail-container'>";
                                        
                                        // Kolom Rincian Tunjangan
                                        echo "      <div class='detail-section'>";
                                        echo "          <div class='detail-title title-tunjangan'>Rincian Tunjangan</div>";
                                        
                                        if($makan > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Makan</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($makan, 0, ',', '.') . "</span></div>";
                                        if($transport > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Transport</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($transport, 0, ',', '.') . "</span></div>";
                                        if($uangLembur > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Uang Lembur</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($uangLembur, 0, ',', '.') . "</span></div>";
                                        if($insentif > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Insentif Penjualan</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($insentif, 0, ',', '.') . "</span></div>";
                                        if($tunJabatan > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Tunjangan Jabatan</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($tunJabatan, 0, ',', '.') . "</span></div>";
                                        if($kompensasi > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Kompensasi</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($kompensasi, 0, ',', '.') . "</span></div>";
                                        if($thr > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>THR</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($thr, 0, ',', '.') . "</span></div>";
                                        if($bpjs > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>BPJS</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($bpjs, 0, ',', '.') . "</span></div>";
                                        if($bat > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>BAT</span><span class='salary-detail-value val-tunjangan'>Rp " . number_format($bat, 0, ',', '.') . "</span></div>";
                                        
                                        if($total_tunjangan == 0) echo "<div class='salary-detail-empty'>Tidak ada tunjangan bulan ini.</div>";
                                        
                                        echo "          <div class='salary-detail-total'>";
                                        echo "              <span class='salary-total-label'>Total Tunjangan</span>";
                                        echo "              <span class='salary-total-value val-tunjangan'>Rp " . number_format($total_tunjangan, 0, ',', '.') . "</span>";
                                        echo "          </div>";
                                        echo "      </div>";

                                        // Kolom Rincian Potongan
                                        echo "      <div class='detail-section'>";
                                        echo "          <div class='detail-title title-potongan'>Rincian Potongan</div>";
                                        
                                        if($potPajak > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Potongan Pajak</span><span class='salary-detail-value val-potongan'>Rp " . number_format($potPajak, 0, ',', '.') . "</span></div>";
                                        if($potKehadiran > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Potongan Kehadiran</span><span class='salary-detail-value val-potongan'>Rp " . number_format($potKehadiran, 0, ',', '.') . "</span></div>";
                                        if($pinjaman > 0) echo "<div class='salary-detail-row'><span class='salary-detail-label'>Pinjaman</span><span class='salary-detail-value val-potongan'>Rp " . number_format($pinjaman, 0, ',', '.') . "</span></div>";
                                        
                                        if($total_potongan == 0) echo "<div class='salary-detail-empty'>Tidak ada potongan bulan ini.</div>";

                                        echo "          <div class='salary-detail-total'>";
                                        echo "              <span class='salary-total-label'>Total Potongan</span>";
                                        echo "              <span class='salary-total-value val-potongan'>Rp " . number_format($total_potongan, 0, ',', '.') . "</span>";
                                        echo "          </div>";
                                        echo "      </div>";

                                        echo "  </div>";
                                        echo "</td>";
                                        echo "</tr>";

                                        $no++;
                                    }
                                } else {
                                    echo "<tr>
                                            <td colspan='7' class='text-center text-muted cell-empty-state'>
                                                <i class='fa-solid fa-folder-open icon-empty-folder'></i>
                                                Belum ada data rincian gaji untuk Anda.
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

        <script>
            function toggleDetail(rowId) {
                var detailRow = document.getElementById(rowId);
                detailRow.classList.toggle('show');
            }
        </script>
    </body>
</html>
<?php
ob_end_flush();
?>