<?php
session_start();
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta'); 

// ==========================================
// 1. KONEKSI DATABASE
// ==========================================
$host = 'localhost';
$user = 'root';
$pass = "";
$db   = 'sistempenggajian';
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

if (!isset($_SESSION['loginKaryawan'])) {
    header("Location: loginkaryawan.php");
    exit;
}

$id_karyawan = mysqli_real_escape_string($conn, $_SESSION['id_karyawan'] ?? '');
$hari_ini = date('Y-m-d');
// Gunakan format full datetime untuk insert ke database agar kompatibel dgn tipe data DATETIME
$waktu_sekarang = date('Y-m-d H:i:s'); 

if (!function_exists('hitungJarak')) {
    function hitungJarak($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        if ($dist > 1) $dist = 1;
        if ($dist < -1) $dist = -1;
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344) * 1000;
    }
}

// Fungsi Bantuan Pengecekan Waktu (Bypass default MySQL 00:00:00)
function isValidTime($timeStr) {
    return !empty($timeStr) && $timeStr != '00:00:00' && $timeStr != '0000-00-00 00:00:00';
}

// ==========================================
// 2. BACKEND AJAX PROCESSOR 
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'absen_geo') {
    ob_clean(); 
    header('Content-Type: application/json'); 

    $lat_user = $_POST['latitude'] ?? '';
    $lng_user = $_POST['longitude'] ?? '';
    $jenis_absen = $_POST['jenis_absen'] ?? '';
    $stts_presensi = mysqli_real_escape_string($conn, $_POST['sttsPresensi'] ?? 'Hadir');
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    if(empty($lat_user) || empty($lng_user)) {
        echo json_encode(["status" => "gagal", "pesan" => "Koordinat GPS gagal didapatkan oleh perangkat."]);
        exit;
    }

    $query_kantor = mysqli_query($conn, "SELECT latitude, longitude, radius FROM lokasi LIMIT 1");
    $data_kantor = mysqli_fetch_assoc($query_kantor);
    $lat_kantor = $data_kantor['latitude']; 
    $lng_kantor = $data_kantor['longitude']; 
    $radius_maks = $data_kantor['radius']; 

    if (strtolower($stts_presensi) == 'hadir') {
        $jarak_user = hitungJarak($lat_user, $lng_user, $lat_kantor, $lng_kantor);
        if ($jarak_user > $radius_maks) {
            $jarak_bulat = round($jarak_user);
            echo json_encode(["status" => "gagal", "pesan" => "Gagal Absen! Jarak Anda {$jarak_bulat} meter dari kantor (Batas: {$radius_maks}m)."]);
            exit;
        }
    }

    // Proses Simpan Absen Masuk
    if ($jenis_absen == 'masuk') {
        $cek = mysqli_query($conn, "SELECT * FROM presensi WHERE id_karyawan = '$id_karyawan' AND tglPresensi = '$hari_ini'");
        if(mysqli_num_rows($cek) == 0) {
            $insert = "INSERT INTO presensi (tglPresensi, jamMasuk, sttsPresensi, catatan, id_karyawan) VALUES ('$hari_ini', '$waktu_sekarang', '$stts_presensi', '$catatan', '$id_karyawan')";
            if(mysqli_query($conn, $insert)){
                echo json_encode(["status" => "sukses", "pesan" => "Data presensi ($stts_presensi) berhasil disimpan!"]);
            } else {
                echo json_encode(["status" => "gagal", "pesan" => "Gagal ke database: " . mysqli_error($conn)]);
            }
        } else {
            echo json_encode(["status" => "gagal", "pesan" => "Anda sudah mengisi form kehadiran hari ini!"]);
        }
    } 
    // Proses Simpan Absen Keluar
    else if ($jenis_absen == 'keluar') {
        $cek = mysqli_query($conn, "SELECT jamMasuk FROM presensi WHERE id_karyawan = '$id_karyawan' AND tglPresensi = '$hari_ini'");
        if(mysqli_num_rows($cek) > 0) {
            $data = mysqli_fetch_assoc($cek);
            
            // Hitung jam kerja untuk disimpan ke DB
            $selisih_detik = strtotime($waktu_sekarang) - strtotime($data['jamMasuk']);
            $total_jam = round($selisih_detik / 3600, 2);
            if($total_jam < 0) $total_jam = 0;

            $query_catatan = "";
            if (!empty($catatan)) {
                $query_catatan = ", catatan = CONCAT(IFNULL(catatan, ''), ' Pulang: ', '$catatan')";
            }

            $update = "UPDATE presensi SET jamKeluar = '$waktu_sekarang', jamKerja = '$total_jam' $query_catatan WHERE id_karyawan = '$id_karyawan' AND tglPresensi = '$hari_ini'";
            if(mysqli_query($conn, $update)){
                echo json_encode(["status" => "sukses", "pesan" => "Absen Keluar Berhasil Disimpan!"]);
            } else {
                echo json_encode(["status" => "gagal", "pesan" => "Gagal update database: " . mysqli_error($conn)]);
            }
        }
    }
    exit; 
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Presensi Karyawan</title>
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
                <a href="dashboardkaryawan.php" class="brand"><img src="assets/logoputih.svg" class="logo" alt="logo"></a>
                <nav class="nav-menu">
                    <a href="dashboardkaryawan.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                    <a href="presensikaryawan.php" class="nav-item active"><i class="fa-solid fa-square-check"></i> Presensi</a>
                    <a href="gajikaryawan.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Gaji</a>
                    <a href="ordersales.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> Penjualan</a>
                </nav>
                <div class="sidebar-footer">
                    <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Apakah anda yakin ingin logout?');">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </aside>

            <main class="main-content">
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
                            <h3>Presensi</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Keluar</th>
                                    <th>Total Waktu</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT tglPresensi, sttsPresensi, jamMasuk, jamKeluar, jamKerja, catatan 
                                          FROM presensi 
                                          WHERE id_karyawan = '$id_karyawan' 
                                          ORDER BY tglPresensi DESC";
                                $result = mysqli_query($conn, $query);

                                $data_list = [];
                                $sudah_absen_hari_ini = false;

                                if ($result && mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $data_list[] = $row;
                                        if ($row['tglPresensi'] == $hari_ini) {
                                            $sudah_absen_hari_ini = true;
                                        }
                                    }
                                }

                                // KONDISI 1: BELUM ABSEN SAMA SEKALI HARI INI
                                if (!$sudah_absen_hari_ini) {
                                    $tgl_format_sekarang = date('d F Y', strtotime($hari_ini));
                                    echo "<tr>";
                                    echo "<td class='text-bold'>{$tgl_format_sekarang} <span class='badge-today'>Hari Ini</span></td>";
                                    echo "<td><span class='text-muted font-weight-bold'>Belum Absen</span></td>";
                                    echo "<td>-</td><td>-</td><td>-</td><td>-</td>";
                                    echo "<td>
                                            <button type='button' onclick=\"toggleEditRow('absen-masuk-hari-ini')\" class='btn-action-presensi btn-toggle-blue'>
                                                <i class='fa-solid fa-pen-to-square'></i> Absen
                                            </button>
                                          </td>";
                                    echo "</tr>";

                                    echo "<tr id='absen-masuk-hari-ini' class='edit-row' style='display: none;'>";
                                    echo "<td colspan='7' class='dropdown-form-container'>";
                                    echo "  <div class='form-presensi-wrapper'>";
                                    echo "      <div class='form-row-grid'>";
                                    echo "          <div class='form-group-item'>";
                                    echo "              <label>Status Kehadiran</label>";
                                    echo "              <select id='sttsPresensi-masuk' class='form-control-style'>";
                                    echo "                  <option value='Hadir'>Hadir</option>";
                                    echo "                  <option value='Izin'>Izin</option>";
                                    echo "                  <option value='Sakit'>Sakit</option>";
                                    echo "              </select>";
                                    echo "          </div>";
                                    echo "          <div class='form-group-item'>";
                                    echo "              <label>Catatan / Keterangan (Opsional)</label>";
                                    echo "              <input type='text' id='catatan-masuk' class='form-control-style' placeholder='Tambahkan catatan...'>";
                                    echo "          </div>";
                                    echo "      </div>";
                                    echo "      <button type='button' onclick='ambilLokasi(\"masuk\")' class='btn-action-presensi btn-masuk' style='margin-top: 15px;'>";
                                    echo "          Masuk";
                                    echo "      </button>";
                                    echo "  </div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }

                                // KONDISI 2: MENAMPILKAN RIWAYAT & TOMBOL ABSEN KELUAR
                                if (count($data_list) > 0) {
                                    foreach($data_list as $row) {
                                        $tgl = date('d F Y', strtotime($row['tglPresensi']));
                                        $status = htmlspecialchars($row['sttsPresensi']);
                                        $catatan_tabel = !empty($row['catatan']) ? htmlspecialchars($row['catatan']) : '-';

                                        // PENGECEKAN VALIDASI JAM DARI DATABASE
                                        $jamMasukValid = isValidTime($row['jamMasuk']);
                                        $jamKeluarValid = isValidTime($row['jamKeluar']);

                                        // Format waktu menjadi H:i:s murni
                                        $jamMasukCetak = $jamMasukValid ? date('H:i:s', strtotime($row['jamMasuk'])) : '-';
                                        $jamKeluarCetak = $jamKeluarValid ? date('H:i:s', strtotime($row['jamKeluar'])) : '-';

                                        // LOGIKA PENGHITUNGAN JAM KERJA DENGAN MENIT & DETIK
                                        $teks_waktu_kerja = '-';
                                        if ($jamMasukValid && $jamKeluarValid) {
                                            // Hitung selisih dari waktu aslinya langsung
                                            $waktu_keluar_utuh = $row['tglPresensi'] . ' ' . $row['jamKeluar'];
                                            $selisih = strtotime($waktu_keluar_utuh) - strtotime($row['jamMasuk']);
                                            
                                            if ($selisih >= 0) {
                                                $jam_kerja = floor($selisih / 3600);
                                                $menit_kerja = floor(($selisih % 3600) / 60);
                                                $detik_kerja = $selisih % 60;

                                                if ($jam_kerja > 0) {
                                                    $teks_waktu_kerja = "{$jam_kerja} Jam {$menit_kerja} Menit";
                                                } else if ($menit_kerja > 0) {
                                                    $teks_waktu_kerja = "{$menit_kerja} Menit";
                                                } else {
                                                    $teks_waktu_kerja = "{$detik_kerja} Detik";
                                                }
                                            }
                                        }

                                        $statusClass = (strtolower($status) == 'hadir') ? 'text-success font-weight-bold' : 'text-warning font-weight-bold';

                                        echo "<tr>";
                                        echo "<td class='text-bold'>{$tgl} " . ($row['tglPresensi'] == $hari_ini ? "<span class='badge-today'>Hari Ini</span>" : "") . "</td>";
                                        echo "<td><span class='{$statusClass}'>{$status}</span></td>";
                                        echo "<td>{$jamMasukCetak}</td>";
                                        echo "<td>{$jamKeluarCetak}</td>";
                                        echo "<td><span class='text-muted' style='font-size: 0.9em; font-weight: 500;'>{$teks_waktu_kerja}</span></td>";
                                        echo "<td>{$catatan_tabel}</td>";
                                        
                                        echo "<td>";
                                        if ($row['tglPresensi'] == $hari_ini) {
                                            if (strtolower($row['sttsPresensi']) != 'hadir') {
                                                echo "<span class='badge-status-done'><i class='fa-solid fa-circle-check'></i> Tidak Hadir </span>";
                                            } else if (!$jamKeluarValid) {
                                                // Jika status Hadir & jam keluar belum valid, muncul tombol toggle Keluar
                                                echo "<button type='button' onclick=\"toggleEditRow('absen-keluar-hari-ini')\" class='btn-action-presensi btn-toggle-blue'>
                                                        <i class='fa-solid fa-pen-to-square'></i> Absen
                                                      </button>";
                                            } else {
                                                echo "<span class='badge-status-done'><i class='fa-solid fa-circle-check'></i> Selesai</span>";
                                            }
                                        } else {
                                            echo "<span class='badge-status-done'><i class='fa-solid fa-circle-check'></i> Selesai</span>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";

                                        if ($row['tglPresensi'] == $hari_ini && !$jamKeluarValid && strtolower($row['sttsPresensi']) == 'hadir') {
                                            echo "<tr id='absen-keluar-hari-ini' class='edit-row' style='display: none;'>";
                                            echo "<td colspan='7' class='dropdown-form-container'>";
                                            echo "  <div class='form-presensi-wrapper'>";
                                            echo "      <div class='form-group-item' style='text-align: left; max-width: 500px; margin: 0 auto;'>";
                                            echo "          <label>Catatan Kepulangan (Opsional)</label>";
                                            echo "          <input type='text' id='catatan-keluar' class='form-control-style' placeholder='Tambahkan catatan...'>";
                                            echo "      </div>";
                                            echo "      <button type='button' onclick='ambilLokasi(\"keluar\")' class='btn-action-presensi btn-keluar' style='margin-top: 15px;'>";
                                            echo "          Pulang";
                                            echo "      </button>";
                                            echo "  </div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                } else if (count($data_list) == 0 && $sudah_absen_hari_ini) {
                                    echo "<tr><td colspan='7' class='text-center text-muted'>Belum ada data presensi.</td></tr>";
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
<?php ob_end_flush(); ?>