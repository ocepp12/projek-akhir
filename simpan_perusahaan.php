<?php
// Tampilkan error jika ada masalah lain agar tidak ngeblank
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Koneksi ke database
$host = 'localhost';
$user = 'root';
$pass = "";
$db   = 'sistempenggajian';
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi berhasil atau tidak
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Mulai sesi
session_start();
$errors = [];

// Ambil data dan sanitasi dari form
if (isset($_POST['submit_perusahaan'])){
    $nmaPerusahaan = mysqli_real_escape_string($conn, $_POST['nama_perusahaan']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $noWA = mysqli_real_escape_string($conn, $_POST['no_wa']);
    $lattitude = mysqli_real_escape_string($conn, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['longitude']);
    $radius = mysqli_real_escape_string($conn, $_POST['radius']);
    
    // Validasi Input
    if (empty($nmaPerusahaan) || empty($alamat) || empty($noWA) || empty($lattitude) || empty($longitude) || empty($radius)) {
        $errors[] = "Semua kolom wajib diisi!";
    }
    if (!ctype_digit($noWA)) {
        $errors[] = "No WA harus berupa angka saja!";
    }
    if ($lattitude < -90 || $lattitude > 90) {
        $errors[] = "Latitude tidak valid! Harus di antara -90 sampai 90!";
    }
    if ($longitude < -180 || $longitude > 180) {
        $errors[] = "Longitude tidak valid! Harus di antara -180 sampai 180!";
    }
    if ($radius <= 0 || $radius >= 50000) {
        $errors[] = "Radius tidak valid! Harus di antara 0 sampai 50.000!";
    } 
    
    // Jika tidak ada error validasi, lanjut cek ke database
    if (count($errors) === 0) {
        // 1. Pastikan session ID perusahaan ada (user sudah login)
        if (!isset($_SESSION['id_perusahaan'])) {
            $errors[] = "Sesi Anda telah habis, silakan login kembali.";
        } else {
            $id_perusahaan = $_SESSION['id_perusahaan'];

            // -----------------------------------------------------------------
            // TAMBAHAN: Cek dulu apakah perusahaan ini sudah punya id_lokasi di database
            // -----------------------------------------------------------------
            $cek_query = mysqli_query($conn, "SELECT id_lokasi FROM perusahaan WHERE id_perusahaan = '$id_perusahaan'");
            $data_pt = mysqli_fetch_assoc($cek_query);
            $id_lokasi_exist = (!empty($data_pt['id_lokasi'])) ? $data_pt['id_lokasi'] : null;

            if ($id_lokasi_exist) {
                // --- JIKA SUDAH ADA LOKASI: JALANKAN UPDATE LOKASI ---
                $id_lokasi = $id_lokasi_exist; // Simpan id untuk tahap update perusahaan nanti
                $sql_lokasi = "UPDATE lokasi SET latitude = ?, longitude = ?, radius = ? WHERE id_lokasi = ?";
                $stmt_lokasi = mysqli_prepare($conn, $sql_lokasi);
                
                if ($stmt_lokasi) {
                    mysqli_stmt_bind_param($stmt_lokasi, "ssii", $lattitude, $longitude, $radius, $id_lokasi);
                    if (!mysqli_stmt_execute($stmt_lokasi)) {
                        $errors[] = "Gagal memperbarui data lokasi: " . mysqli_stmt_error($stmt_lokasi);
                        unset($id_lokasi); // Batalkan jika gagal eksekusi
                    }
                    mysqli_stmt_close($stmt_lokasi);
                } else {
                    $errors[] = "Gagal menyiapkan query update lokasi: " . mysqli_error($conn);
                }

            } else {
                // --- JIKA BELUM ADA LOKASI: JALANKAN INSERT LOKASI ASLI LU ---
                $sql_lokasi = "INSERT INTO lokasi (latitude, longitude, radius) VALUES (?, ?, ?)";
                $stmt_lokasi = mysqli_prepare($conn, $sql_lokasi);
                if ($stmt_lokasi) {
                    mysqli_stmt_bind_param($stmt_lokasi, "ssi", $lattitude, $longitude, $radius);
                    
                    if (mysqli_stmt_execute($stmt_lokasi)) {
                        // KUNCI UTAMA: Mengambil ID yang baru saja dibuat otomatis oleh INSERT di atas
                        $id_lokasi = mysqli_insert_id($conn);
                    } else {
                        $errors[] = "Gagal menyimpan data lokasi: " . mysqli_stmt_error($stmt_lokasi);
                    }
                    mysqli_stmt_close($stmt_lokasi);
                } else {
                    $errors[] = "Gagal menyiapkan query lokasi: " . mysqli_error($conn);
                }
            }

            // update data perusahaan
            // jika lokasi selesai maka jalankan ini
            if (isset($id_lokasi)) {
                $sql = "UPDATE perusahaan 
                        SET nmaperusahaan = ?, 
                            alamat = ?, 
                            noWA = ?, 
                            id_lokasi = ?
                        WHERE id_perusahaan = ?";
                // 3. Jalankan Prepared Statement (untuk keamanan)
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    // "ssssssd" berarti tipe datanya: s = string, d = double/float/integer
                    // Sesuaikan urutan variabelnya dengan tanda tanya (?) di query
                    mysqli_stmt_bind_param($stmt, "sssii", 
                        $nmaPerusahaan, 
                        $alamat, 
                        $noWA, 
                        $id_lokasi, 
                        $id_perusahaan
                    );
                    // 4. Eksekusi query
                    if (mysqli_stmt_execute($stmt)) {
                        // Jika sukses, set notifikasi sukses atau redirect ke dashboard
                        $_SESSION['sukses'] = "Identitas perusahaan berhasil dilengkapi!";
                        echo "<script>
                            alert('Identitas perusahaan berhasil diperbarui');
                            window.location.href = 'dashboardperusahaan.php';
                            </script>"; 
                        exit();
                    } else {
                        $errors[] = "Gagal menyimpan data ke database: " . mysqli_stmt_error($stmt);
                    }
                    // Tutup statement
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = "Gagal mempersiapkan query: " . mysqli_error($conn);
                }
            }
        }
    }
    // Apa pun error-nya, jika array $errors tidak kosong, munculkan alert ini
    if (count($errors) > 0) {
        $pesan_error = implode("\\n", $errors);
        echo "<script>
            alert('$pesan_error');
            window.location.href = 'formperusahaan.php?action=edit';
            </script>";
        exit();
    }
}
?>