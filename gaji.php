<?php
ob_start(); // Mencegah error "Headers already sent" saat redirect

// 1. KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistempenggajian";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 2. PROSES INSERT DATA GAJI (Dari Form Tambah Baru)
if (isset($_POST['simpan_gaji'])) {
    $gapok        = (int)$_POST['gapok'];
    $potPajak     = (int)$_POST['potPajak'];
    $potKehadiran = (int)$_POST['potKehadiran'];
    $pinjaman     = (int)$_POST['pinjaman'];
    $id_tunjangan = mysqli_real_escape_string($koneksi, $_POST['id_tunjangan']);

    $query_insert = "INSERT INTO gaji (gapok, potPajak, potKehadiran, pinjaman, id_tunjangan) 
                     VALUES ('$gapok', '$potPajak', '$potKehadiran', '$pinjaman', '$id_tunjangan')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>
                alert('Data gaji berhasil disimpan ke database!');
                window.location.href='gaji.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// 3. PROSES UPDATE DATA GAJI (Dari Form Edit Inline)
if (isset($_POST['update_gaji'])) {
    $id_gaji      = mysqli_real_escape_string($koneksi, $_POST['id_gaji']);
    $gapok        = (int)$_POST['gapok'];
    $potPajak     = (int)$_POST['potPajak'];
    $potKehadiran = (int)$_POST['potKehadiran'];
    $pinjaman     = (int)$_POST['pinjaman'];
    $id_tunjangan = mysqli_real_escape_string($koneksi, $_POST['id_tunjangan']);

    $query_update = "UPDATE gaji SET 
                        gapok = '$gapok', 
                        potPajak = '$potPajak', 
                        potKehadiran = '$potKehadiran', 
                        pinjaman = '$pinjaman', 
                        id_tunjangan = '$id_tunjangan' 
                     WHERE id_gaji = '$id_gaji'";

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>
                alert('Data gaji berhasil diupdate!');
                window.location.href='gaji.php';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal mengupdate data: " . mysqli_error($koneksi) . "');</script>";
    }
}

