<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta'); 

// 1. KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistempenggajian";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// =========================================================================
// AUTO-FIX DATABASE: Otomatis melengkapi tabel rekapgaji agar bisa simpan riwayat bulanan
// =========================================================================
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM `rekapgaji` LIKE 'id_karyawan'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE `rekapgaji` 
        ADD COLUMN `id_karyawan` INT(5) AFTER `id_rekap`,
        ADD COLUMN `tahun` INT(4) AFTER `periodebulan`,
        ADD COLUMN `gaji_pokok` DECIMAL(12,0) DEFAULT 0 AFTER `id_gaji`,
        ADD COLUMN `t_jabatan` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `t_lain` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `insentif` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `p_kehadiran` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `p_bpjs` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `p_pajak` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `p_kasbon` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `gaji_bersih` DECIMAL(12,0) DEFAULT 0,
        ADD COLUMN `status_bayar` ENUM('Belum Dibayar','Sudah Dibayar') DEFAULT 'Belum Dibayar'
    ");
}

// =========================================================================
// 2. PROSES HITUNG DAN SIMPAN GAJI BULANAN KARYAWAN
// =========================================================================
if (isset($_POST['Hitung_gaji'])) {
    $id_karyawan      = mysqli_real_escape_string($koneksi, $_POST['id_karyawan']);
    $bulan            = intval($_POST['bulan']);
    $tahun            = intval($_POST['tahun']);
    
    // TANGKAP INPUT FORM MANUAL
    $Insentif         = intval($_POST['insentif'] ?? 0);
    $potBPJS          = intval($_POST['potBPJS'] ?? 0); 
    $potongan_pajak   = intval($_POST['potPajak'] ?? 0); 
    $TunJab           = intval($_POST['TunJab'] ?? 0);  
    $TunLain          = intval($_POST['TunLain'] ?? 0); 
    $pinjaman_manual  = intval($_POST['pinjaman_manual'] ?? 0); 
    
    // STEP A: Ambil aturan Gaji Pokok & Denda dari Master
    $query_gaji = "SELECT ag.id_gaji, ag.gaji_pokok, ag.potKehadiran 
                   FROM userkaryawan uk
                   JOIN aturgajipokok ag ON ag.id_jabatan = uk.id_jabatan AND ag.id_level = uk.id_level
                   WHERE uk.id_karyawan = '$id_karyawan' LIMIT 1";
                   
    $result_gaji = mysqli_query($koneksi, $query_gaji);
    $data_gaji = mysqli_fetch_assoc($result_gaji);

    if (!$data_gaji) {
        echo "<script>alert('Gagal memproses! Konfigurasi Gaji Pokok untuk karyawan ini belum diatur di Master Gaji.'); window.location.href='gaji.php';</script>";
        exit;
    }

    $id_gaji_master     = $data_gaji['id_gaji'];
    $gaji_pokok         = $data_gaji['gaji_pokok'];
    $POTONGAN_PER_ALPHA = $data_gaji['potKehadiran'] ?? 0; 

    // STEP B: Logika Perhitungan Target Kehadiran Dinamis
    $jml_hari_bulan = date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));
    $jml_minggu = 0;
    for ($i = 1; $i <= $jml_hari_bulan; $i++) {
        $tanggal_cek = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
        if (date('w', strtotime($tanggal_cek)) == 0) { 
            $jml_minggu++;
        }
    }
    
    $target_hari_kerja = $jml_hari_bulan - $jml_minggu;

    $query_absen = "SELECT COUNT(*) AS total_hadir FROM presensi 
                    WHERE id_karyawan = '$id_karyawan' 
                      AND sttsPresensi = 'Hadir' 
                      AND MONTH(tglPresensi) = '$bulan' 
                      AND YEAR(tglPresensi) = '$tahun'";
                      
    $result_absen = mysqli_query($koneksi, $query_absen);
    $data_absen = mysqli_fetch_assoc($result_absen);
    $total_hadir = $data_absen['total_hadir'] ?? 0;

    $hari_alpha = 0;
    if ($total_hadir < $target_hari_kerja) {
        $hari_alpha = $target_hari_kerja - $total_hadir; 
    }
    
    $potongan_kehadiran = $hari_alpha * $POTONGAN_PER_ALPHA;

    // STEP C: Kalkulasi Total Matematika Gaji
    $total_tunjangan = $TunJab + $TunLain;
    $total_potongan  = $potongan_kehadiran + $pinjaman_manual + $potBPJS + $potongan_pajak;
    $total_diterima  = ($gaji_pokok + $total_tunjangan + $Insentif) - $total_potongan;
    
    $bulan_string = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"][$bulan];
    
    // STEP D: Simpan riwayat hitungan ke dalam tabel rekapgaji
    $cek_rekap = mysqli_query($koneksi, "SELECT id_rekap FROM rekapgaji WHERE id_karyawan = '$id_karyawan' AND periodebulan = '$bulan_string' AND tahun = '$tahun'");
    
    if (mysqli_num_rows($cek_rekap) > 0) {
        $q_rekap = "UPDATE rekapgaji SET 
                    id_gaji = '$id_gaji_master', gaji_pokok = '$gaji_pokok', t_jabatan = '$TunJab', t_lain = '$TunLain',
                    insentif = '$Insentif', p_kehadiran = '$potongan_kehadiran', p_bpjs = '$potBPJS',
                    p_pajak = '$potongan_pajak', p_kasbon = '$pinjaman_manual', gaji_bersih = '$total_diterima',
                    status_bayar = 'Belum Dibayar'
                    WHERE id_karyawan = '$id_karyawan' AND periodebulan = '$bulan_string' AND tahun = '$tahun'";
    } else {
        $q_rekap = "INSERT INTO rekapgaji (id_karyawan, periodebulan, tahun, id_gaji, gaji_pokok, t_jabatan, t_lain, insentif, p_kehadiran, p_bpjs, p_pajak, p_kasbon, gaji_bersih, status_bayar) 
                    VALUES ('$id_karyawan', '$bulan_string', '$tahun', '$id_gaji_master', '$gaji_pokok', '$TunJab', '$TunLain', '$Insentif', '$potongan_kehadiran', '$potBPJS', '$potongan_pajak', '$pinjaman_manual', '$total_diterima', 'Belum Dibayar')";
    }
    
    if (mysqli_query($koneksi, $q_rekap)) {
        echo "<script>
            alert('Gaji berhasil dihitung!\\nTarget Kerja: $target_hari_kerja hari\\nHadir Aktual: $total_hadir hari\\nKekurangan: $hari_alpha hari (Dipotong)'); 
            window.location.href='gaji.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan rincian: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 3. PROSES VERIFIKASI PEMBAYARAN PER PERIODE
// =========================================================================
if (isset($_GET['action']) && isset($_GET['id_rekap'])) {
    $id_rekap_aksi = mysqli_real_escape_string($koneksi, $_GET['id_rekap']);
    $action = $_GET['action'];

    if ($action == 'bayar') {
        $query_status = "UPDATE rekapgaji SET status_bayar = 'Sudah Dibayar' WHERE id_rekap = '$id_rekap_aksi'";
    } else if ($action == 'batal') {
        $query_status = "UPDATE rekapgaji SET status_bayar = 'Belum Dibayar' WHERE id_rekap = '$id_rekap_aksi'";
    }

    if (mysqli_query($koneksi, $query_status)) {
        header("Location: gaji.php");
        exit;
    }
}

// AMBIL DATA KARYAWAN UNTUK DROPDOWN FORM
$list_karyawan = [];
$query_karyawan_drop = mysqli_query($koneksi, "SELECT id_karyawan, nmaKaryawan FROM userkaryawan ORDER BY nmaKaryawan ASC");
while($k = mysqli_fetch_assoc($query_karyawan_drop)) {
    $list_karyawan[] = $k;
}

// =========================================================================
// 4. PENANGKAPAN PARAMETER PENCARIAN & FILTER
// =========================================================================
$search_nama  = $_GET['search_nama'] ?? '';
$filter_bulan = $_GET['filter_bulan'] ?? '';
$filter_tahun = $_GET['filter_tahun'] ?? '';

$s_nama  = mysqli_real_escape_string($koneksi, $search_nama);
$f_bulan = mysqli_real_escape_string($koneksi, $filter_bulan);
$f_tahun = mysqli_real_escape_string($koneksi, $filter_tahun);

$q_tampil = "SELECT rg.*, u.nmaKaryawan 
             FROM rekapgaji rg 
             JOIN userkaryawan u ON rg.id_karyawan = u.id_karyawan 
             WHERE 1=1 "; 

if (!empty($s_nama)) {
    $q_tampil .= " AND u.nmaKaryawan LIKE '%$s_nama%' ";
}
if (!empty($f_bulan)) {
    $q_tampil .= " AND rg.periodebulan = '$f_bulan' ";
}
if (!empty($f_tahun)) {
    $q_tampil .= " AND rg.tahun = '$f_tahun' ";
}

$q_tampil .= " ORDER BY rg.tahun DESC, FIELD(rg.periodebulan, 'Desember', 'November', 'Oktober', 'September', 'Agustus', 'Juli', 'Juni', 'Mei', 'April', 'Maret', 'Februari', 'Januari') DESC, rg.id_rekap DESC";

$result_rekap = mysqli_query($koneksi, $q_tampil);
$error_db = mysqli_error($koneksi); 
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan - Gaji</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            <aside class="sidebar active">
                <a href="index.php" class="brand"><img src="assets/logoputih.svg" class="logo" alt="logo"></a>
                <nav class="nav-menu">
                    <a href="dashboardperusahaan.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                    <a href="presensi1.php" class="nav-item"><i class="fa-solid fa-square-check"></i> Presensi</a>
                    <a href="biodata.php" class="nav-item"><i class="fa-solid fa-id-card"></i> Data Karyawan</a>
                    <a href="gaji.php" class="nav-item active"><i class="fa-solid fa-calendar-days"></i> Gaji</a>
                    <a href="master_gaji.php" class="nav-item"><i class="fa-solid fa-gears"></i> Manajemen Gaji</a>
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
                        <form method="GET" action="gaji.php" class="search-wrapper">
                            <input type="hidden" name="filter_bulan" value="<?= htmlspecialchars($filter_bulan) ?>">
                            <input type="hidden" name="filter_tahun" value="<?= htmlspecialchars($filter_tahun) ?>">
                            <input type="text" name="search_nama" class="search-input" placeholder="Cari nama karyawan..." value="<?= htmlspecialchars($search_nama) ?>">
                            <button type="submit" style="background:none; border:none;"><i class="fa-solid fa-magnifying-glass icon-btn"></i></button>
                        </form>
                        <span class="user-name">
                            <?php 
                            $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                            $bulan_list = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                            echo $hari[date('w')] . ", " . date('j') . " " . $bulan_list[date('n')] . " " . date('Y'); 
                            ?>
                        </span>
                    </div>
                </header>

                <div class="content-body">
                    <div class="table-container">
                        
                        <div class="form-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 10px;">
                            <h3 style="margin: 0;">Riwayat Rincian Gaji & Status Pembayaran</h3>
                            
                            <form method="GET" action="gaji.php" style="display: flex; gap: 10px; align-items: center;">
                                <input type="hidden" name="search_nama" value="<?= htmlspecialchars($search_nama) ?>">
                                
                                <select name="filter_bulan" class="form-control" style="width: auto; padding: 6px 10px; border-radius: 5px;">
                                    <option value="">-- Semua Bulan --</option>
                                    <?php
                                    $bulan_arr = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                                    foreach($bulan_arr as $b) {
                                        $sel = ($filter_bulan == $b) ? 'selected' : '';
                                        echo "<option value='$b' $sel>$b</option>";
                                    }
                                    ?>
                                </select>

                                <select name="filter_tahun" class="form-control" style="width: auto; padding: 6px 10px; border-radius: 5px;">
                                    <option value="">-- Semua Tahun --</option>
                                    <?php
                                    $current_year = date('Y');
                                    $years = [$current_year];
                                    $q_tahun = mysqli_query($koneksi, "SELECT DISTINCT tahun FROM rekapgaji WHERE tahun IS NOT NULL ORDER BY tahun DESC");
                                    while($th = mysqli_fetch_assoc($q_tahun)) {
                                        if (!in_array($th['tahun'], $years)) {
                                            $years[] = $th['tahun'];
                                        }
                                    }
                                    rsort($years); 
                                    foreach($years as $t) {
                                        $sel = ($filter_tahun == $t) ? 'selected' : '';
                                        echo "<option value='$t' $sel>$t</option>";
                                    }
                                    ?>
                                </select>

                                <button type="submit" class="btn-submit" style="padding: 6px 15px; margin: 0;"><i class="fa-solid fa-filter"></i> Filter</button>
                                
                                <?php if(!empty($filter_bulan) || !empty($filter_tahun)): ?>
                                    <a href="gaji.php?search_nama=<?= urlencode($search_nama) ?>" class="btn-cancel" style="padding: 6px 15px; text-decoration: none; margin: 0;">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Karyawan</th>
                                    <th>Periode</th>
                                    <th>Gaji Pokok (Base)</th>
                                    <th>Total Tunjangan</th>
                                    <th>Total Potongan</th>
                                    <th>Insentif Penjualan</th>
                                    <th>Gaji Bersih</th>
                                    <th>Status Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!$result_rekap && !empty($error_db)) {
                                    echo "<tr><td colspan='10' class='error-row-cell'><strong>ERROR DATABASE:</strong> " . htmlspecialchars($error_db) . "</td></tr>";
                                } else if ($result_rekap && mysqli_num_rows($result_rekap) > 0) {
                                    $no = 1;
                                    while ($row = mysqli_fetch_assoc($result_rekap)) {
                                        
                                        $tunjangan = ($row['t_jabatan'] ?? 0) + ($row['t_lain'] ?? 0);
                                        $potongan  = ($row['p_kehadiran'] ?? 0) + ($row['p_bpjs'] ?? 0) + ($row['p_pajak'] ?? 0) + ($row['p_kasbon'] ?? 0);
                                        $status_bayar = $row['status_bayar'] ?? 'Belum Dibayar';
                                        
                                        $id_toggle = "detail-" . $row['id_rekap']; // ID unik untuk baris toggle
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class="text-bold">
                                                <a href="#" onclick="toggleDetail('<?= $id_toggle ?>'); return false;" style="color: #007bff; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($row['nmaKaryawan']) ?>
                                                </a>
                                            </td>
                                            <td><span class="badge-status badge-belum" style="background:#007bff; color:white; border:none; padding:4px 8px; border-radius:4px;"><?= $row['periodebulan'] . " " . $row['tahun'] ?></span></td>
                                            <td class="text-bold" style="color: #4a5568;">Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($tunjangan, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($potongan, 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($row['insentif'], 0, ',', '.') ?></td>
                                            <td class="text-company-name" style="font-weight: bold;">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></td>
                                            <td>
                                                <?php if($status_bayar == 'Sudah Dibayar'): ?>
                                                    <span class="badge-status badge-dibayar"><i class="fa-solid fa-circle-check"></i> Sudah Dibayar</span>
                                                <?php else: ?>
                                                    <span class="badge-status badge-belum"><i class="fa-solid fa-circle-xmark"></i> Belum Dibayar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($status_bayar == 'Belum Dibayar'): ?>
                                                    <a href="gaji.php?action=bayar&id_rekap=<?= $row['id_rekap'] ?>" class="btn-submit btn-bayar" onclick="return confirm('Verifikasi pembayaran?')">Set Dibayar</a>
                                                <?php else: ?>
                                                    <a href="gaji.php?action=batal&id_rekap=<?= $row['id_rekap'] ?>" class="btn-batal" onclick="return confirm('Batalkan verifikasi?')">Batalkan</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <tr id="<?= $id_toggle ?>" class="detail-row">
                                            <td colspan="10">
                                                <div class="detail-container">
                                                    <div class="detail-header">
                                                        <strong>Rincian Slip Gaji:</strong> <?= htmlspecialchars($row['nmaKaryawan']) ?> (Periode: <?= $row['periodebulan'] . " " . $row['tahun'] ?>)
                                                    </div>
                                                    <div class="detail-grid">
                                                        
                                                        <div class="detail-box">
                                                            <h4><i class="fa-solid fa-arrow-trend-up"></i> Komponen Penerimaan</h4>
                                                            <div class="detail-item">
                                                                <span>Gaji Pokok Base</span>
                                                                <span>Rp <?= number_format($row['gaji_pokok'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span>Tunjangan Jabatan</span>
                                                                <span>Rp <?= number_format($row['t_jabatan'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span>Tunjangan Lain-lain</span>
                                                                <span>Rp <?= number_format($row['t_lain'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span>Insentif Penjualan</span>
                                                                <span>Rp <?= number_format($row['insentif'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <?php $total_terima = $row['gaji_pokok'] + $row['t_jabatan'] + $row['t_lain'] + $row['insentif']; ?>
                                                            <div class="detail-item total">
                                                                <span>Total Penerimaan Kotor</span>
                                                                <span>Rp <?= number_format($total_terima, 0, ',', '.') ?></span>
                                                            </div>
                                                        </div>

                                                        <div class="detail-box box-danger">
                                                            <h4><i class="fa-solid fa-arrow-trend-down"></i> Komponen Potongan</h4>
                                                            <div class="detail-item">
                                                                <span>Denda Absensi (Alpha)</span>
                                                                <span>- Rp <?= number_format($row['p_kehadiran'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span>Potongan BPJS</span>
                                                                <span>- Rp <?= number_format($row['p_bpjs'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span>Potongan Pajak</span>
                                                                <span>- Rp <?= number_format($row['p_pajak'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item">
                                                                <span>Pinjaman / Kasbon</span>
                                                                <span>- Rp <?= number_format($row['p_kasbon'], 0, ',', '.') ?></span>
                                                            </div>
                                                            <div class="detail-item total" style="color: #dc2626;">
                                                                <span>Total Potongan</span>
                                                                <span>- Rp <?= number_format($potongan, 0, ',', '.') ?></span>
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div style="margin-top: 15px; text-align: right;">
                                                        <span style="font-size: 14px; color: #64748b; margin-right: 15px;">Total Gaji Bersih (Take Home Pay)</span>
                                                        <span class="badge-status badge-dibayar" style="font-size: 16px; padding: 8px 15px;">Rp <?= number_format($row['gaji_bersih'], 0, ',', '.') ?></span>
                                                    </div>

                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='10' class='text-center text-muted' style='padding: 20px;'>Belum ada riwayat gaji yang dihitung atau filter tidak ditemukan.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-container">
                        <div class="form-title">
                            <h3><i class="fa-solid fa-calculator"></i> Hitung Gaji Karyawan Baru</h3>
                        </div>
                        <form action="gaji.php" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="id_karyawan">Pilih Karyawan</label>
                                    <select name="id_karyawan" id="id_karyawan" class="form-control" required>
                                        <option value="">-- Pilih Karyawan --</option>
                                        <?php foreach($list_karyawan as $k) { echo "<option value='".$k['id_karyawan']."'>".$k['nmaKaryawan']."</option>"; } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="bulan">Bulan Periode</label>
                                    <select name="bulan" id="bulan" class="form-control" required>
                                        <?php for($m=1; $m<=12; $m++) { $sel = ($m == date('n')) ? 'selected' : ''; echo "<option value='$m' $sel>".$bulan_list[$m]."</option>"; } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="tahun">Tahun Periode</label>
                                    <input type="number" name="tahun" id="tahun" class="form-control" value="<?= date('Y') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="insentif">Insentif Penjualan (Rp)</label>
                                    <input type="number" name="insentif" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="potBPJS">Potongan BPJS (Rp)</label>
                                    <input type="number" name="potBPJS" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="potPajak">Potongan Pajak (Rp)</label>
                                    <input type="number" name="potPajak" id="potPajak" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="TunJab">Tunjangan Jabatan (Rp)</label>
                                    <input type="number" name="TunJab" class="form-control" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="TunLain">Tunjangan Lain-lain (Rp)</label>
                                    <input type="number" name="TunLain" class="form-control" value="0">
                                </div>
                                <div class="form-group span-two">
                                    <label for="pinjaman_manual">Potongan Kasbon/Pinjaman (Rp)</label>
                                    <input type="number" name="pinjaman_manual" id="pinjaman_manual" class="form-control" value="0">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="reset" class="btn-cancel"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                                <button type="submit" name="Hitung_gaji" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Hitung & Simpan Gaji</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>

        <script>
            // Fungsi Javascript untuk membuka/tutup rincian slip gaji
            function toggleDetail(rowId) {
                var detailRow = document.getElementById(rowId);
                if (detailRow) {
                    detailRow.classList.toggle('show');
                }
            }
        </script>
    </body>
</html>
<?php ob_end_flush(); ?>