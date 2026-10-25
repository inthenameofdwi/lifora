<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['username'])) {
    // Cek role yang sedang login, arahkan ke dashboard yang sesuai
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'dokter') {
        header("Location: dokter/dashboard_dokter.php");
    } else {
        header("Location: admin/dashboard_admin.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $error = "Username dan Kata Sandi wajib diisi!";
    } else {
        try {
            // Cari user berdasarkan username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Cocokkan password secara langsung (Plain Text)
            if ($user && $password === $user['password']) {
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'] ?? 'admin';

                // Redirect berdasarkan role
                if ($_SESSION['role'] === 'dokter') {
                    header("Location: dokter/dashboard_dokter.php");
                } else {
                    header("Location: admin/dashboard_admin.php");
                }
                exit;
            } else {
                $error = "Username atau Kata Sandi salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Sistem Rekam Medis - Lifora Puskesmas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --lifora-primary: #00806f;
            --lifora-primary-dark: #005a4e;
            --lifora-primary-soft: #9df2e3;
            --lifora-blue-soft: #e4edfb;
            --lifora-blue-badge: #d6e4fa;
            --lifora-blue-text: #0b5c8a;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9ff;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }
        body::before, body::after {
            content: ''; position: fixed; z-index: 0; pointer-events: none; border-radius: 50%; filter: blur(80px);
        }
        body::before { width: 400px; height: 400px; left: -150px; top: -50px; background: rgba(157, 242, 227, 0.4); }
        body::after  { width: 400px; height: 400px; right: -150px; bottom: -50px; background: rgba(214, 228, 250, 0.6); }

        .login-wrapper {
            position: relative; z-index: 1;
            width: 100%; max-width: 1000px;
            background: #fff;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.1);
            display: flex;
            min-height: 600px;
        }
        .login-left {
            background: var(--lifora-blue-soft);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 50%;
        }
        .login-right {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 50%;
        }
        .brand-logo-box {
            width: 36px; height: 36px;
            background: var(--lifora-primary);
            color: #fff;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .brand-logo-box::after {
            content: ''; position: absolute; width: 6px; height: 6px; border-radius: 50%; background: #7dd3fc; top: 8px; left: 10px;
        }
        .feature-box {
            background: var(--lifora-blue-badge);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
        }
        .input-group-text {
            background-color: #eef3ff; border: 1px solid transparent; border-right: 0; color: #64748b;
        }
        .form-control {
            background-color: #eef3ff; border: 1px solid transparent; border-left: 0; padding: 0.75rem 1rem;
        }
        .form-control:focus {
            background-color: #fff; border-color: var(--lifora-primary); box-shadow: none;
        }
        .input-group:focus-within .input-group-text {
            background-color: #fff; border-color: var(--lifora-primary);
        }
        .btn-lifora {
            background-color: var(--lifora-primary-dark);
            color: #fff;
            padding: 0.85rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: 0.2s;
        }
        .btn-lifora:hover { background-color: #00473e; color: #fff; }
        
        @media (max-width: 860px) {
            .login-wrapper { flex-direction: column; min-height: auto; }
            .login-left, .login-right { width: 100%; padding: 2rem; }
            .login-left { border-bottom: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Sisi Kiri -->
    <div class="login-left">
        <div>
            <div class="d-flex align-items-center gap-2 mb-5">
                <div class="brand-logo-box">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 text-dark lh-1" style="font-family: 'Plus Jakarta Sans', sans-serif;">Lifora</div>
                    <div class="text-lifora-primary fw-bold text-uppercase" style="font-size: 9px; letter-spacing: 0.1em; color: var(--lifora-primary) !important;">Puskesmas</div>
                </div>
            </div>

            <div class="d-inline-flex align-items-center gap-2 bg-lifora-primary-soft text-dark rounded-pill px-3 py-1 mb-3 fw-bold" style="font-size: 11px; letter-spacing: 0.05em; background-color: var(--lifora-primary-soft);">
                <span class="bg-dark rounded-circle d-inline-block" style="width: 6px; height: 6px;"></span> SI-RME TERAKREDITASI
            </div>

            <h1 class="fw-bold text-dark mb-3" style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.8rem; line-height: 1.3;">Transformasi Digital Puskesmas Terpadu</h1>
            <p class="text-muted mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                Akurasi diagnosis, tata kelola data rekam medis elektronik, dan integrasi penuh dengan ekosistem SatuSehat Nasional.
            </p>

            <div class="d-flex flex-column gap-2">
                <div class="feature-box">
                    <i class="fa-solid fa-shield-halved text-lifora-primary mt-1" style="color: var(--lifora-primary) !important;"></i>
                    <div>
                        <strong class="d-block text-dark" style="font-size: 14px;">Keamanan Standar BSSN</strong>
                        <span class="text-muted" style="font-size: 12px;">Enkripsi rekam medis end-to-end dengan audit trail terverifikasi.</span>
                    </div>
                </div>
                <div class="feature-box">
                    <i class="fa-solid fa-circle-nodes text-lifora-blue mt-1" style="color: var(--lifora-blue-text) !important;"></i>
                    <div>
                        <strong class="d-block text-dark" style="font-size: 14px;">Sinkronisasi SatuSehat & BPJS P-Care</strong>
                        <span class="text-muted" style="font-size: 12px;">Bridging otomatis rujukan dan klaim kapitasi real-time.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-5">
            <div>
                <small class="d-block text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 0.08em;">Kesiapan Faskes</small>
                <strong style="font-family: 'Plus Jakarta Sans', sans-serif; color: var(--lifora-primary-dark);">Tingkat Paripurna</strong>
            </div>
            <span class="badge bg-white text-dark shadow-sm rounded-pill px-3 py-2 fw-semibold" style="font-size: 13px;">
                <span class="bg-lifora-primary rounded-circle d-inline-block me-1" style="width: 8px; height: 8px; background-color: var(--lifora-primary) !important;"></span> Server Online
            </span>
        </div>
    </div>

    <!-- Sisi Kanan -->
    <div class="login-right">
        <h2 class="fw-bold text-dark mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.02em;">Masuk ke Sistem</h2>
        <p class="text-muted mb-4" style="font-size: 14px;">Akses Terpadu Administrasi & Pelayanan Pasien</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 py-2 px-3 mb-3" role="alert" style="font-size: 13px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="alert d-flex align-items-start gap-2 rounded-3 mb-4 p-3" role="alert" style="background-color: var(--lifora-blue-badge); color: var(--lifora-blue-text); font-size: 13px; border: none;">
            <i class="fa-solid fa-circle-info mt-1"></i>
            <span>Akun dikelola oleh Administrator Puskesmas. Silakan masukkan username dan password resmi Anda.</span>
        </div>

        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold text-dark" style="font-size: 13px;">Username Pegawai / NIK SIP <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-regular fa-id-card"></i></span>
                    <input type="text" class="form-control" id="username" name="username" required value="admin_rekammedis" autocomplete="username" placeholder="Masukkan username">
                </div>
                <div class="text-muted mt-1" style="font-size: 11px;">ID akun terdaftar pada SIMPUS & Kepegawaian Dinkes</div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold text-dark" style="font-size: 13px;">Kata Sandi <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required value="Puskesmas2025#" autocomplete="current-password" placeholder="Masukkan kata sandi">
                    <button type="button" class="btn btn-light border" onclick="togglePassword()" aria-label="Tampilkan kata sandi" style="border-color: transparent !important; background-color: #eef3ff;">
                        <i id="eyeIcon" class="fa-regular fa-eye text-muted"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-lifora w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Sistem
            </button>
        </form>

        <div class="text-center text-muted mt-5" style="font-size: 11px; font-weight: 500;">
            Kementerian Kesehatan RI &bull; Standar SatuSehat &bull; v2.4 Puskesmas Melati Sejahtera
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
</script>
</body>
</html>