<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistempenggajian";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) { die("Koneksi gagal: " . mysqli_connect_error()); }

// ==========================================
// 1. PROSES TAMBAH / UPDATE ATURAN BARU (FORM BAWAH)
// ==========================================
if (isset($_POST['simpan_master'])) {
    $id_jabatan   = mysqli_real_escape_string($koneksi, $_POST['id_jabatan']);
    $id_level     = mysqli_real_escape_string($koneksi, $_POST['id_level']);
    $gaji_pokok   = intval($_POST['gaji_pokok']);
    $potKehadiran = intval($_POST['potKehadiran']);

    // Simpan kombinasi unik Jabatan + Level + Gaji Pokok + Denda Kehadiran ke aturgajipokok
    $cek_ag = mysqli_query($koneksi, "SELECT id_gaji FROM aturgajipokok WHERE id_jabatan = '$id_jabatan' AND id_level = '$id_level'");
    if (mysqli_num_rows($cek_ag) > 0) {
        $q_ag = "UPDATE aturgajipokok SET gaji_pokok = '$gaji_pokok', potKehadiran = '$potKehadiran' WHERE id_jabatan = '$id_jabatan' AND id_level = '$id_level'";
    } else {
        $q_ag = "INSERT INTO aturgajipokok (id_jabatan, id_level, gaji_pokok, potKehadiran) VALUES ('$id_jabatan', '$id_level', '$gaji_pokok', '$potKehadiran')";
    }
    
    if (mysqli_query($koneksi, $q_ag)) {
        echo "<script>alert('Aturan Gaji Pokok & Potongan Berhasil Disimpan!'); window.location.href='master_gaji.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan: " . mysqli_error($koneksi) . "');</script>";
    }
}

// ==========================================
// 2. PROSES UPDATE VIA TOMBOL EDIT (FORM TOGGLE ATAS)
// ==========================================
if (isset($_POST['update_master'])) { 
    $id_gaji      = mysqli_real_escape_string($koneksi, $_POST['id_gaji']);
    $gaji_pokok   = intval($_POST['gaji_pokok']);
    $potKehadiran = intval($_POST['potKehadiran']);

    // Mengupdate data secara presisi berdasarkan id baris aturan tanpa mengganggu level/jabatan lain
    $update_ag = "UPDATE aturgajipokok SET gaji_pokok = '$gaji_pokok', potKehadiran = '$potKehadiran' WHERE id_gaji = '$id_gaji'";

    if (mysqli_query($koneksi, $update_ag)) {
        echo "<script>alert('Aturan Gaji Berhasil Diperbarui!'); window.location.href='master_gaji.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// AMBIL DATA DROPDOWN
$master_jabatan = mysqli_query($koneksi, "SELECT * FROM jabatan ORDER BY nmaJabatan ASC");
$master_level   = mysqli_query($koneksi, "SELECT * FROM levelkaryawan ORDER BY nmaLevel ASC");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Master Aturan Gaji</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
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
                    <a href="gaji.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Gaji</a>
                    <a href="master_gaji.php" class="nav-item active"><i class="fa-solid fa-gears"></i> Manajemen Gaji</a>
                </nav>
                <div class="sidebar-footer">
                    <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Apakah anda yakin ingin logout?');">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </aside>

            <main class="main-content sidebar-active">
                <div class="content-body" style="margin-top: 50px;">
                    
                    <div class="table-container">
                        <div class="form-title"><h3>Daftar Aturan Gaji Pokok & Potongan Kehadiran</h3></div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jabatan</th>
                                    <th>Level</th>
                                    <th>Gaji Pokok (Base)</th>
                                    <th>Denda Alpha / Hari</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res_master = mysqli_query($koneksi, "SELECT ag.*, j.nmaJabatan, l.nmaLevel 
                                               FROM aturgajipokok ag 
                                               LEFT JOIN jabatan j ON ag.id_jabatan = j.id_jabatan 
                                               LEFT JOIN levelkaryawan l ON ag.id_level = l.id_level 
                                               ORDER BY ag.id_gaji DESC");
                                $no_m = 1;
                                while($rm = mysqli_fetch_assoc($res_master)) {
                                    $edit_id = "edit-master-" . $rm['id_gaji'];
                                ?>
                                <tr>
                                    <td><?= $no_m++ ?></td>
                                    <td class="text-bold"><?= htmlspecialchars($rm['nmaJabatan'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($rm['nmaLevel'] ?? 'N/A') ?></td>
                                    <td>Rp <?= number_format($rm['gaji_pokok'], 0, ',', '.') ?></td>
                                    <td class="text-company-name">Rp <?= number_format($rm['potKehadiran'] ?? 0, 0, ',', '.') ?></td>
                                    <td>
                                        <a href="#" class="btn-action-edit btn-action-update" onclick="toggleEditRow('<?= $edit_id ?>'); return false;"><i class="fa-solid fa-pen"></i> Edit</a>
                                    </td>
                                </tr>

                                <tr id="<?= $edit_id ?>" class="detail-row" style="display:none;">
                                    <td colspan="6">
                                        <div class="form-container" style="border: 1px dashed #007bff; margin: 10px; padding: 15px;">
                                            <form action="master_gaji.php" method="POST">
                                                <input type="hidden" name="id_gaji" value="<?= $rm['id_gaji']; ?>">
                                                <div class="form-grid">
                                                    <div class="form-group">
                                                        <label>Gaji Pokok Baru (Rp)</label>
                                                        <input type="number" name="gaji_pokok" class="form-control" value="<?= $rm['gaji_pokok']; ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Denda Alpha Baru (Rp)</label>
                                                        <input type="number" name="potKehadiran" class="form-control" value="<?= $rm['potKehadiran'] ?? 0; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="form-actions" style="margin-top: 10px;">
                                                    <button type="button" class="btn-cancel" onclick="toggleEditRow('<?= $edit_id ?>')">Batal</button>
                                                    <button type="submit" name="update_master" class="btn-submit" style="background-color: #28a745;">Update Aturan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-container">
                        <div class="form-title"><h3><i class="fa-solid fa-sliders"></i> Atur Konfigurasi Gaji Pokok Baru</h3></div>
                        <form action="master_gaji.php" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Pilih Jabatan</label>
                                    <select name="id_jabatan" class="form-control" required>
                                        <option value="">-- Pilih Jabatan --</option>
                                        <?php mysqli_data_seek($master_jabatan, 0); while($jb = mysqli_fetch_assoc($master_jabatan)) { echo "<option value='".$jb['id_jabatan']."'>".$jb['nmaJabatan']."</option>"; } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Pilih Level</label>
                                    <select name="id_level" class="form-control" required>
                                        <option value="">-- Pilih Level --</option>
                                        <?php mysqli_data_seek($master_level, 0); while($lv = mysqli_fetch_assoc($master_level)) { echo "<option value='".$lv['id_level']."'>".$lv['nmaLevel']."</option>"; } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Gaji Pokok (Rp)</label>
                                    <input type="number" name="gaji_pokok" class="form-control" placeholder="Contoh: 3000000" required>
                                </div>
                                <div class="form-group">
                                    <label>Potongan per Hari Alpha (Rp)</label>
                                    <input type="number" name="potKehadiran" class="form-control" placeholder="Contoh: 100000" required>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="simpan_master" class="btn-submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi</button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>

        
    </body>
</html>
<?php ob_end_flush(); ?>