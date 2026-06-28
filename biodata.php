<?php
session_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =========================================================================
// 1. KONEKSI & KONFIGURASI DATABASE
// =========================================================================
$host     = "localhost";
$username = "root";
$password = "";
$database = "sistempenggajian";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// =========================================================================
// AUTO-FIX DATABASE: Ubah minTahun -> minBulan & maxTahun -> maxBulan otomatis
// =========================================================================
$cek_min = mysqli_query($koneksi, "SHOW COLUMNS FROM `levelkaryawan` LIKE 'minTahun'");
if ($cek_min && mysqli_num_rows($cek_min) > 0) {
    mysqli_query($koneksi, "ALTER TABLE `levelkaryawan` CHANGE `minTahun` `minBulan` INT(11) DEFAULT '0'");
}
$cek_max = mysqli_query($koneksi, "SHOW COLUMNS FROM `levelkaryawan` LIKE 'maxTahun'");
if ($cek_max && mysqli_num_rows($cek_max) > 0) {
    mysqli_query($koneksi, "ALTER TABLE `levelkaryawan` CHANGE `maxTahun` `maxBulan` INT(11) DEFAULT '999'");
}

// =========================================================================
// FUNGSI PINTAR: MENGHITUNG LEVEL OTOMATIS BERDASARKAN TGL GABUNG
// =========================================================================
function hitung_level_otomatis($koneksi, $tglGabung) {
    $query = "SELECT id_level FROM levelkaryawan WHERE TIMESTAMPDIFF(MONTH, '$tglGabung', CURDATE()) BETWEEN minBulan AND maxBulan LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    // Jika tidak ditemukan, set default ke level 1
    return $data ? $data['id_level'] : 1; 
}

// =========================================================================
// 2. PROSES ACTION: UPDATE ATURAN LEVEL (PENGATURAN BULAN)
// =========================================================================
if (isset($_POST['action']) && $_POST['action'] === 'update_aturan_level') {
    $id_level = intval($_POST['id_level']);
    $minBulan = intval($_POST['minBulan']);
    $maxBulan = intval($_POST['maxBulan']);
    
    $q_update = "UPDATE levelkaryawan SET minBulan = '$minBulan', maxBulan = '$maxBulan' WHERE id_level = '$id_level'";
    if (mysqli_query($koneksi, $q_update)) {
        echo "<script>alert('Aturan level berhasil diperbarui!'); window.location='biodata.php';</script>";
        exit;
    }
}

