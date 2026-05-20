<?php
include 'koneksi.php';
$query = mysqli_query($kon, "SELECT a.*, k.nama_lengkap 
                             FROM absensi a 
                             JOIN karyawan k ON a.karyawan_id = k.id_karyawan 
                             ORDER BY a.waktu_absen DESC");
?>

<h2>Data Rekap Absensi</h2>
<table border="1" cellpadding="10">
    <tr>
        <th>Nama</th>
        <th>Waktu</th>
        <th>Jarak (Meter)</th>
        <th>Status</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($query)) : ?>
    <tr>
        <td><?= $row['nama_lengkap']; ?></td>
        <td><?= $row['waktu_absen']; ?></td>
        <td><?= round($row['jarak_meter']); ?> m</td>
        <td style="color: <?= ($row['status'] == 'Hadir') ? 'green' : 'red'; ?>">
            <?= $row['status']; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>