// AMBIL DATA TUNJANGAN UNTUK DROPDOWN
$list_tunjangan = [];
$query_tunjangan = mysqli_query($koneksi, "SELECT id_tunjangan FROM tunjangan ORDER BY id_tunjangan ASC");
while($t = mysqli_fetch_assoc($query_tunjangan)) {
    $list_tunjangan[] = $t['id_tunjangan'];
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan - Gaji</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inherit">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
        <script src="assets/script.js" defer></script>
    </head>

    <body>
        <div class="dashboard-container">
            
            <aside class="sidebar">
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
                    <a href="biodata.php" class="nav-item">
                        <i class="fa-solid fa-id-card"></i> Data Karyawan
                    </a>
                    <a href="gaji.php" class="nav-item active"> 
                        <i class="fa-solid fa-calendar-days"></i> Gaji
                    </a>
                    <a href="penjualan.php" class="nav-item">
                        <i class="fa-solid fa-chart-line"></i> Penjualan
                    </a>
                </nav>
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
                            date_default_timezone_set('Asia/Jakarta'); 
                            $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                            $bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                            
                            echo $hari[date('w')] . ", " . date('j') . " " . $bulan[date('n')] . " " . date('Y'); 
                            ?>
                        </span>
                    </div>
                </header>

                <div class="content-body">
                    <div class="content-header">
                        <h3>Manajemen Data Gaji Karyawan</h3>
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
                                $query = "SELECT g.*, t.* FROM gaji g LEFT JOIN tunjangan t ON g.id_tunjangan = t.id_tunjangan ORDER BY g.id_gaji DESC";
                                $result = mysqli_query($koneksi, $query);
                                $no = 1;

                                if ($result && mysqli_num_rows($result) > 0) {
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $gapok = (int)$row['gapok'];
                                        
                                        $total_tunjangan = (int)$row['makan'] + (int)$row['transport'] + (int)$row['uangLembur'] + 
                                                           (int)$row['insentifPenjualan'] + (int)$row['tunJabatan'] + 
                                                           (int)$row['kompensasi'] + (int)$row['THR'] + (int)$row['BPJS'] + (int)$row['BAT'];
                                        
                                        $total_potongan = (int)$row['potPajak'] + (int)$row['potKehadiran'] + (int)$row['pinjaman'];
                                        $gaji_bersih = ($gapok + $total_tunjangan) - $total_potongan;
                                        
                                        $edit_id = "edit-" . $row['id_gaji'];
                                        ?>
                                        
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class='text-bold'><?= htmlspecialchars($row['id_gaji']) ?></td>
                                            <td>Rp <?= number_format($gapok, 0, ',', '.') ?></td>
                                            <td><span class='val-tunjangan'>Rp <?= number_format($total_tunjangan, 0, ',', '.') ?></span></td>
                                            <td><span class='val-potongan'>Rp <?= number_format($total_potongan, 0, ',', '.') ?></span></td>
                                            <td class='text-company-name'>Rp <?= number_format($gaji_bersih, 0, ',', '.') ?></td>
                                            <td>
                                                <a href="#" class="btn-action-edit btn-action-update" onclick="toggleEditRow('<?= $edit_id ?>'); return false;">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </a>
                                            </td>
                                        </tr>

                                        <tr id="<?= $edit_id ?>" class="detail-row">
                                            <td colspan="7">
                                                <div class="form-container">
                                                    <div class="form-title">
                                                        <i class="fa-solid fa-pen-to-square icon-edit-mode"></i> Edit Data Gaji (ID: <?= htmlspecialchars($row['id_gaji']); ?>)
                                                    </div>
                                                    
                                                    <form action="gaji.php" method="POST">
                                                        <input type="hidden" name="id_gaji" value="<?= htmlspecialchars($row['id_gaji']); ?>">

                                                        <div class="form-grid">
                                                            <div class="form-group">
                                                                <label>Gaji Pokok (Rp)</label>
                                                                <input type="number" name="gapok" class="form-control" value="<?= $gapok; ?>" required>
                                                            </div>

                                                            <div class="form-group">
                                                                <label>ID Tunjangan (Relasi)</label>
                                                                <select name="id_tunjangan" class="form-control" required>
                                                                    <option value="">-- Pilih Data Tunjangan --</option>
                                                                    <?php
                                                                    foreach($list_tunjangan as $t_id) {
                                                                        $selected = ($t_id == $row['id_tunjangan']) ? "selected" : "";
                                                                        echo "<option value='".$t_id."' $selected>Tunjangan ID: ".$t_id."</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>

                                                            <div class="form-group">
                                                                <label>Potongan Pajak (Rp)</label>
                                                                <input type="number" name="potPajak" class="form-control" value="<?= (int)$row['potPajak']; ?>">
                                                            </div>

                                                            <div class="form-group">
                                                                <label>Potongan Kehadiran (Rp)</label>
                                                                <input type="number" name="potKehadiran" class="form-control" value="<?= (int)$row['potKehadiran']; ?>">
                                                            </div>

                                                            <div class="form-group span-two">
                                                                <label>Potongan Pinjaman/Kasbon (Rp)</label>
                                                                <input type="number" name="pinjaman" class="form-control" value="<?= (int)$row['pinjaman']; ?>">
                                                            </div>
                                                        </div>

                                                        <div class="form-actions">
                                                            <button type="button" class="btn-cancel" onclick="toggleEditRow('<?= $edit_id ?>')">
                                                                <i class="fa-solid fa-xmark"></i> Tutup
                                                            </button>
                                                            <button type="submit" name="update_gaji" class="btn-submit btn-update">
                                                                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr>
                                            <td colspan='7' class='text-center text-muted cell-empty-state'>
                                                <i class='fa-solid fa-folder-open icon-empty-folder'></i>
                                                Belum ada data gaji yang tersedia di database.
                                            </td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-container">
                        <div class="form-title">
                            <i class="fa-solid fa-file-invoice-dollar icon-insert-mode"></i> Input Gaji Baru
                        </div>
                        
                        <form action="gaji.php" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="gapok">Gaji Pokok (Rp)</label>
                                    <input type="number" name="gapok" id="gapok" class="form-control" placeholder="Contoh: 3500000" required>
                                </div>

                                <div class="form-group">
                                    <label for="id_tunjangan">ID Tunjangan (Relasi)</label>
                                    <select name="id_tunjangan" id="id_tunjangan" class="form-control" required>
                                        <option value="">-- Pilih Data Tunjangan --</option>
                                        <?php
                                        foreach($list_tunjangan as $t_id) {
                                            echo "<option value='".$t_id."'>Tunjangan ID: ".$t_id."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="potPajak">Potongan Pajak (Rp)</label>
                                    <input type="number" name="potPajak" id="potPajak" class="form-control" placeholder="0" value="0">
                                </div>

                                <div class="form-group">
                                    <label for="potKehadiran">Potongan Kehadiran (Rp)</label>
                                    <input type="number" name="potKehadiran" id="potKehadiran" class="form-control" placeholder="0" value="0">
                                </div>

                                <div class="form-group span-two">
                                    <label for="pinjaman">Potongan Pinjaman/Kasbon (Rp)</label>
                                    <input type="number" name="pinjaman" id="pinjaman" class="form-control" placeholder="0" value="0">
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="reset" class="btn-cancel">
                                    <i class="fa-solid fa-rotate-left"></i> Reset
                                </button>
                                <button type="submit" name="simpan_gaji" class="btn-submit">
                                    <i class="fa-solid fa-floppy-disk"></i> Simpan Data Gaji
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

            </main>
        </div>

        <script>
            function toggleEditRow(rowId) {
                var editRow = document.getElementById(rowId);
                if (editRow) {
                    editRow.classList.toggle('show');
                }
            }
        </script>
    </body>
</html>
<?php ob_end_flush(); ?>