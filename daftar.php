<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
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
            
            <form action="logikadaftarlogin.php" method="post">
                <div class="input-group">
                    <label for="username">EMAIL</label>
                    <input type="email" name="username" id="username" placeholder="Email" required>
                </div>

                <div class="input-group">
                    <label for="password_p">PASSWORD</label>
                    <div class="password-wrapper">
                        <input type="password" name="password_p" id="password_p" placeholder="Password" required>
                        <i class="fa-regular fa-eye" id="togglePassword" style="cursor: pointer;"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-signin">Daftar</button>
                
                <p class="footer-text">Sudah punya akun? <a href="login.php">Masuk</a></p>
                <div class="footer">
                    <p class="copyright1">&copy; 2026 Payroll & Sales. All rights reserved.</p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>