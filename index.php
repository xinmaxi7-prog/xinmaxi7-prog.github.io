<?php
session_start();
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'rubick123'); 
$db_file = "database.php";

if (!file_exists($db_file)) { file_put_contents($db_file, "<?php exit; ?>" . PHP_EOL); }

// Logika Login
if (isset($_POST['login'])) {
    if (($_POST['username'] ?? '') === ADMIN_USERNAME && ($_POST['password'] ?? '') === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        echo "<script>alert('Login Berhasil, Selamat Datang Grand Magus!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Username atau Password Salah!');</script>";
    }
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit(); }
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// --- SIHIR UTAMA ---
function konversiLinkDownload($url) {
    if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return "https://docs.google.com/uc?export=download&id=" . $matches[1];
    }
    return $url;
}

if (isset($_POST['add_link']) && $is_admin) {
    $file_name = trim($_POST['fileName']);
    $raw_link = trim($_POST['driveLink']);
    
    // 1. OPSI JIKA LINK DARI GOOGLE DRIVE
    if (strpos($raw_link, 'drive.google.com') !== false) {
        $direct_link = konversiLinkDownload($raw_link);
        
    // 2. OPSI JIKA LINK DARI GITHUB
    } elseif (strpos($raw_link, 'github.com') !== false) {
        $direct_link = str_replace('github.com', 'raw.githubusercontent.com', $raw_link);
        $direct_link = str_replace('/blob/', '/', $direct_link);
        
    // 3. JIKA LINK LAINNYA
    } else {
        $direct_link = $raw_link; 
    }
    
    if (!empty($file_name) && !empty($direct_link)) {
        file_put_contents($db_file, $file_name . "|||" . $direct_link . PHP_EOL, FILE_APPEND | LOCK_EX);
        echo "<script>alert('Mantra berhasil dipasang!'); window.location='index.php';</script>";
    }
}

