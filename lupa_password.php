<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Koneksi ke database lu
$conn = mysqli_connect("localhost", "root", "", "sistempenggajian");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Ambil tipe user dari URL (default-nya perusahaan)
$type = isset($_GET['type']) ? $_GET['type'] : 'perusahaan';
$pesan = "";

// 2. Logika ketika tombol "Simpan Password Baru" diklik
if (isset($_POST['reset_password'])) {
    
    if ($type == 'perusahaan') {
        $email = trim(mysqli_real_escape_string($conn, $_POST['input_1']));
        $nama_perusahaan = trim(mysqli_real_escape_string($conn, $_POST['input_2']));
        $password_baru = mysqli_real_escape_string($conn, $_POST['password_baru']);

        // Cek apakah email & nama perusahaan ada dan cocok di DB
        $query_cek = "SELECT * FROM perusahaan WHERE email = '$email' AND nmaperusahaan = '$nama_perusahaan' LIMIT 1";
        $result = mysqli_query($conn, $query_cek);

        if (mysqli_num_rows($result) > 0) {
            // Jika cocok, hash password baru dan update
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $query_update = "UPDATE perusahaan SET password_p = '$password_hash' WHERE email = '$email'";
            
            if (mysqli_query($conn, $query_update)) {
                echo "<script>alert('Password Perusahaan Berhasil Diubah!'); window.location.href='login.php';</script>";
                exit();
            }
        } else {
            $pesan = "Email atau Nama Perusahaan salah / tidak cocok!";
        }

    } else if ($type == 'karyawan') {
        $nmakaryawan = trim(mysqli_real_escape_string($conn, $_POST['input_1']));
        $id_jabatan = trim(mysqli_real_escape_string($conn, $_POST['input_2']));
        $password_baru = mysqli_real_escape_string($conn, $_POST['password_baru']);

        // Cek apakah nama karyawan & id_jabatan cocok di DB
        $query_cek = "SELECT * FROM userkaryawan WHERE nmakaryawan = '$nmakaryawan' AND id_jabatan = '$id_jabatan' LIMIT 1";
        $result = mysqli_query($conn, $query_cek);

        if (mysqli_num_rows($result) > 0) {
            // Jika cocok, hash password baru dan update
            $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $query_update = "UPDATE userkaryawan SET password = '$password_hash' WHERE nmakaryawan = '$nmakaryawan'";
            
            if (mysqli_query($conn, $query_update)) {
                echo "<script>alert('Password Karyawan Berhasil Diubah!'); window.location.href='loginKaryawan.php';</script>";
                exit();
            }
        } else {
            $pesan = "Nama Karyawan atau ID Jabatan salah / tidak cocok!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./assets/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inherit">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="assets/script.js" defer></script>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="header-login">
                <a href="index.php" class="logo-link">
                    <img src="assets/logo.svg" class="logo" alt="logo">
                </a>
            </div>
            
            <h2 style="text-align: center; margin-bottom: 20px; color: #2d3748;">
                Reset Password (<?= ucfirst($type); ?>)
            </h2>
            
            <?php if($pesan != ""): ?>
                <p style="color: #e53e3e; text-align: center; font-weight: 500; margin-bottom: 15px;"><?= $pesan; ?></p>
            <?php endif; ?>

            <form action="" method="post">
                
                <?php if ($type == 'perusahaan'): ?>
                    <div class="input-group">
                        <label>EMAIL PERUSAHAAN</label>
                        <input type="email" name="input_1" placeholder="Masukkan Email Terdaftar" required>
                    </div>
                    <div class="input-group">
                        <label>NAMA PERUSAHAAN</label>
                        <input type="text" name="input_2" placeholder="Masukkan Nama Perusahaan Anda" required>
                    </div>

                <?php else: ?>
                    <div class="input-group">
                        <label>NAMA KARYAWAN</label>
                        <input type="text" name="input_1" placeholder="Masukkan Nama Lengkap" required>
                    </div>
                    <div class="input-group">
                        <label>ID JABATAN</label>
                        <input type="text" name="input_2" placeholder="Contoh: 1 / 2 / 3" required>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <label>PASSWORD BARU</label>
                    <input type="password" name="password_baru" placeholder="Ketik Password Baru" required>
                </div>

                <button type="submit" name="reset_password" class="btn-signin">Simpan Password Baru</button>
                
                <p class="footer-text" style="text-align: center; margin-top: 15px;">
                    <a href="<?= ($type == 'karyawan') ? 'loginKaryawan.php' : 'login.php'; ?>">Kembali ke Halaman Login</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>