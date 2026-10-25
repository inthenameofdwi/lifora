<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

// Ambil Profil Dokter yang Login
function ambilProfilDokter($pdo) {
    $username = $_SESSION['username'] ?? '';
    $profil = ['nama' => $username ?: 'Dokter', 'badge' => '', 'info' => '', 'poli' => '', 'foto_html' => ''];
    try {
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
    } catch (Throwable $e) {}
    if ($profil['foto_html'] === '') {
        $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($profil['nama']) . '&background=0d6b5f&color=fff&bold=true';
        $profil['foto_html'] = '<img src="' . $fallback . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">';
    }
    return $profil;
}
$profil = ambilProfilDokter($pdo);

// Ambil Data Statistik Dinamis dari tabel antrean & rekam_medis
$namaDokter = $profil['nama'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM antrean WHERE dokter = ? AND status_layanan = 'Menunggu'");
$stmt->execute([$namaDokter]);
$totalMenunggu = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis WHERE dokter = ? AND DATE(waktu_kunjungan) = CURDATE()");
$stmt->execute([$namaDokter]);
$totalSelesai = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM antrean WHERE dokter = ? AND DATE(waktu_daftar) = CURDATE()");
$stmt->execute([$namaDokter]);
$totalKuota = $stmt->fetchColumn();

// Ambil Data Antrean Hari Ini
$stmt = $pdo->prepare("SELECT * FROM antrean WHERE dokter = ? AND DATE(waktu_daftar) = CURDATE() ORDER BY waktu_daftar ASC");
$stmt->execute([$namaDokter]);
$antreanList = $stmt->fetchAll();

// Ambil pasien yang sedang diperiksa (status Dilayani)
$pasienAktif = null;
foreach ($antreanList as $a) {
    if ($a['status_layanan'] === 'Dilayani') {
        $pasienAktif = $a;
        break;
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
    <!-- Include Sidebar -->
    <?php include '../layout/components/dokter/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        
        <!-- Include Topbar -->
        <?php include '../layout/components/dokter/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Header Judul -->
            <div>
                <h1 class="fw-bold text-dark mb-1">Selamat Bertugas, <?= htmlspecialchars($profil['nama']); ?></h1>
                <div class="d-flex align-items-center gap-2 fs-8 text-muted">
                    <span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill px-2 py-1"><i class="fa-solid fa-hospital-user me-1"></i> <?= htmlspecialchars($profil['poli'] ?? 'Poli Umum'); ?> • Ruang Periksa 1</span>
                    <span>Sesi Layanan Pagi • ID Jaga: RJ-2024-PU01 • Terhubung SATUSEHAT Kemenkes RI</span>
                </div>
            </div>

            <!-- 4 Statistik Ringkasan Antrean -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Antrean Menunggu</div>
                                <div class="fs-2 fw-bold text-dark"><?= $totalMenunggu; ?> <span class="fs-6 fw-normal text-muted">Pasien</span></div>
                                <div class="fs-9 text-danger fw-semibold mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> 2 Pasien Triage Prioritas (P2)</div>
                            </div>
                            <div class="bg-info bg-opacity-10 text-info rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-hourglass-half fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Selesai Diperiksa</div>
                                <div class="fs-2 fw-bold text-dark"><?= $totalSelesai; ?> <span class="fs-6 fw-normal text-muted">Pasien</span></div>
                                <div class="fs-9 text-lifora-primary fw-semibold mt-1"><i class="fa-solid fa-chart-line me-1"></i> Target Kuota Hari Ini</div>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-regular fa-circle-check fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Total Kunjungan</div>
                                <div class="fs-2 fw-bold text-dark"><?= $totalKuota; ?> <span class="fs-6 fw-normal text-muted">Pasien</span></div>
                            </div>
                            <div class="bg-teal-100 text-teal-600 rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #ccfbf1 !important; color: #0d9488 !important;">
                                <i class="fa-solid fa-stethoscope fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Rerata Konsultasi</div>
                                <div class="fs-2 fw-bold text-dark">9.5 <span class="fs-6 fw-normal text-muted">Mnt</span></div>
                                <div class="fs-9 text-lifora-primary fw-semibold mt-1"><i class="fa-solid fa-circle-check me-1"></i> Sesuai SOP (< 15 mnt)</div>
                            </div>
                            <div class="bg-indigo-100 text-indigo-600 rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: #e0e7ff !important; color: #4f46e5 !important;">
                                <i class="fa-regular fa-clock fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Pasien Sedang Dipanggil (Jika Ada) -->
            <?php if ($pasienAktif): ?>
            <div class="card border-0 shadow-lg rounded-4 text-white" style="background-color: var(--lifora-primary-dark);">
                <div class="card-body p-4 d-flex flex-column gap-3">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white text-lifora-primary-dark p-3 rounded-4 text-center" style="min-width: 100px;">
                                <div class="fs-9 fw-bold text-muted text-uppercase" style="letter-spacing: 0.05em;">No. Antrean</div>
                                <div class="fs-1 fw-bolder lh-1"><?= htmlspecialchars($pasienAktif['no_antrean']); ?></div>
                            </div>
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h2 class="fs-3 fw-bold mb-0"><?= htmlspecialchars($pasienAktif['nama_pasien']); ?></h2>
                                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 fs-9">BPJS Kesehatan</span>
                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1 fs-9"><i class="fa-solid fa-triangle-exclamation me-1"></i> Alergi Penisilin</span>
                                </div>
                                <div class="fs-8 opacity-75 mt-1 d-flex flex-wrap gap-2">
                                    <span>No. RM: <?= htmlspecialchars($pasienAktif['no_rm']); ?></span>
                                    <span>• Laki-laki, 68 Tahun</span>
                                </div>
                                <div class="fs-8 text-lifora-primary-soft mt-1">Kunjungan Berulang (Hipertensi Kronis)</div>
                            </div>
                        </div>
                        <div class="bg-black bg-opacity-25 px-3 py-2 rounded-4 border border-white border-opacity-25 d-flex align-items-center gap-2">
                            <i class="fa-regular fa-clock text-lifora-primary-soft fs-4"></i>
                            <div>
                                <div class="fs-9 opacity-75 fw-bold text-uppercase">Durasi</div>
                                <div class="fs-5 fw-bold" style="font-family: monospace;">06:42</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tanda Vital -->
                    <div class="row g-2 bg-white bg-opacity-10 p-3 rounded-3 border border-white border-opacity-10 fs-8">
                        <div class="col-md-3">
                            <div class="fs-9 opacity-75 text-uppercase">Keluhan Utama</div>
                            <div class="fw-semibold text-white mt-1">Pusing berputar, tengkuk kaku sejak 2 hari</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-9 opacity-75 text-uppercase">Tekanan Darah</div>
                            <div class="fw-bold text-danger mt-1">165/95 mmHg (Tinggi)</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-9 opacity-75 text-uppercase">Nadi & Suhu</div>
                            <div class="fw-semibold text-white mt-1">88 bpm • 36.8 °C</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-9 opacity-75 text-uppercase">Gula Darah Sewaktu</div>
                            <div class="fw-semibold text-white mt-1">142 mg/dL (Normal)</div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 pt-1">
                        <button class="btn btn-success fw-bold rounded-3 px-3 py-2 fs-8"><i class="fa-solid fa-file-signature me-2"></i> Lanjutkan SOAP</button>
                        <button class="btn btn-outline-light rounded-3 px-3 py-2 fs-8"><i class="fa-regular fa-folder-open me-2"></i> Riwayat Medis</button>
                        <button class="btn btn-outline-light rounded-3 px-3 py-2 fs-8"><i class="fa-solid fa-vial me-2"></i> Rujuk Lab</button>
                        <button class="btn btn-info text-white fw-bold rounded-3 px-3 py-2 fs-8 ms-auto"><i class="fa-solid fa-bullhorn me-2"></i> Panggil Berikutnya</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Grid Tabel Antrean & Sidebar Kanan -->
            <div class="row g-4">
                
                <!-- Tabel Antrean Poli (Kiri) -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-3 gap-2">
                                <div>
                                    <h3 class="fw-bold text-dark fs-6 mb-1">Daftar Antrean Poli Hari Ini</h3>
                                    <p class="fs-9 text-muted mb-0">Prioritas berdasarkan Triage Perawat & Waktu Check-in</p>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-9 text-muted fw-semibold">Filter Triage:</span>
                                    <button class="btn btn-sm bg-lifora-primary-dark text-white rounded-pill px-3 py-1 fs-9 fw-bold">Semua</button>
                                    <button class="btn btn-sm bg-light text-dark rounded-pill px-3 py-1 fs-9 fw-medium border">P2 Urgent</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="min-width: 700px;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="fs-9 fw-bold text-muted text-uppercase py-3">No. Antrean</th>
                                            <th class="fs-9 fw-bold text-muted text-uppercase py-3">No. RM</th>
                                            <th class="fs-9 fw-bold text-muted text-uppercase py-3">Nama Pasien</th>
                                            <th class="fs-9 fw-bold text-muted text-uppercase py-3">Keluhan Utama</th>
                                            <th class="fs-9 fw-bold text-muted text-uppercase py-3">Status</th>
                                            <th class="fs-9 fw-bold text-muted text-uppercase py-3 text-center">Tindakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($antreanList) > 0): ?>
                                            <?php foreach ($antreanList as $a): 
                                                $isDilayani = $a['status_layanan'] === 'Dilayani';
                                                $isPrioritas = false; // Anda bisa menambahkan logika prioritas dari DB jika ada
                                            ?>
                                            <tr class="<?= $isDilayani ? 'table-success bg-opacity-10' : ''; ?>">
                                                <td class="fw-bold <?= $isDilayani ? 'text-lifora-primary-dark' : 'text-muted'; ?> fs-7"><?= htmlspecialchars($a['no_antrean']); ?></td>
                                                <td class="fs-8 text-muted"><?= htmlspecialchars($a['no_rm']); ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark fs-7"><?= htmlspecialchars($a['nama_pasien']); ?></div>
                                                    <div class="fs-9 text-muted">Laki-laki, 45 Th</div>
                                                </td>
                                                <td class="fs-8 text-dark">Keluhan utama pasien...</td>
                                                <td>
                                                    <?php if ($isDilayani): ?>
                                                        <span class="badge bg-lifora-primary-dark text-white rounded-pill px-2 py-1 fs-9">Sedang Diperiksa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info bg-opacity-25 text-info rounded-pill px-2 py-1 fs-9">Menunggu</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm <?= $isDilayani ? 'bg-lifora-primary-dark text-white' : 'btn-light border text-dark'; ?> fw-bold rounded-2 fs-9 px-3">
                                                        <?= $isDilayani ? 'Periksa' : 'Panggil'; ?>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center py-4 text-muted fs-7">Belum ada antrean untuk hari ini.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Kanan (Peringatan & Pengingat) -->
                <div class="col-lg-4 d-flex flex-column gap-4">
                    
                    <!-- Peringatan Alergi -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h3 class="fw-bold text-dark fs-7 mb-0 d-flex align-items-center gap-1"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Peringatan Alergi Pasien</h3>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 fs-9">Poli Umum</span>
                            </div>
                            <p class="fs-9 text-muted mb-3">Konfirmasi riwayat reaksi hipersensitivitas obat berikut sebelum meresepkan antibiotik dan analgesik:</p>
                            
                            <div class="d-flex flex-column gap-2">
                                <div class="bg-light rounded-3 p-2 border">
                                    <div class="d-flex justify-content-between fw-bold text-dark fs-8">
                                        <span>Golongan Penisilin</span>
                                        <span class="text-danger fs-9">12 Pasien</span>
                                    </div>
                                    <p class="fs-9 text-muted mb-0 mt-1">Amoxicillin, Ampicillin • Alternatif: Azitromisin</p>
                                </div>
                                <div class="bg-light rounded-3 p-2 border">
                                    <div class="d-flex justify-content-between fw-bold text-dark fs-8">
                                        <span>NSAIDs</span>
                                        <span class="text-danger fs-9">8 Pasien</span>
                                    </div>
                                    <p class="fs-9 text-muted mb-0 mt-1">Asam Mefenamat, Diklofenak • Waspadai dispepsia</p>
                                </div>
                                <div class="bg-light rounded-3 p-2 border">
                                    <div class="d-flex justify-content-between fw-bold text-dark fs-8">
                                        <span>Sulfonamida</span>
                                        <span class="text-danger fs-9">5 Pasien</span>
                                    </div>
                                    <p class="fs-9 text-muted mb-0 mt-1">Kotrimoksazol • Risiko Stevens-Johnson</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengingat Medis -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h3 class="fw-bold text-dark fs-7 border-bottom pb-2 mb-3 d-flex align-items-center gap-1"><i class="fa-regular fa-bell text-lifora-primary"></i> Pengingat Medis Hari Ini</h3>
                            <div class="d-flex flex-column gap-2">
                                <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 p-2 d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-notes-medical text-primary mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-primary fs-9">Jadwal Skrining HbA1c</div>
                                        <p class="fs-9 text-muted mb-0 mt-1">Pasien DM Tipe 2 kunjungan berulang wajib ditawarkan evaluasi 3 bulanan.</p>
                                    </div>
                                </div>
                                <div class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded-3 p-2 d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-syringe text-success mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-success fs-9">Ketersediaan Vaksin Tetanus</div>
                                        <p class="fs-9 text-muted mb-0 mt-1">Stok tersisa 6 vial di lemari pendingin farmasi sentral.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<!-- Include Footer & Scripts -->
<?php include '../layout/components/dokter/footer.php'; ?>

</body>
</html>