if (isset($_GET['delete_idx']) && $is_admin) {
    $idx = (int)$_GET['delete_idx'];
    $lines = file($db_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (isset($lines[$idx])) {
        unset($lines[$idx]);
        file_put_contents($db_file, implode(PHP_EOL, $lines) . (empty($lines) ? "" : PHP_EOL));
        echo "<script>window.location='index.php';</script>";
    }
}

// ==================== MEMBACA DATA VAULT + SISTEM PAGING ====================
$files_data = [];
$per_halaman = 5; // Batas data per halaman (Ubah ke 10 jika ingin menampilkan 10 data)
$halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;

$total_data = 0;
$total_halaman = 1;

if (file_exists($db_file)) {
    $lines = file($db_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $index => $line) {
        if ($index === 0) continue; 
        
        $parts = explode("|||", $line);
        if (count($parts) == 2) { 
            $files_data[] = [
                'index' => $index, 
                'name' => $parts[0], 
                'link' => $parts[1]
            ]; 
        }
    }
    
    $files_data = array_reverse($files_data);
    
    $total_data = count($files_data);
    $total_halaman = ceil($total_data / $per_halaman);
    if ($total_halaman < 1) $total_halaman = 1;
    if ($halaman_aktif > $total_halaman) $halaman_aktif = $total_halaman;
    
    $indeks_mulai = ($halaman_aktif - 1) * $per_halaman;
    $files_data = array_slice($files_data, $indeks_mulai, $per_halaman);
}
// ============================================================================
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAXI FILE SHARING - THE GRAND VAULT</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body>

<div class="container">
    <div class="auth-bar">
        <?php if ($is_admin): ?>
            <div class="auth-status-left">
                <div class="rubick-minilogo"></div>
                <span style="color: #19e69e; font-weight: 700; font-size: 13px;">🧙‍♂️ Mode Admin (Grand Magus) Aktif</span>
            </div>
            <a href="index.php?logout=1" style="color: #e74c3c; float:right; text-decoration:none; font-weight:700; background: rgba(231,76,60,0.1); padding: 2px 8px; border-radius:4px;">[ Logout ]</a>
        <?php else: ?>
            <div class="auth-status-left">
                <div class="rubick-minilogo"></div>
                <span style="font-weight: 500;">🌐 Mode Klien (Pengunduhan Aman)</span>
            </div>
            <span style="float: right;"><span class="login-toggle" onclick="toggleLoginBox()">🔑 Portal</span></span>
        <?php endif; ?>
    </div>

    <?php if (!$is_admin): ?>
        <div id="loginBox" class="login-section" style="display: none;">
            <h3 style="color: #e6c24a; border-left: 4px solid #e6c24a;">🔑 Gerbang Sihir</h3>
            <form action="index.php" method="post">
                <label>Username Kunci:</label>
                <input type="text" name="username" placeholder="Masukkan Username Admin" required>
                <label>Password Mantra:</label>
                <input type="password" name="password" placeholder="Masukkan Password Admin" required>
                <button type="submit" name="login" style="background: linear-gradient(135deg, #e6c24a, #b39224); color: #080d10; width: 100%; margin-top: 5px;">Buka Segel </button>
            </form>
        </div>
    <?php endif; ?>

    <h1>THE GRAND MAGUS ARCHIVE</h1>
    <div class="subtitle">Mirka'S Secret File Sharing</div>
    
    <div class="logo-container">
        <img src="rubick-logo.png" alt="Grand Magus Logo" class="main-logo">
    </div>

    <?php if ($is_admin): ?>
        <div class="section">
            <h3>Pasang Berkas / Mantra Baru</h3>
            <form action="index.php" method="post">
                <label>Nama Pengenal Berkas:</label>
                <input type="text" name="fileName" placeholder="Contoh: Rekaman Rahasia Revan" required>
                
                <label>Masukkan Tautan Google Drive Berbagi:</label>
                <input type="text" name="driveLink" placeholder="Tempel tautan Google Drive di sini..." required>
                
                <button type="submit" name="add_link" style="margin-top: 10px; width: 100%;">Enkripsi & Amankan ke dalam Vault</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="section">
        <h3>Instant Spell</h3>
        <input type="text" id="searchInput" onkeyup="filterFiles()" placeholder="Ketik nama berkas rahasia untuk mencari...">
        
        <table id="fileTable">
            <thead>
                <tr>
                    <th>Nama Berkas Rahasia</th>
                    <th>Chantless Magic</th>
                    <?php if ($is_admin): ?>
                        <th>Opsi Sistem</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($files_data)): ?>
                    <tr>
                        <td colspan="<?php echo $is_admin ? '3' : '2'; ?>" style="text-align:center; color:#4e6357; padding: 40px; font-style: italic;">Belum ada berkas terenkripsi di dalam Vault ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($files_data as $data): ?>
                        <tr>
                            <td class="file-name" style="font-weight: 600; color: #ffffff; font-size: 11pt;">
                                📦 &nbsp;<?php echo htmlspecialchars($data['name']); ?>
                            </td>
                            <td style="width: 260px;">
                                 <button onclick="prosesUnduhMantra(this, '<?php echo $data['link']; ?>')" class="btn-download-ekg">
                                    <svg class="ekg-border" viewBox="0 0 160 50">
                                        <path d="M 0,45 L 35,45 L 42,35 L 48,45 L 70,45 L 75,8 L 82,48 L 88,38 L 93,45 L 125,45 L 132,38 L 138,45 L 160,45" />
                                    </svg>
                                    <span>Unduh Berkas</span>
                                </button>
                                
                                <div class="progress-container">
                                    <div class="progress-bar"></div>
                                    <span class="progress-text">0%</span>
                                </div>
                            </td>
                            <?php if ($is_admin): ?>
                                <td style="width: 100px;">
                                    <a href="index.php?delete_idx=<?php echo $data['index']; ?>" class="btn btn-delete" onclick="return confirm('Hapus berkas ini secara permanen?')">Hapus</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- --- GERBANG NAVIGASI HALAMAN (PAGINATION - SLIDING WINDOW) --- -->
        <?php if (isset($total_halaman) && $total_halaman > 1): ?>
            <div class="pagination-vault">
                
                <!-- Tombol Previous -->
                <?php if ($halaman_aktif > 1): ?>
                    <a href="index.php?page=<?php echo $halaman_aktif - 1; ?>" class="page-link">&laquo; Prev</a>
                <?php else: ?>
                    <a href="#" class="page-link" style="opacity: 0.3; pointer-events: none;">&laquo; Prev</a>
                <?php endif; ?>

                <?php
                $maks_tampil = 3; // Menampilkan 3 nomor inti di tengah halaman aktif
                $awal_hal = max(1, $halaman_aktif - floor($maks_tampil / 2));
                $akhir_hal = min($total_halaman, $awal_hal + $maks_tampil - 1);

                if ($akhir_hal - $awal_hal + 1 < $maks_tampil) {
                    $awal_hal = max(1, $akhir_hal - $maks_tampil + 1);
                }

                // Tampilkan Halaman 1 dan Titik-titik Awal jika halaman aktif bergeser jauh ke kanan
                if ($awal_hal > 1) {
                    echo '<a href="index.php?page=1" class="page-link ' . ($halaman_aktif == 1 ? 'active' : '') . '">1</a>';
                    if ($awal_hal > 2) {
                        echo '<span class="page-link" style="border: none; background: none; opacity: 0.5; pointer-events: none;">...</span>';
                    }
                }

                // Perulangan Angka Utama (Sliding Window)
                for ($i = $awal_hal; $i <= $akhir_hal; $i++) {
                    $status_aktif = ($i === $halaman_aktif) ? 'active' : '';
                    echo '<a href="index.php?page=' . $i . '" class="page-link ' . $status_aktif . '">' . $i . '</a>';
                }

                // Tampilkan Titik-titik Akhir dan Halaman Terakhir jika masih banyak data ke kanan
                if ($akhir_hal < $total_halaman) {
                    if ($akhir_hal < $total_halaman - 1) {
                        echo '<span class="page-link" style="border: none; background: none; opacity: 0.5; pointer-events: none;">...</span>';
                    }
                    echo '<a href="index.php?page=' . $total_halaman . '" class="page-link ' . ($halaman_aktif == $total_halaman ? 'active' : '') . '">' . $total_halaman . '</a>';
                }
                ?>

                <!-- Tombol Next -->
                <?php if ($halaman_aktif < $total_halaman): ?>
                    <a href="index.php?page=<?php echo $halaman_aktif + 1; ?>" class="page-link">Next &raquo;</a>
                <?php else: ?>
                    <a href="#" class="page-link" style="opacity: 0.3; pointer-events: none;">Next &raquo;</a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</div>