// =========================================================================
// 3. PROSES ACTION: TAMBAH DATA KARYAWAN
// =========================================================================
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nmaKaryawan   = mysqli_real_escape_string($koneksi, $_POST['nmaKaryawan']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $password_user = mysqli_real_escape_string($koneksi, $_POST['password']);
    $status        = mysqli_real_escape_string($koneksi, $_POST['status']); 
    $tglGabung     = mysqli_real_escape_string($koneksi, $_POST['tglGabung']);
    $jmlAnak       = intval($_POST['jmlAnak']);
    $id_jabatan    = intval($_POST['id_jabatan']);

    // Tentukan level otomatis berdasarkan tanggal gabung
    $id_level_otomatis = hitung_level_otomatis($koneksi, $tglGabung);

    $query = "INSERT INTO userkaryawan (nmaKaryawan, alamat, password, status, tglGabung, jmlAnak, id_level, id_jabatan) 
              VALUES ('$nmaKaryawan', '$alamat', '$password_user', '$status', '$tglGabung', '$jmlAnak', '$id_level_otomatis', '$id_jabatan')";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Karyawan ditambahkan & Level diatur otomatis!'); window.location='biodata.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menambah data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 4. PROSES ACTION: UPDATE DATA KARYAWAN
// =========================================================================
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id_karyawan   = intval($_POST['id_karyawan']);
    $nmaKaryawan   = mysqli_real_escape_string($koneksi, $_POST['nmaKaryawan']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $status        = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tglGabung     = mysqli_real_escape_string($koneksi, $_POST['tglGabung']);
    $jmlAnak       = intval($_POST['jmlAnak']);
    $id_jabatan    = intval($_POST['id_jabatan']);

    // Hitung ulang level otomatis jika admin mengubah tanggal bergabung
    $id_level_otomatis = hitung_level_otomatis($koneksi, $tglGabung);

    $password_update = "";
    if (!empty($_POST['password'])) {
        $password_user = mysqli_real_escape_string($koneksi, $_POST['password']);
        $password_update = ", password = '$password_user'";
    }

    $query_update = "UPDATE userkaryawan SET 
                        nmaKaryawan = '$nmaKaryawan', alamat = '$alamat', status = '$status', 
                        tglGabung = '$tglGabung', jmlAnak = '$jmlAnak', id_jabatan = '$id_jabatan',
                        id_level = '$id_level_otomatis' $password_update
                     WHERE id_karyawan = '$id_karyawan'";

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Data karyawan & level otomatis diperbarui!'); window.location='biodata.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal mengupdate data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 5. PROSES ACTION: HAPUS DATA KARYAWAN
// =========================================================================
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    if (mysqli_query($koneksi, "DELETE FROM userkaryawan WHERE id_karyawan = '$id_hapus'")) {
        echo "<script>alert('Data karyawan berhasil dihapus!'); window.location='biodata.php';</script>";
        exit;
    }
}

// =========================================================================
// 6. SINKRONISASI MASSAL: Pastikan semua level karyawan selalu terupdate otomatis
// =========================================================================
mysqli_query($koneksi, "
    UPDATE userkaryawan u
    SET u.id_level = COALESCE((
        SELECT id_level FROM levelkaryawan 
        WHERE TIMESTAMPDIFF(MONTH, u.tglGabung, CURDATE()) BETWEEN minBulan AND maxBulan LIMIT 1
    ), u.id_level)
    WHERE u.tglGabung IS NOT NULL AND u.tglGabung != '0000-00-00'
");

// =========================================================================
// AMBIL DATA UNTUK DITAMPILKAN
// =========================================================================
$query_jabatan = mysqli_query($koneksi, "SELECT * FROM jabatan ORDER BY nmaJabatan ASC");
$query_level_rule = mysqli_query($koneksi, "SELECT * FROM levelkaryawan ORDER BY minBulan ASC");

$query_tampil = "SELECT u.*, j.nmaJabatan, l.nmaLevel, TIMESTAMPDIFF(MONTH, u.tglGabung, CURDATE()) as masa_kerja_bulan 
                 FROM userkaryawan u
                 LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan
                 LEFT JOIN levelkaryawan l ON u.id_level = l.id_level
                 ORDER BY u.id_karyawan DESC";
$result_data = mysqli_query($koneksi, $query_tampil);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Perusahaan - Biodata</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">
        
        <aside class="sidebar active">
            <a href="index.php" class="brand"><img src="assets/logoputih.svg" class="logo" alt="logo"></a>
            <nav class="nav-menu">
                <a href="dashboardperusahaan.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="presensi1.php" class="nav-item"><i class="fa-solid fa-square-check"></i> Presensi</a>
                <a href="biodata.php" class="nav-item active"><i class="fa-solid fa-id-card"></i> Data Karyawan</a>
                <a href="gaji.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Gaji Bulanan</a>
                <a href="master_gaji.php" class="nav-item"><i class="fa-solid fa-gears"></i> Master Aturan Gaji</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Yakin ingin logout?');"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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
                        <h3>Daftar Karyawan Terdaftar</h3>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Level Saat Ini</th>
                                <th>Tgl Gabung / Masa Kerja</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result_data) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result_data)) {
                                    $edit_row_id = "edit-" . $row['id_karyawan'];
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="text-bold"><?= htmlspecialchars($row['nmaKaryawan']); ?></td>
                                <td><?= htmlspecialchars($row['nmaJabatan'] ?? '-'); ?></td>
                                <td>
                                    <span style="background: #10b981; color: white; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                        <?= htmlspecialchars($row['nmaLevel'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($row['tglGabung'])); ?> 
                                    <br><span style="color: #64748b; font-size: 12px;">(<?= $row['masa_kerja_bulan'] ?> Bulan Berjalan)</span>
                                </td>
                                <td>
                                    <button type="button" onclick="toggleEditRow('<?= $edit_row_id; ?>')" class="btn-action-edit">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="biodata.php?hapus=<?= $row['id_karyawan']; ?>" onclick="return confirm('Yakin ingin menghapus data <?= htmlspecialchars($row['nmaKaryawan']); ?>?');" class="btn-action-delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <tr id="<?= $edit_row_id; ?>" class="edit-row">
                                <td colspan="6">
                                    <div class="edit-form-container">
                                        <h4 style="margin-bottom:15px; color:#1e293b;"><i class="fa-solid fa-user-pen"></i> Update Karyawan: <?= htmlspecialchars($row['nmaKaryawan']); ?></h4>
                                        <form action="biodata.php" method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id_karyawan" value="<?= $row['id_karyawan']; ?>">
                                            
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label>Nama Lengkap</label>
                                                    <input type="text" name="nmaKaryawan" class="form-control" value="<?= htmlspecialchars($row['nmaKaryawan']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Status Pernikahan</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="Lajang" <?= $row['status'] == 'Lajang' ? 'selected' : ''; ?>>Lajang</option>
                                                        <option value="Menikah" <?= $row['status'] == 'Menikah' ? 'selected' : ''; ?>>Menikah</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Jumlah Anak</label>
                                                    <input type="number" name="jmlAnak" class="form-control" value="<?= htmlspecialchars($row['jmlAnak']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Tanggal Bergabung</label>
                                                    <input type="date" name="tglGabung" class="form-control" value="<?= htmlspecialchars($row['tglGabung']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Jabatan</label>
                                                    <select name="id_jabatan" class="form-control" required>
                                                        <option value="">-- Pilih Jabatan --</option>
                                                        <?php 
                                                        mysqli_data_seek($query_jabatan, 0);
                                                        while($j_edit = mysqli_fetch_assoc($query_jabatan)) { 
                                                            $sel = ($row['id_jabatan'] == $j_edit['id_jabatan']) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?= $j_edit['id_jabatan']; ?>" <?= $sel ?>><?= htmlspecialchars($j_edit['nmaJabatan']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Password Akun</label>
                                                    <input type="text" name="password" class="form-control" placeholder="Isi untuk mengubah password, kosongkan jika tetap">
                                                </div>
                                                <div class="form-group span-two">
                                                    <label>Alamat Rumah</label>
                                                    <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($row['alamat']); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="form-actions" style="margin-top:15px; text-align:right;">
                                                <button type="button" class="btn-cancel" onclick="toggleEditRow('<?= $edit_row_id; ?>');">Batal</button>
                                                <button type="submit" class="btn-submit" style="background:#22c55e;"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted' style='padding: 20px;'>Belum ada karyawan yang terdaftar.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-container" style="margin-bottom: 25px;">
                    <div class="form-title">
                        <h3><i class="fa-solid fa-layer-group"></i> Pengaturan Level Karyawan (Otomatis)</h3>
                        <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Tentukan rentang bulan masa kerja untuk kenaikan level otomatis.</p>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Level</th>
                                <th>Masa Kerja Minimal</th>
                                <th>Masa Kerja Maksimal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($lvl_rule = mysqli_fetch_assoc($query_level_rule)) { 
                                $id_rule_toggle = "rule-" . $lvl_rule['id_level'];
                            ?>
                            <tr>
                                <td class="text-bold" style="color: #0ea5e9;"><?= htmlspecialchars($lvl_rule['nmaLevel']) ?></td>
                                <td><?= $lvl_rule['minBulan'] ?> Bulan</td>
                                <td><?= $lvl_rule['maxBulan'] >= 999 ? 'Tak Terhingga' : $lvl_rule['maxBulan'] . ' Bulan' ?></td>
                                <td>
                                    <button type="button" onclick="toggleEditRow('<?= $id_rule_toggle ?>')" class="btn-action-edit" style="background: #eab308; color: #fff;">
                                        <i class="fa-solid fa-gear"></i> Atur
                                    </button>
                                </td>
                            </tr>
                            <tr id="<?= $id_rule_toggle ?>" class="edit-row">
                                <td colspan="4">
                                    <div class="edit-form-container" style="border-color: #eab308;">
                                        <form action="biodata.php" method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
                                            <input type="hidden" name="action" value="update_aturan_level">
                                            <input type="hidden" name="id_level" value="<?= $lvl_rule['id_level'] ?>">
                                            <div style="flex: 1;">
                                                <label style="font-size: 13px; font-weight: bold; margin-bottom: 5px; display: block;">Minimal Bulan</label>
                                                <input type="number" name="minBulan" class="form-control" value="<?= $lvl_rule['minBulan'] ?>" required>
                                            </div>
                                            <div style="flex: 1;">
                                                <label style="font-size: 13px; font-weight: bold; margin-bottom: 5px; display: block;">Maksimal Bulan (Isi 999 jika tak terhingga)</label>
                                                <input type="number" name="maxBulan" class="form-control" value="<?= $lvl_rule['maxBulan'] ?>" required>
                                            </div>
                                            <div>
                                                <button type="submit" class="btn-submit" style="margin: 0; padding: 10px 20px;"><i class="fa-solid fa-check"></i> Simpan Rule</button>
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
                    <div class="form-title">
                        <h3><i class="fa-solid fa-user-plus"></i> Tambah Karyawan Baru</h3>
                    </div>
                    
                    <form action="biodata.php" method="POST">
                        <input type="hidden" name="action" value="tambah">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nmaKaryawan" class="form-control" placeholder="Masukkan nama..." required>
                            </div>
                            <div class="form-group">
                                <label>Password Akun</label>
                                <input type="password" name="password" class="form-control" placeholder="Buat password login..." required>
                            </div>
                            <div class="form-group">
                                <label>Status Pernikahan</label>
                                <select name="status" class="form-control" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Lajang">Lajang</option>
                                    <option value="Menikah">Menikah</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jumlah Anak</label>
                                <input type="number" name="jmlAnak" class="form-control" placeholder="Contoh: 0" value="0" required>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Bergabung</label>
                                <input type="date" name="tglGabung" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Jabatan</label>
                                <select name="id_jabatan" class="form-control" required>
                                    <option value="">-- Pilih Jabatan --</option>
                                    <?php 
                                    mysqli_data_seek($query_jabatan, 0); 
                                    while($j = mysqli_fetch_assoc($query_jabatan)) { 
                                    ?>
                                        <option value="<?= $j['id_jabatan']; ?>"><?= htmlspecialchars($j['nmaJabatan']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group span-two">
                                <label>Alamat Rumah</label>
                                <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap..." required></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Data Karyawan
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Fungsi JS standar untuk Toggle Baris Edit (Menggunakan classList.toggle sesuai CSS terpisah)
        function toggleEditRow(rowId) {
            var editRow = document.getElementById(rowId);
            if(editRow) {
                editRow.classList.toggle('show');
            }
        }
    </script>
</body>
</html>