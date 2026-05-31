<?php
// Wajib di baris paling atas sebelum ada output HTML/echo apa pun
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
// 2. INISIALISASI VARIABEL AWAL (Mencegah Error Undefined Array Key)
// =========================================================================
$is_edit = false;
$edit_data = [
    'id_karyawan' => '',
    'nmaKaryawan' => '', 
    'password'    => '',
    'tglGabung'   => '',
    'jmlAnak'     => 0,
    'status'      => '', 
    'id_jabatan'  => '',
    'id_level'    => '',
    'alamat'      => ''
];

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
    $id_level      = intval($_POST['id_level']);
    $id_jabatan    = intval($_POST['id_jabatan']);
    
    $id_perusahaan = $_SESSION['id_perusahaan'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query_insert = "INSERT INTO userkaryawan (nmaKaryawan, alamat, password, status, tglGabung, jmlAnak, id_perusahaan, id_level, id_jabatan) 
                     VALUES ('$nmaKaryawan', '$alamat', '$password_user', '$status', '$tglGabung', '$jmlAnak', '$id_perusahaan', '$id_level', '$id_jabatan')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>alert('Data karyawan berhasil ditambahkan!'); window.location='biodata.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 4. PROSES ACTION: UPDATE / EDIT DATA KARYAWAN
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
        $password_query = ", password='$password_user'";
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
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// =========================================================================
// 5. CEK REQUEST EDIT (Mengambil Data Lama untuk Form)
// =========================================================================
if (isset($_GET['id_edit'])) {
    $id_edit = intval($_GET['id_edit']);
    $res_edit = mysqli_query($koneksi, "SELECT * FROM userkaryawan WHERE id_karyawan = $id_edit");
    if ($res_edit && mysqli_num_rows($res_edit) > 0) {
        $is_edit = true;
        $edit_data = mysqli_fetch_assoc($res_edit);
    }
}

// =========================================================================
// 6. QUERY MASTER DATA (Dropdown & Tabel Utama)
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
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            
            <aside class="sidebar active">
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
                    <a href="biodata.php" class="nav-item active">
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

            <main class="main-content sidebar-active">
                
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
                    
                    <div class="table-container">
                        <h4 class="form-title"><i class="fa-solid fa-users"></i> Daftar Karyawan Aktif</h4>
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
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if (mysqli_num_rows($result_table) > 0) {
                                    while($row = mysqli_fetch_assoc($result_table)) { 
                                ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td class="text-bold"><?= htmlspecialchars($row['nmaKaryawan']); ?></td>
                                            <td><?= htmlspecialchars($row['alamat']); ?></td>
                                            <td>
                                                <?php 
                                                if (strtolower($row['status']) == 'menikah') {
                                                    echo '<span class="badge-menikah" style="background:#ebf8ff; color:#2b6cb0; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600;">Menikah</span>';
                                                } elseif (strtolower($row['status']) == 'belum menikah') {
                                                    echo '<span class="badge-belum" style="background:#f7fafc; color:#4a5568; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600; border:1px solid #edf2f7;">Belum Menikah</span>';
                                                } else {
                                                    echo '<span class="text-muted">Belum diset</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?= date('d-m-Y', strtotime($row['tglGabung'])); ?></td>
                                            <td><?= htmlspecialchars($row['jmlAnak']); ?></td>
                                            <td><span class="badge-level"><?= htmlspecialchars($row['nmaLevel']); ?></span></td>
                                            <td><?= htmlspecialchars($row['nmaJabatan']); ?></td>
                                            <td style="text-align: center;">
                                                <a href="biodata.php?id_edit=<?= $row['id_karyawan']; ?>#form-karyawan" class="btn-action-edit">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center'>Data tidak ditemukan atau kosong.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-container" id="form-karyawan">
                        <h4 class="form-title">
                            <i class="fa-solid <?= $is_edit ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i> 
                            <?= $is_edit ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru'; ?>
                        </h4>
                        
                        <form action="" method="POST" id="formKaryawan">
                            
                            <?php if ($is_edit) { ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id_karyawan" value="<?= $edit_data['id_karyawan']; ?>">
                            <?php } else { ?>
                                <input type="hidden" name="action" value="tambah">
                            <?php } ?>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nama Karyawan</label>
                                    <input type="text" name="nmaKaryawan" class="form-control" placeholder="Masukkan nama lengkap" 
                                           value="<?= htmlspecialchars($edit_data['nmaKaryawan']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Password Akun</label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="<?= $is_edit ? 'Kosongkan jika tidak ingin mengubah password' : 'Masukkan password log in'; ?>" 
                                           <?= $is_edit ? '' : 'required'; ?>>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Gabung</label>
                                    <input type="date" name="tglGabung" class="form-control" 
                                           value="<?= $edit_data['tglGabung']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Anak</label>
                                    <input type="number" name="jmlAnak" class="form-control" min="0" 
                                           value="<?= $edit_data['jmlAnak']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Status Pernikahan</label>
                                    <select name="status" class="form-control" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Menikah" <?= (strtolower($edit_data['status']) == 'menikah') ? 'selected' : ''; ?>>Menikah</option>
                                        <option value="Belum Menikah" <?= (strtolower($edit_data['status']) == 'belum menikah') ? 'selected' : ''; ?>>Belum Menikah</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Jabatan</label>
                                    <select name="id_jabatan" class="form-control" required>
                                        <option value="">-- Pilih Jabatan --</option>
                                        <?php 
                                        mysqli_data_seek($res_jabatan, 0); 
                                        while($j = mysqli_fetch_assoc($res_jabatan)) { 
                                            $selected = ($is_edit && $j['id_jabatan'] == $edit_data['id_jabatan']) ? 'selected' : '';
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
                                            $selected = ($is_edit && $l['id_level'] == $edit_data['id_level']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $l['id_level']; ?>" <?= $selected; ?>><?= htmlspecialchars($l['nmaLevel']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group span-two">
                                    <label>Alamat Rumah</label>
                                    <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap..." required><?= htmlspecialchars($edit_data['alamat']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <?php if ($is_edit) { ?>
                                    <a href="biodata.php" class="btn-cancel">
                                        <i class="fa-solid fa-xmark"></i> Batal
                                    </a>
                                    <button type="button" class="btn-submit btn-update" onclick="document.getElementById('formKaryawan').submit();">
                                        <i class="fa-solid fa-pen-to-square"></i> Perbarui Data Karyawan
                                    </button>
                                <?php } else { ?>
                                    <button type="button" class="btn-submit" onclick="document.getElementById('formKaryawan').submit();">
                                        <i class="fa-solid fa-floppy-disk"></i> Simpan Data Karyawan
                                    </button>
                                <?php } ?>
                            </div>
                        </form>
                    </div>

                </div>
            </main>

        </div>
    </body>
</html>