<canvas id="neuralCanvas" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none;"></canvas>

<script>
function toggleLoginBox() {
    const box = document.getElementById('loginBox');
    box.style.display = (box.style.display === 'none') ? 'block' : 'none';
}

function filterFiles() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("fileTable");
    tr = table.getElementsByTagName("tr");
    for (i = 1; i < tr.length; i++) {
        td = tr[i].getElementsByClassName("file-name")[0];
        if (td) {
            txtValue = td.textContent || td.innerText;
            tr[i].style.display = (txtValue.toUpperCase().indexOf(filter) > -1) ? "" : "none";
        }       
    }
}

function prosesUnduhMantra(btnElement, url) {
    const pContainer = btnElement.nextElementSibling;
    const pBar = pContainer.querySelector('.progress-bar');
    const pText = pContainer.querySelector('.progress-text');
        
    btnElement.disabled = true;
    btnElement.style.opacity = "0.6";
    btnElement.innerText = "Mendekripsi...";
    pContainer.style.display = 'block';
    
    let kemajuan = 0;
    const interval = setInterval(() => {
        kemajuan += Math.floor(Math.random() * 14) + 6; 
        if (kemajuan >= 100) {
            kemajuan = 100;
            clearInterval(interval);
            
            pBar.style.width = '100%';
            pText.innerText = '100% (Membuka Gerbang Vault)';
            
            window.open(url, '_blank');
            
            setTimeout(() => {
                btnElement.disabled = false;
                btnElement.style.opacity = "1";
                btnElement.innerText = "Unduh Berkas";
                pContainer.style.display = 'none';
                pBar.style.width = '0%';
            }, 1800);
        } else {
            pBar.style.width = kemajuan + '%';
            pText.innerText = kemajuan + '% Mendekripsi Data...';
        }
    }, 80); 
}

// LOGIKA WALLPAPER CANVAS NEURAL
const canvas = document.getElementById('neuralCanvas');
const ctx = canvas.getContext('2d');
let particles = [];
const particleCount = 45; 
const maxDistance = 150;  

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

class Particle {
    constructor() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.vx = (Math.random() - 0.5) * 0.5; 
        this.vy = (Math.random() - 0.5) * 0.5;
        this.radius = Math.random() * 4 + 5; 
    }
    update() {
        this.x += this.vx;
        this.y += this.vy;
        if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
        if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
    }
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        ctx.shadowBlur = 15;
        ctx.shadowColor = 'rgba(25, 230, 158, 0.85)';
        let gradient = ctx.createRadialGradient(this.x, this.y, 1, this.x, this.y, this.radius);
        gradient.addColorStop(0, '#ffffff'); 
        gradient.addColorStop(0.3, '#46ffc4'); 
        gradient.addColorStop(1, 'rgba(25, 230, 158, 0.9)'); 
        ctx.fillStyle = gradient;
        ctx.fill();
        ctx.shadowBlur = 0; 
    }
}

for (let i = 0; i < particleCount; i++) { particles.push(new Particle()); }

function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < maxDistance) {
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                const alpha = (1 - dist / maxDistance) * 0.5; 
                ctx.strokeStyle = `rgba(35, 255, 178, ${alpha})`; 
                ctx.lineWidth = 2; 
                ctx.stroke();
            }
        }
    }
    particles.forEach(p => { p.update(); p.draw(); });
    requestAnimationFrame(animate);
}
animate();
</script>
</body>
</html>