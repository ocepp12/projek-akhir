<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sistem Absensi Geolocation</title>
    <script src="assets/script.js" defer></script>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        #pesan { margin-top: 20px; font-weight: bold; color: blue; }
        button { padding: 15px 30px; font-size: 24px; cursor: pointer; background-color: #28a745; color: white; border: none; border-radius: 5px; }
    </style>
</head>
<body>

    <h2>Absensi Kehadiran Karyawan</h2>
    <p>Silakan klik tombol di bawah untuk absen:</p>
    
    <button onclick="ambilLokasi()">ABSEN SEKARANG</button>

    <div id="pesan"></div>
</body>
</html>