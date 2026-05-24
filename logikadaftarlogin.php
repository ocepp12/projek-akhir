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
if (isset($_POST['register'])){
    $email      = mysqli_real_escape_string($conn, $_POST['username']);
    $password_p = mysqli_real_escape_string($conn, $_POST['password_p']);

    // Validasi Input
    if (empty($email) || empty($password_p)) {
        $errors[] = "Semua kolom wajib diisi!";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    if (strlen($password_p) < 8) {
        $errors[] = "Password minimal harus 8 karakter!";
    }
    if (!preg_match("/[A-Z]/", $password_p)) {
        $errors[] = "Password harus memiliki setidaknya satu huruf besar!";
    }
    if (!preg_match("/[a-z]/", $password_p)) {
        $errors[] = "Password harus memiliki setidaknya satu huruf kecil!";
    }
    if (!preg_match("/[0-9]/", $password_p)) {
        $errors[] = "Password harus memiliki setidaknya satu angka!";
    }

    // Jika tidak ada error validasi, lanjut cek ke database
    if (count($errors) === 0) {
        
        $query_cek = "SELECT email FROM perusahaan WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query_cek);
        
        if (mysqli_num_rows($result) > 0) {
            echo "<script>alert('Email Sudah Terdaftar!');</script>";
        } else {
            $password_hash = password_hash($password_p, PASSWORD_DEFAULT);

            // Tambah data ke database
            $query = "INSERT INTO perusahaan (email, password_p) VALUES ('$email', '$password_hash')";
            
            if (mysqli_query($conn, $query)){
                echo "<script>
                        alert('Registrasi Berhasil!');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            } else {
                echo "Periksa kembali: " . mysqli_error($conn);
            }
        } // Tutup Else Cek Email
    } else {
        // Jika ada error validasi input
        $pesan_error = implode("\\n", $errors);
        echo "<script>
                alert('$pesan_error');
                window.location.href = 'daftar.php';
                </script>";
    }
} // Tutup If Isset Register
if (isset($_POST['masuk'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_p = mysqli_real_escape_string($conn, $_POST['password_p']);
    if ($email == '' or $password_p == ''){
        echo "<script>alert('Masukan email dan password');</script>";
    }else{
        $query_cek = "SELECT * FROM perusahaan WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query_cek);
        if (mysqli_num_rows($result)=== 0){
            echo "<script>alert('Email tidak tersedia!');</script>";
        }else{
            $r1 = mysqli_fetch_array($result); 
            if(password_verify($password_p, $r1['password_p'])){
                $_SESSION['email'] = $r1['email'];
                $_SESSION['id_perusahaan'] = $r1['id_perusahaan'];
<<<<<<< Updated upstream
                echo "<script>
                        alert('Anda berhasil login!');
                        window.location.href = 'formperusahaan.php';
                        </script>";
                exit();
=======
                $_SESSION['perusahaan'] = $r1['nmaPerusahaan'];
                if (empty($r1['nmaPerusahaan'])){
                    //untuk login pertama kali
                    header("Location: formperusahaan.php");
                    exit();
                }else{
                    //untuk login yang kesekian kali
                    header("Location: dashboardperusahaan.php");
                    exit();;
                }
>>>>>>> Stashed changes
            }else{
            echo "<script>
                alert('Password yang dimasukkan tidak sesuai!');
                window.location.href = 'login.php';
                </script>";
            }
        }    
    }
}
mysqli_close($conn);
?>