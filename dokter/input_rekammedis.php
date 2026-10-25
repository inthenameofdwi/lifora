<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

$id_antrean = (int)($_GET['id_antrean'] ?? 0);
if ($id_antrean <= 0) {
    header("Location: dashboard_dokter.php");
    exit;
}

// Ambil data antrean yang akan diperiksa
$stmt = $pdo->prepare("SELECT * FROM antrean WHERE id = ?");
$stmt->execute([$id_antrean]);
$data_antrean = $stmt->fetch();

if (!$data_antrean) {
    echo "<script>alert('Data antrean tidak ditemukan!'); window.location='dashboard_dokter.php';</script>";
    exit;
}

$pesan = '';

// Proses Simpan Rekam Medis
if (isset($_POST['simpan'])) {
    $no_registrasi    = "REG-" . date('Ymd') . "-" . rand(1000, 9999);
    $waktu_kunjungan  = date('Y-m-d H:i:s');
    $no_rm            = $_POST['no_rm'];
    $nama_pasien      = $_POST['nama_pasien'];
    $poli             = $_POST['poli_tujuan'];
    $dokter           = $_POST['dokter'];
    $kode_icd         = trim($_POST['kode_icd']);
    $nama_diagnosis   = trim($_POST['nama_diagnosis']);
    $keterangan_klinis = trim($_POST['keterangan_klinis']);

    if ($kode_icd === '' || $nama_diagnosis === '' || $keterangan_klinis === '') {
        $pesan = "<div class='alert alert-danger rounded-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Kolom ICD-10, Diagnosis, dan SOAP wajib diisi.</div>";
    } else {
        try {
            // Mulai transaksi agar insert RM dan update antrean berjalan bersamaan
            $pdo->beginTransaction();

            // 1. Insert ke rekam_medis
            $sqlInsert = "INSERT INTO rekam_medis (no_registrasi, waktu_kunjungan, no_rm, nama_pasien, poli, dokter, kode_icd, nama_diagnosis, keterangan_klinis) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([$no_registrasi, $waktu_kunjungan, $no_rm, $nama_pasien, $poli, $dokter, $kode_icd, $nama_diagnosis, $keterangan_klinis]);

            // 2. Update status antrean
            $stmtUpdate = $pdo->prepare("UPDATE antrean SET status_layanan = 'Dilayani' WHERE id = ?");
            $stmtUpdate->execute([$id_antrean]);

            // Commit transaksi jika semua berhasil
            $pdo->commit();

            echo "<script>alert('Rekam Medis berhasil disimpan!'); window.location='dashboard_dokter.php';</script>";
            exit;

        } catch (PDOException $e) {
            // Batalkan semua perubahan jika ada error
            $pdo->rollBack();
            $pesan = "<div class='alert alert-danger rounded-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Gagal menyimpan data: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/dokter/head.php'; ?>
</head>
<body>

<div class="d-flex min-vh-100">
    <!-- Include Sidebar Dokter -->
    <?php include '../layout/components/dokter/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        
        <!-- Include Topbar Dokter -->
        <?php 
        // Panggil profil untuk topbar
        function ambilProfilDokterInput($pdo) {
            $username = $_SESSION['username'] ?? '';
            $profil = ['nama' => $username, 'badge' => '', 'info' => '', 'poli' => '', 'foto_html' => ''];
            $stmt = $pdo->prepare("SELECT * FROM dokter WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $d = $stmt->fetch();
            if ($d) {
                $profil['nama']  = $d['nama_dokter'];
                $profil['badge'] = $d['poli'];
                $profil['poli']  = $d['poli'];
                $profil['info']  = 'SIP. ' . strtok(trim($d['nip_sip']), ' ');
                $av = trim($d['foto_avatar'] ?? '');
                $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($d['nama_dokter']) . '&background=0d6b5f&color=fff&bold=true';
                $profil['foto_html'] = '<img src="../assets/img/dokter/' . htmlspecialchars($av) . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;" onerror="this.onerror=null;this.src=\'' . $fallback . '\'">';
            }
            if ($profil['foto_html'] === '') {
                $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($profil['nama']) . '&background=0d6b5f&color=fff&bold=true';
                $profil['foto_html'] = '<img src="' . $fallback . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">';
            }
            return $profil;
        }
        $profil = ambilProfilDokterInput($pdo);
        include '../layout/components/dokter/topbar.php'; 
        ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Breadcrumb & Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div>
                    <nav aria-label="breadcrumb" class="mb-2">
                        <ol class="breadcrumb mb-0 fs-8">
                            <li class="breadcrumb-item"><a href="dashboard_dokter.php" class="text-decoration-none text-muted">Dashboard Pelayanan</a></li>
                            <li class="breadcrumb-item active text-lifora-primary fw-semibold" aria-current="page">Input Rekam Medis</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold text-dark mb-1">Form Pemeriksaan Medis</h2>
                    <p class="text-muted fs-7 mb-0">Lengkapi diagnosis ICD-10 dan catatan SOAP berdasarkan hasil anamnesis & pemeriksaan fisik.</p>
                </div>
                <a href="dashboard_dokter.php" class="btn btn-light border fw-semibold px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-2"></i> Kembali
                </a>
            </div>

            <!-- Alert Pesan -->
            <?= $pesan; ?>

            <!-- Form Input -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- Info Pasien (Readonly) -->
                    <div class="bg-lifora-blue-soft rounded-4 p-4 mb-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
                        <div class="bg-white text-lifora-primary-dark p-3 rounded-3 text-center shadow-sm" style="min-width: 100px;">
                            <div class="fs-9 fw-bold text-muted text-uppercase" style="letter-spacing: 0.05em;">No. Antrean</div>
                            <div class="fs-2 fw-bolder lh-1"><?= htmlspecialchars($data_antrean['no_antrean']); ?></div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fs-8 text-muted fw-bold text-uppercase mb-1">Data Pasien</div>
                            <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars($data_antrean['nama_pasien']); ?></h3>
                            <div class="d-flex flex-wrap gap-3 fs-7 text-dark fw-medium">
                                <span><i class="fa-regular fa-id-card text-muted me-1"></i> RM: <?= htmlspecialchars($data_antrean['no_rm']); ?></span>
                                <span><i class="fa-solid fa-hospital text-muted me-1"></i> <?= htmlspecialchars($data_antrean['poli_tujuan']); ?></span>
                                <span><i class="fa-solid fa-user-doctor text-muted me-1"></i> <?= htmlspecialchars($data_antrean['dokter']); ?></span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <!-- Hidden Inputs -->
                        <input type="hidden" name="no_rm" value="<?= htmlspecialchars($data_antrean['no_rm']); ?>">
                        <input type="hidden" name="nama_pasien" value="<?= htmlspecialchars($data_antrean['nama_pasien']); ?>">
                        <input type="hidden" name="poli_tujuan" value="<?= htmlspecialchars($data_antrean['poli_tujuan']); ?>">
                        <input type="hidden" name="dokter" value="<?= htmlspecialchars($data_antrean['dokter']); ?>">

                        <!-- Baris 1: Diagnosis -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fs-8 fw-bold text-dark">Kode ICD-10 <span class="text-danger">*</span></label>
                                <input type="text" name="kode_icd" class="form-control bg-light border-0 py-2" placeholder="Contoh: J06.9" required>
                                <div class="fs-9 text-muted mt-1">Gunakan kode standar WHO/SatuSehat.</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fs-8 fw-bold text-dark">Nama Diagnosis <span class="text-danger">*</span></label>
                                <input type="text" name="nama_diagnosis" class="form-control bg-light border-0 py-2" placeholder="Contoh: ISPA Akut" required>
                            </div>
                        </div>

                        <!-- Baris 2: SOAP -->
                        <div class="mb-4">
                            <label class="form-label fs-8 fw-bold text-dark">Keterangan Klinis (SOAP) <span class="text-danger">*</span></label>
                            <textarea name="keterangan_klinis" rows="6" class="form-control bg-light border-0 py-3" placeholder="Subjective (Keluhan)...&#10;Objective (Tanda Vital, Pemeriksaan Fisik)...&#10;Assessment (Diagnosis Kerja)...&#10;Plan (Terapi, Rujukan, Edukasi)..." required></textarea>
                            <div class="fs-9 text-muted mt-1">Tuliskan hasil pemeriksaan secara lengkap dan jelas untuk rekam medis elektronik.</div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="dashboard_dokter.php" class="btn btn-light fw-semibold px-4"><i class="fa-solid fa-xmark me-1"></i> Batal</a>
                            <button type="submit" name="simpan" class="btn bg-lifora-primary text-white fw-semibold px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Rekam Medis</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Include Footer & Scripts -->
<?php include '../layout/components/dokter/footer.php'; ?>

</body>
</html>