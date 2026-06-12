<?php
session_start(); 

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
// 2. PROSES ACTION: TAMBAH DATA KARYAWAN (Dari Form Utama)
// =========================================================================
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nmaKaryawan   = mysqli_real_escape_string($koneksi, $_POST['nmaKaryawan']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $password_user = mysqli_real_escape_string($koneksi, $_POST['password']);
    $status        = mysqli_real_escape_string($koneksi, $_POST['status']); 
    $tglGabung     = mysqli_real_escape_string($koneksi, $_POST['tglGabung']);
    $jmlAnak       = intval($_POST['jmlAnak']);
    $id_level      = intval($_POST['id_level']);
    $id_jabatan    = intval($_POST['id_jabatan']);
    
    $id_perusahaan = $_SESSION['id_perusahaan'] ?? 1; // Fallback ke 1 jika session kosong
    $password_hash = password_hash($password_user, PASSWORD_DEFAULT);

    $query_insert = "INSERT INTO userkaryawan (nmaKaryawan, alamat, password, status, tglGabung, jmlAnak, id_perusahaan, id_level, id_jabatan) 
                     VALUES ('$nmaKaryawan', '$alamat', '$password_hash', '$status', '$tglGabung', '$jmlAnak', '$id_perusahaan', '$id_level', '$id_jabatan')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>alert('Data karyawan berhasil ditambahkan!'); window.location='biodata.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menambahkan data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 3. PROSES ACTION: UPDATE / EDIT DATA KARYAWAN (Dari Form Inline)
// =========================================================================
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id_karyawan   = intval($_POST['id_karyawan']);
    $nmaKaryawan   = mysqli_real_escape_string($koneksi, $_POST['nmaKaryawan']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $status        = mysqli_real_escape_string($koneksi, $_POST['status']); 
    $tglGabung     = mysqli_real_escape_string($koneksi, $_POST['tglGabung']);
    $jmlAnak       = intval($_POST['jmlAnak']);
    $id_level      = intval($_POST['id_level']);
    $id_jabatan    = intval($_POST['id_jabatan']);

    $password_query = "";
    if (!empty($_POST['password'])) {
        $password_user  = mysqli_real_escape_string($koneksi, $_POST['password']);
        $password_hash  = password_hash($password_user, PASSWORD_DEFAULT);
        $password_query = ", password='$password_hash'";
    }

    $query_update = "UPDATE userkaryawan SET 
                        nmaKaryawan='$nmaKaryawan', 
                        alamat='$alamat', 
                        status='$status', 
                        tglGabung='$tglGabung', 
                        jmlAnak='$jmlAnak', 
                        id_level='$id_level', 
                        id_jabatan='$id_jabatan'
                        $password_query
                     WHERE id_karyawan='$id_karyawan'";

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Data karyawan berhasil diperbarui!'); window.location='biodata.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 4. QUERY MASTER DATA DROPDOWN & TABEL
// =========================================================================
$res_jabatan = mysqli_query($koneksi, "SELECT * FROM jabatan");
$res_level   = mysqli_query($koneksi, "SELECT * FROM levelkaryawan");

$query_table = "SELECT 
                    u.id_karyawan, 
                    u.nmaKaryawan, 
                    u.alamat, 
                    u.status, 
                    u.tglGabung, 
                    u.jmlAnak,
                    u.id_jabatan,
                    u.id_level,
                    l.nmaLevel, 
                    j.nmaJabatan 
                FROM userkaryawan u
                LEFT JOIN levelkaryawan l ON u.id_level = l.id_level
                LEFT JOIN jabatan j ON u.id_jabatan = j.id_jabatan";
$result_table = mysqli_query($koneksi, $query_table);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan - Biodata Karyawan</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inherit">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            
            <aside class="sidebar active">
                <a href="index.php" class="brand">
                    <img src="assets/logoputih.svg" class="logo" alt="logo">
                </a>
                <nav class="nav-menu">
                    <a href="dashboardperusahaan.php" class="nav-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                    <a href="presensi1.php" class="nav-item"><i class="fa-solid fa-square-check"></i> Presensi</a>
                    <a href="biodata.php" class="nav-item active"><i class="fa-solid fa-id-card"></i> Data Karyawan</a>
                    <a href="gaji.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Gaji</a>
                    <a href="penjualan.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> Penjualan</a>
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
                        <div class="search-wrapper">
                            <input type="text" class="search-input" placeholder="Cari...">
                            <i class="fa-solid fa-magnifying-glass icon-btn search-toggle"></i>
                        </div>
                        <span class="user-name">
                            <?php 
                            date_default_timezone_set('Asia/Jakarta'); 
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
                            <h3> Daftar Karyawan </h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Karyawan</th>
                                    <th>Alamat</th>
                                    <th>Status</th>
                                    <th>Tanggal Gabung</th>
                                    <th>Jumlah Anak</th>
                                    <th>Level</th>
                                    <th>Jabatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($result_table && mysqli_num_rows($result_table) > 0) {
                                    while($row = mysqli_fetch_assoc($result_table)) { 
                                        $edit_row_id = "edit-row-krw-" . $no;
                                ?>
                                        <tr>
                                            <td><?= $no; ?></td>
                                            <td class="text-bold"><?= htmlspecialchars($row['nmaKaryawan']); ?></td>
                                            <td><?= htmlspecialchars($row['alamat']); ?></td>
                                            <td>
                                                <?php 
                                                if (strtolower($row['status']) == 'menikah') {
                                                    echo '<span class="badge-menikah" style="background:#ebf8ff; color:#2b6cb0; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;">Menikah</span>';
                                                } elseif (strtolower($row['status']) == 'belum menikah') {
                                                    echo '<span class="badge-belum" style="background:#f7fafc; color:#4a5568; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; border:1px solid #edf2f7;">Belum Menikah</span>';
                                                } else {
                                                    echo '<span class="text-muted">' . htmlspecialchars($row['status']) . '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= date('d-m-Y', strtotime($row['tglGabung'])); ?></td>
                                            <td><?= htmlspecialchars($row['jmlAnak']); ?></td>
                                            <td><span class="badge-level"><?= htmlspecialchars($row['nmaLevel']); ?></span></td>
                                            <td><?= htmlspecialchars($row['nmaJabatan']); ?></td>
                                            <td style="text-align: center;">
                                                <a href="#" class="btn-action-edit" onclick="toggleEditForm('<?= $edit_row_id ?>'); return false;">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </a>
                                            </td>
                                        </tr>

                                        <tr id="<?= $edit_row_id ?>" class="detail-row">
                                            <td colspan="9" style="padding: 15px;">
                                                <div class="form-container" style="box-shadow: none; border: 1px solid #e2e8f0; margin: 0; text-align: left;">
                                                    <h4 class="form-title" style="font-size: 14px; margin-bottom: 15px;">
                                                        <i class="fa-solid fa-user-pen"></i> Edit Data: <strong><?= htmlspecialchars($row['nmaKaryawan']); ?></strong>
                                                    </h4>
                                                    
                                                    <form action="biodata.php" method="POST">
                                                        <input type="hidden" name="action" value="update">
                                                        <input type="hidden" name="id_karyawan" value="<?= $row['id_karyawan']; ?>">

                                                        <div class="form-grid">
                                                            <div class="form-group">
                                                                <label>Nama Karyawan</label>
                                                                <input type="text" name="nmaKaryawan" class="form-control" value="<?= htmlspecialchars($row['nmaKaryawan']); ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Password Akun</label>
                                                                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Tanggal Gabung</label>
                                                                <input type="date" name="tglGabung" class="form-control" value="<?= $row['tglGabung']; ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Jumlah Anak</label>
                                                                <input type="number" name="jmlAnak" class="form-control" min="0" value="<?= $row['jmlAnak']; ?>" required>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label>Status Pernikahan</label>
                                                                <select name="status" class="form-control" required>
                                                                    <option value="Unset">-- Pilih Status --</option>
                                                                    <option value="Menikah" <?= (strtolower($row['status']) == 'menikah') ? 'selected' : ''; ?>>Menikah</option>
                                                                    <option value="Belum Menikah" <?= (strtolower($row['status']) == 'belum menikah') ? 'selected' : ''; ?>>Belum Menikah</option>
                                                                </select>
                                                            </div>

                                                            <div class="form-group">
                                                                <label>Jabatan</label>
                                                                <select name="id_jabatan" class="form-control" required>
                                                                    <option value="">-- Pilih Jabatan --</option>
                                                                    <?php 
                                                                    mysqli_data_seek($res_jabatan, 0); 
                                                                    while($j = mysqli_fetch_assoc($res_jabatan)) { 
                                                                        $selected = ($j['id_jabatan'] == $row['id_jabatan']) ? 'selected' : '';
                                                                    ?>
                                                                        <option value="<?= $j['id_jabatan']; ?>" <?= $selected; ?>><?= htmlspecialchars($j['nmaJabatan']); ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>

                                                            <div class="form-group">
                                                                <label>Level Karyawan</label>
                                                                <select name="id_level" class="form-control" required>
                                                                    <option value="">-- Pilih Level --</option>
                                                                    <?php 
                                                                    mysqli_data_seek($res_level, 0); 
                                                                    while($l = mysqli_fetch_assoc($res_level)) { 
                                                                        $selected = ($l['id_level'] == $row['id_level']) ? 'selected' : '';
                                                                    ?>
                                                                        <option value="<?= $l['id_level']; ?>" <?= $selected; ?>><?= htmlspecialchars($l['nmaLevel']); ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>

                                                            <div class="form-group span-two">
                                                                <label>Alamat Rumah</label>
                                                                <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($row['alamat']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-actions" style="margin-top: 15px;">
                                                            <button type="button" class="btn-cancel" onclick="toggleEditForm('<?= $edit_row_id ?>')">
                                                                <i class="fa-solid fa-xmark"></i> Tutup
                                                            </button>
                                                            <button type="submit" class="btn-submit btn-update">
                                                                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                <?php 
                                        $no++;
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center'>Belum ada data karyawan.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-container" id="form-karyawan">
                        <div class="card-header-title">
                            <h3> Tambah Karyawan Baru </h3>
                        </div>
                        <form action="biodata.php" method="POST" id="formKaryawan">
                            <input type="hidden" name="action" value="tambah">

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nama Karyawan</label>
                                    <input type="text" name="nmaKaryawan" class="form-control" placeholder="Masukkan nama lengkap" required>
                                </div>
                                <div class="form-group">
                                    <label>Password Akun</label>
                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password log in" required>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Gabung</label>
                                    <input type="date" name="tglGabung" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Anak</label>
                                    <input type="number" name="jmlAnak" class="form-control" min="0" value="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Status Pernikahan</label>
                                    <select name="status" class="form-control" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Menikah">Menikah</option>
                                        <option value="Belum Menikah">Belum Menikah</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Jabatan</label>
                                    <select name="id_jabatan" class="form-control" required>
                                        <option value="">-- Pilih Jabatan --</option>
                                        <?php 
                                        mysqli_data_seek($res_jabatan, 0); 
                                        while($j = mysqli_fetch_assoc($res_jabatan)) { 
                                        ?>
                                            <option value="<?= $j['id_jabatan']; ?>"><?= htmlspecialchars($j['nmaJabatan']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Level Karyawan</label>
                                    <select name="id_level" class="form-control" required>
                                        <option value="">-- Pilih Level --</option>
                                        <?php 
                                        mysqli_data_seek($res_level, 0); 
                                        while($l = mysqli_fetch_assoc($res_level)) { 
                                        ?>
                                            <option value="<?= $l['id_level']; ?>"><?= htmlspecialchars($l['nmaLevel']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group span-two">
                                    <label>Alamat Rumah</label>
                                    <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap..." required></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn-submit" onclick="document.getElementById('formKaryawan').submit();">
                                    <i class="fa-solid fa-floppy-disk"></i> Simpan Data Karyawan
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </main>
        </div>

        <script>
            function toggleEditForm(rowId) {
                var editRow = document.getElementById(rowId);
                if(editRow) {
                    editRow.classList.toggle('show');
                }
            }
        </script>
    </body>
</html>