<?php
// 1. KONEKSI DATABASE
$host     = "localhost";
$username = "root";
$password = "";
$database = "sistempenggajian";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 2. PROSES INPUT DATA (Ketika tombol Simpan ditekan)
if (isset($_POST['submit_karyawan'])) {
    $nmakaryawan   = mysqli_real_escape_string($koneksi, $_POST['nmakaryawan']);
    $alamat        = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $password_user = mysqli_real_escape_string($koneksi, $_POST['password']);
    $status        = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tglGabung     = mysqli_real_escape_string($koneksi, $_POST['tglGabung']);
    $jmlAnak       = intval($_POST['jmlAnak']);
    $id_level      = intval($_POST['id_level']);
    $id_jabatan    = intval($_POST['id_jabatan']);
    $id_perusahaan = 25; // Default value sesuai data di screenshot phpMyAdmin kamu

    // Query Insert ke tabel userkaryawan
    $query_insert = "INSERT INTO userkaryawan (nmakaryawan, alamat, password, status, tglGabung, jmlAnak, id_perusahaan, id_level, id_jabatan) 
                     VALUES ('$nmakaryawan', '$alamat', '$password_user', '$status', '$tglGabung', '$jmlAnak', '$id_perusahaan', '$id_level', '$id_jabatan')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>alert('Data karyawan berhasil ditambahkan!'); window.location='biodata.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// 3. AMBIL DATA UNTUK OPTION JABATAN & LEVEL
$res_jabatan = mysqli_query($koneksi, "SELECT * FROM jabatan");
$res_level   = mysqli_query($koneksi, "SELECT * FROM levelkaryawan");

// 4. QUERY AMBIL DATA KARYAWAN UNTUK TABEL (LEFT JOIN)
$query_table = "SELECT 
                    u.nmakaryawan, 
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
        <title>Dashboard Perusahaan - Biodata</title>
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
                        <i class="fa-solid fa-id-card"></i> Biodata
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
                        <span class="user-name">Budi S.</span>
                    </div>
                </header>

                <div class="content-body">
                    <div class="content-header">
                        <h3>Kelola Data Karyawan</h3>
                    </div>

                    <div class="form-container">
                        <h4 class="form-title"><i class="fa-solid fa-user-plus"></i> Tambah Karyawan Baru</h4>
                        <form action="biodata.php" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nama Karyawan</label>
                                    <input type="text" name="nmakaryawan" class="form-control" placeholder="Masukkan nama lengkap" required>
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
                                    <label>Status Kerja</label>
                                    <input type="text" name="status" class="form-control" placeholder="Contoh: Tetap / Kontrak">
                                </div>
                                <div class="form-group">
                                    <label>Jabatan</label>
                                    <select name="id_jabatan" class="form-control" required>
                                        <option value="">-- Pilih Jabatan --</option>
                                        <?php while($j = mysqli_fetch_assoc($res_jabatan)) { ?>
                                            <option value="<?= $j['id_jabatan']; ?>"><?= htmlspecialchars($j['nmaJabatan']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Level Karyawan</label>
                                    <select name="id_level" class="form-control" required>
                                        <option value="">-- Pilih Level --</option>
                                        <?php while($l = mysqli_fetch_assoc($res_level)) { ?>
                                            <option value="<?= $l['id_level']; ?>"><?= htmlspecialchars($l['nmaLevel']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group span-two">
                                    <label>Alamat Rumah</label>
                                    <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap karyawan..." required></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="submit_karyawan" class="btn-submit">
                                    <i class="fa-solid fa-floppy-disk"></i> Simpan Data Karyawan
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="table-container">
                        <h4 class="form-title" style="margin-bottom: 15px;"><i class="fa-solid fa-users"></i> Daftar Karyawan Aktif</h4>
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
                                            <td class="text-bold"><?= htmlspecialchars($row['nmakaryawan']); ?></td>
                                            <td><?= htmlspecialchars($row['alamat']); ?></td>
                                            <td>
                                                <?= $row['status'] ? htmlspecialchars($row['status']) : '<span class="text-muted">Belum diset</span>'; ?>
                                            </td>
                                            <td><?= date('d-m-Y', strtotime($row['tglGabung'])); ?></td>
                                            <td><?= htmlspecialchars($row['jmlAnak']); ?></td>
                                            <td><span class="badge-level"><?= htmlspecialchars($row['nmaLevel']); ?></span></td>
                                            <td><?= htmlspecialchars($row['nmaJabatan']); ?></td>
                                        </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>Data tidak ditemukan atau kosong.</td></tr>";
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