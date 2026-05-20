<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Perusahaan</title>
        <link rel="stylesheet" href="assets/style.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inherit">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
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
                
                <form action="simpan_perusahaan.php" method="post">
                    
                    <!-- 1. NAMA PERUSAHAAN -->
                    <div class="input-group">
                        <label for="nama_perusahaan">NAMA PERUSAHAAN</label>
                        <input type="text" name="nama_perusahaan" id="nama_perusahaan" placeholder="Masukkan Nama Perusahaan" required>
                    </div>

                    <!-- 2. ALAMAT -->
                    <div class="input-group">
                        <label for="alamat">ALAMAT PERUSAHAAN</label>
                        <textarea name="alamat" id="alamat" placeholder="Masukkan Alamat Lengkap" rows="3" required></textarea>
                    </div>

                    <!-- 3. NOMOR WA -->
                    <div class="input-group">
                        <label for="no_wa">NOMOR WHATSAPP</label>
                        <input type="tel" name="no_wa" id="no_wa" placeholder="Masukkan Nomor WhatsApp" required>
                    </div>

                    <!-- 4. LATITUDE -->
                    <div class="input-group">
                        <label for="latitude">LATITUDE</label>
                        <input type="text" name="latitude" id="latitude" placeholder="Contoh: -6.2088" required>
                    </div>

                    <!-- 5. LONGITUDE -->
                    <div class="input-group">
                        <label for="longitude">LONGITUDE</label>
                        <input type="text" name="longitude" id="longitude" placeholder="Contoh: 106.8456" required>
                    </div>

                    <!-- 6. RADIUS -->
                    <div class="input-group">
                        <label for="radius">RADIUS ABSENSI (METER)</label>
                        <input type="number" name="radius" id="radius" placeholder="Contoh: 100" min="1" required>
                    </div>

                    <!-- BUTTON SUBMIT -->
                    <button type="submit" name="submit_perusahaan" class="btn-signin">Simpan Data</button>
                    
                    <div class="footer">
                        <p class="copyright1">&copy; 2026 Payroll & Sales. All rights reserved.</p>
                    </div>
                </form>
            </div>
        </div>
    </body>