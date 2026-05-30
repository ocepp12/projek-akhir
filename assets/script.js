/**
 * SISTEM INFORMASI ABSENSI & DASHBOARD UTAMA
 * File: assets/script.js
 */

// =================================================================
// 1. FITUR UTAMA: ABSENSI ONLINE (GEOLOCATION & AJAX FETCH)
// =================================================================

/**
 * Menginisialisasi proses pengambilan koordinat GPS pengguna
 */
function ambilLokasi() {
    const pesan = document.getElementById("pesan");
    
    if (!navigator.geolocation) {
        pesan.innerHTML = "Geolocation tidak didukung oleh browser ini.";
        return;
    }

    pesan.innerHTML = "Sedang mengambil lokasi... Mohon tunggu.";
    navigator.geolocation.getCurrentPosition(showPosition, showError, {
        enableHighAccuracy: true
    });
}

/**
 * Berhasil mengambil lokasi, lalu mengirimkan koordinat ke server via AJAX
 * @param {Object} position - Objek koordinat dari browser
 */
function showPosition(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const pesan = document.getElementById("pesan");
    
    pesan.innerHTML = `Lokasi Ditemukan!<br>Lat: ${lat}<br>Lng: ${lng}<br><br>Sedang mengirim data ke server...`;

    // Mengirim data koordinat ke proses.php secara background (tanpa reload)
    fetch('proses.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `latitude=${lat}&longitude=${lng}`
    })
    .then(response => {
        if (!response.ok) throw new Error('Gagal terhubung ke server');
        return response.text();
    })
    .then(data => {
        pesan.innerHTML = data;
    })
    .catch(error => {
        pesan.innerHTML = `Terjadi kesalahan: ${error.message}`;
    });
}

/**
 * Menangani error jika proses pengambilan GPS gagal
 * @param {Object} error - Objek error dari browser
 */
function showError(error) {
    const pesan = document.getElementById("pesan");
    switch(error.code) {
        case error.PERMISSION_DENIED:
            pesan.innerHTML = "User menolak permintaan lokasi.";
            break;
        case error.POSITION_UNAVAILABLE:
            pesan.innerHTML = "Informasi lokasi tidak tersedia.";
            break;
        case error.TIMEOUT:
            pesan.innerHTML = "Permintaan waktu mengambil lokasi habis.";
            break;
        case error.UNKNOWN_ERROR:
            pesan.innerHTML = "Terjadi kesalahan yang tidak diketahui.";
            break;
    }
}


// =================================================================
// INTERAKSI UI & KOMPONEN (Dijalankan Setelah DOM Selesai Dimuat)
// =================================================================
document.addEventListener("DOMContentLoaded", function () {

    // 2. FITUR UTAMA: CAROUSEL/SLIDER BANNER (SWIPER INITIATION)
    if (document.querySelector('.swiper')) {
        new Swiper(".swiper", {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                    spaceBetween: 40,
                }
            }
        });
    }

    // 3. FITUR UTAMA: RESPONSIVE TOGGLE SIDEBAR
    const toggleBtn = document.querySelector('.toggle-btn');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('sidebar-active');
        });
    }

    // 4. FITUR UTAMA: ELEGAN ANIMASI PENCARIAN (SEARCH BAR)
    const searchWrapper = document.querySelector('.search-wrapper');
    const searchToggle = document.querySelector('.search-toggle');
    const searchInput = document.querySelector('.search-input');

    if (searchToggle && searchWrapper && searchInput) {
        // Klik icon kaca pembesar untuk buka/tutup input search
        searchToggle.addEventListener('click', function () {
            searchWrapper.classList.toggle('open');
            
            // Jika terbuka, kursor otomatis fokus ke dalam kotak input
            if (searchWrapper.classList.contains('open')) {
                searchInput.focus();
            }
        });

        // Deteksi tombol Enter untuk eksekusi pencarian data
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && this.value.trim() !== '') {
                alert(`Mencari data untuk: ${this.value}`);
                
                // Reset form setelah pencarian dijalankan
                searchWrapper.classList.remove('open');
                this.value = ''; 
            }
        });
    }

});

// =================================================================
// FITUR UTAMA: SHOW/HIDE PASSWORD (DINAMIS LOGIN & DAFTAR)
// =================================================================
const togglePassword = document.querySelector('#togglePassword');

if (togglePassword) {
    togglePassword.addEventListener('click', function () {
        // Cari elemen password secara dinamis, baik itu id password_log (login) maupun password_p (daftar)
        const passwordInput = document.querySelector('#password_log') || document.querySelector('#password_p');
        
        if (passwordInput) {
            // Tukar tipe input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Tukar ikon mata
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        }
    });
}

// =================================================================
// 5. FITUR TAMBAHAN: GRAFIK REKAP JABATAN (CHART.JS)
// =================================================================
document.addEventListener("DOMContentLoaded", function () {
    const canvasGrafik = document.getElementById('grafikJabatan');
    
    // Validasi: Jalankan script hanya jika element canvas tersebut memang ada di halaman aktif
    if (canvasGrafik) {
        // Ekstrak string JSON dari atribut HTML data-*
        const rawLabels = canvasGrafik.getAttribute('data-labels');
        const rawValues = canvasGrafik.getAttribute('data-values');
        
        if (rawLabels && rawValues) {
            // Ubah string JSON kembali menjadi Array JavaScript asli
            const labelsData = JSON.parse(rawLabels);
            const valuesData = JSON.parse(rawValues);
            
            const ctx = canvasGrafik.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut', // Tipe donat (bisa diganti 'pie' kalau mau bulat penuh)
                data: {
                    labels: labelsData,
                    datasets: [{
                        data: valuesData,
                        // Palet warna Grafik
                        backgroundColor: ['#3E9C35', '#48bb78', '#319795', '#2b6cb0', '#718096'],
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom', // Keterangan label dipindah ke bawah grafik
                            labels: {
                                font: {
                                    family: 'Inherit', /* Mengikuti font default halaman */
                                    size: 12
                                },
                                padding: 15
                            }
                        }
                    },
                    cutout: '60%' // Mengatur ketebalan bolongan tengah donat (makin besar % makin tipis)
                }
            });
        }
    }
});