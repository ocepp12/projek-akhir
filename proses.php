<?php
include 'koneksi.php';

// 1. SET WAKTU SERVER (BIAR JUJUR)
date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date("Y-m-d H:i:s");

// 2. TANGKAP KOORDINAT DARI JAVASCRIPT
$lat_user = $_POST['latitude'];
$lng_user = $_POST['longitude'];

// ID Karyawan (Simulasi login: kita anggap ID Budi yang tadi kita input)
$id_karyawan = 1; 

// 3. AMBIL DATA KANTOR DARI DATABASE
$query_kantor = mysqli_query($kon, "SELECT * FROM kantor LIMIT 1");
$data_kantor  = mysqli_fetch_assoc($query_kantor);

$lat_kantor = $data_kantor['latitude_kantor'];
$lng_kantor = $data_kantor['longitude_kantor'];
$radius     = $data_kantor['radius_meter'];

// 4. RUMUS HAVERSINE (MENGHITUNG JARAK 2 TITIK KOORDINAT)
function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return ($miles * 1.609344) * 1000; // Hasil dalam Satuan METER
}

$jarak = hitungJarak($lat_user, $lng_user, $lat_kantor, $lng_kantor);

// 5. VALIDASI & SIMPAN KE DATABASE
if ($jarak <= $radius) {
    $status = "Hadir";
    $pesan  = "✅ Berhasil! Anda berada di area kantor. Jarak: " . round($jarak) . " meter.";
} else {
    $status = "Luar Area";
    $pesan  = "❌ Gagal! Anda terlalu jauh dari kantor. Jarak: " . round($jarak) . " meter.";
}

$simpan = mysqli_query($kon, "INSERT INTO absensi (karyawan_id, waktu_absen, lat_user, lng_user, jarak_meter, status) 
          VALUES ('$id_karyawan', '$waktu_sekarang', '$lat_user', '$lng_user', '$jarak', '$status')");

if ($simpan) {
    echo $pesan;
} else {
    echo "Terjadi kesalahan database.";
}
?>