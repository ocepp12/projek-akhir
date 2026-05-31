<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect("localhost", "root", "", "sistempenggajian");

session_start();

if(isset($_POST['masuk'])){

    $nmakaryawan = trim(mysqli_real_escape_string($conn, $_POST['nmaKaryawan']));
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // VALIDASI KOSONG
     if($nmakaryawan == '' or $password == ''){

        echo "<script>
                alert('Masukkan nama dan password!');
              </script>";

    } else {

        // CARI USER
        $query_cek = "SELECT * FROM userkaryawan 
                      WHERE nmakaryawan = '$nmakaryawan'
                      LIMIT 1";

        $result = mysqli_query($conn, $query_cek);

        // USER TIDAK ADA
        if(mysqli_num_rows($result) == 0){

            echo "<script>
                    alert('Data tidak tersedia!');
                    window.location.href='loginkaryawan.php';
                  </script>";

        } else {

            $r1 = mysqli_fetch_assoc($result);

            // CEK PASSWORD
            if(password_verify($password, $r1['password'])){

                $_SESSION['loginKaryawan'] = true;
                $_SESSION['id_karyawan'] = $r1['id_karyawan'];

                echo "<script>
                        alert('Login berhasil!');
                        window.location.href='dashboardkaryawan.php';
                      </script>";

            } else {

                echo "<script>
                        alert('Password salah!');
                      </script>";
            }
        }
    }
}

?>