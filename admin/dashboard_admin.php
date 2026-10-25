<?php
session_start();
// Pastikan path koneksi benar sesuai struktur folder Anda
require_once '../koneksi.php'; 

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

// --- 1. QUERY STATISTIK ---
// Total Kunjungan Hari Ini (dari tabel antrean)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM antrean WHERE DATE(waktu_daftar) = CURDATE()");
$stmt->execute();
$totalKunjungan = $stmt->fetchColumn();
// Fallback jika data hari ini kosong di database, gunakan total semua antrean untuk demo
if ($totalKunjungan == 0) {
    $totalKunjungan = $pdo->query("SELECT COUNT(*) FROM antrean")->fetchColumn();
}
$targetHarian = 160;
$persenKunjungan = round(($totalKunjungan / $targetHarian) * 100, 1);

// Antrean Aktif (Menunggu / Dilayani)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM antrean WHERE status_layanan IN ('Menunggu', 'Dilayani')");
$stmt->execute();
$antreanAktif = $stmt->fetchColumn();

// Rekam Medis Selesai (dari tabel rekam_medis)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis WHERE status_berkas IN ('Lengkap & Terkunci', 'Selesai Ditandatangani')");
$stmt->execute();
$rmSelesai = $stmt->fetchColumn();
$totalRM = $pdo->query("SELECT COUNT(*) FROM rekam_medis")->fetchColumn();
$persenRM = $totalRM > 0 ? round(($rmSelesai / $totalRM) * 100, 1) : 0;

// Dokter Aktif Bertugas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM dokter WHERE status_tugas = 'Aktif Bertugas'");
$stmt->execute();
$dokterAktif = $stmt->fetchColumn();


// --- 2. QUERY TABEL ANTREAN (5 Terbaru) ---
$stmt = $pdo->query("SELECT * FROM antrean ORDER BY waktu_daftar DESC LIMIT 5");
$antreanList = $stmt->fetchAll();

// --- 3. QUERY TENAGA MEDIS (Bilik Aktif) ---
// Mengambil dokter yang aktif atau sedang cuti (untuk ditampilkan statusnya)
$stmt = $pdo->query("SELECT * FROM dokter WHERE status_tugas IN ('Aktif Bertugas', 'Sedang Cuti') LIMIT 4");
$tenagaMedis = $stmt->fetchAll();

// --- 4. QUERY LOG AKTIVITAS ---
$stmt = $pdo->query("SELECT * FROM log_aktivitas ORDER BY id DESC LIMIT 4");
$logs = $stmt->fetchAll();

// --- 5. DISTRIBUSI PASIEN PER POLI (Berdasarkan tabel antrean) ---
$stmt = $pdo->query("SELECT poli_tujuan as poli, COUNT(*) as jumlah FROM antrean GROUP BY poli_tujuan ORDER BY jumlah DESC");
$distribusiRaw = $stmt->fetchAll();

$warnaPoli = ['Poli Umum' => '#00796b', 'Poli Gigi' => '#0b6e99', 'Poli KIA' => '#2f9e8f', 'Poli Lansia' => '#6fd6c8'];
$distribusi = [];
$totalDistribusi = array_sum(array_column($distribusiRaw, 'jumlah'));
$donatSegments = [];
$currentPercent = 0;

foreach ($distribusiRaw as $d) {
    $persen = $totalDistribusi > 0 ? round(($d['jumlah'] / $totalDistribusi) * 100, 1) : 0;
    $warna = $warnaPoli[$d['poli']] ?? '#64748b'; // default slate
    $distribusi[] = [
        'poli' => $d['poli'],
        'jumlah' => $d['jumlah'],
        'persen' => $persen,
        'warna' => $warna
    ];
    
    // Bangun conic-gradient dinamis
    $nextPercent = $currentPercent + $persen;
    $donatSegments[] = "$warna {$currentPercent}% {$nextPercent}%";
    $currentPercent = $nextPercent;
}
// Tambahkan sisa track jika total < 100
if ($currentPercent < 100) {
    $donatSegments[] = "#dbe7fb {$currentPercent}% 100%";
}
$donat = 'conic-gradient(' . implode(', ', $donatSegments) . ')';

// Fungsi Foto
function foto($file, $nama, $warna = '0d6b5f') {
    // Jika foto_avatar kosong, gunakan fallback ui-avatars
    $src = !empty($file) ? "../assets/img/dokter/" . htmlspecialchars($file) : "https://ui-avatars.com/api/?name=" . urlencode($nama) . "&background=" . $warna . "&color=fff&bold=true";
    return '<img src="' . $src . '" alt="' . htmlspecialchars($nama) . '" class="rounded-circle border" style="width:48px;height:48px;object-fit:cover;" onerror="this.onerror=null;this.src=\'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=' . $warna . '&color=fff&bold=true\'">';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/admin/head.php'; ?>
</head>
<body>

<div class="d-flex min-vh-100">
    <?php include '../layout/components/admin/sidebar.php'; ?>

    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <?php include '../layout/components/admin/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Hero Section -->
            <div class="card border-0 shadow-sm hero-card rounded-4">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2 fs-7 text-muted">
                            <span class="badge bg-lifora-blue-soft text-lifora-blue fw-bold px-2 py-1">PUSAT KENDALI FASKES</span> 
                            <span class="bg-secondary rounded-circle d-inline-block" style="width: 4px; height: 4px;"></span> 
                            Sinkronisasi SIMPUS Terpadu v2.4
                        </div>
                        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin'); ?></h1>
                        <p class="text-muted mb-0 fs-6" style="max-width: 560px; line-height: 1.5;">
                            Pantau antrean loket, ketersediaan bilik poli, verifikasi diagnosis ICD-10, serta pembaruan data rekam medis Puskesmas Melati Sejahtera secara realtime.
                        </p>
                    </div>
                    <div class="d-flex flex-column gap-2 flex-shrink-0">
                        <a href="tambah_dokter.php" class="btn btn-light border fw-semibold"><i class="fa-solid fa-user-plus me-2"></i> Tambah Data Dokter</a>
                        <a href="pendaftaran_pasien.php" class="btn bg-lifora-primary text-white fw-semibold shadow-sm"><i class="fa-solid fa-plus me-2"></i> Daftarkan Pasien Baru</a>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <!-- Stat 1 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-users fs-5"></i>
                                </div>
                                <span class="badge bg-lifora-mint-bg text-lifora-primary-dark rounded-pill fw-semibold"><i class="fa-solid fa-arrow-trend-up me-1"></i> +12%</span>
                            </div>
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Total Kunjungan Hari Ini</div>
                            <div class="fs-2 fw-bold text-dark mb-3"><?= $totalKunjungan; ?> <span class="fs-6 fw-normal text-muted">Pasien</span></div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-lifora-primary-dark" style="width: <?= $persenKunjungan; ?>%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted fs-8 fw-medium">
                                <span>Target: <?= $targetHarian; ?></span><span class="fw-bold text-dark"><?= $persenKunjungan; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 2 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-hourglass-half fs-5"></i>
                                </div>
                                <span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill fw-semibold"><span class="bg-lifora-blue rounded-circle d-inline-block me-1" style="width: 6px; height: 6px;"></span> Loket & Poli</span>
                            </div>
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Antrean Pelayanan Aktif</div>
                            <div class="fs-2 fw-bold text-dark mb-3"><?= $antreanAktif; ?> <span class="fs-6 fw-normal text-muted">Menunggu</span></div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-lifora-blue" style="width: 35%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted fs-8 fw-medium">
                                <span>Rata-rata tunggu</span><span class="fw-bold text-dark">14 Menit</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 3 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-regular fa-circle-check fs-5"></i>
                                </div>
                                <span class="badge bg-lifora-mint-bg text-lifora-primary-dark rounded-pill fw-semibold">Tervalidasi</span>
                            </div>
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Rekam Medis Selesai</div>
                            <div class="fs-2 fw-bold text-dark mb-3"><?= $rmSelesai; ?> <span class="fs-6 fw-normal text-muted">RM Arsip</span></div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-lifora-primary-dark" style="width: <?= $persenRM; ?>%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted fs-8 fw-medium">
                                <span>Rasio penyelesaian</span><span class="fw-bold text-dark"><?= $persenRM; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 4 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-file-medical fs-5"></i>
                                </div>
                                <span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill fw-semibold">4 Poli Buka</span>
                            </div>
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Dokter Jaga Praktik</div>
                            <div class="fs-2 fw-bold text-dark mb-3"><?= $dokterAktif; ?> <span class="fs-6 fw-normal text-muted">Dokter Siaga</span></div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar bg-lifora-primary-dark" style="width: 100%;"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted fs-8 fw-medium">
                                <span>Kapasitas poli</span><span class="fw-bold text-dark">100%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribusi & Antrean Row -->
            <div class="row g-3">
                <!-- Distribusi -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">Distribusi Pasien per Poli</h5>
                                    <p class="text-muted fs-8 mb-0">Beban pelayanan kumulatif sesi pagi</p>
                                </div>
                                <button class="btn btn-sm bg-lifora-blue-soft text-lifora-primary rounded"><i class="fa-solid fa-chart-pie"></i></button>
                            </div>
                            
                            <div class="donut-chart mb-4" style="background: <?= $donat; ?>;">
                                <div class="donut-inner">
                                    <strong class="fs-2 text-dark lh-1"><?= $totalDistribusi; ?></strong>
                                    <span class="fs-9 text-muted fw-semibold mt-1">TOTAL REGISTRASI</span>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($distribusi as $d): ?>
                                <div class="bg-lifora-blue-soft rounded p-2 px-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 fs-7">
                                        <span class="fw-medium d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background: <?= $d['warna']; ?>;"></span>
                                            <?= htmlspecialchars($d['poli']); ?>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= $d['jumlah']; ?> <span class="fw-normal text-muted fs-8">(<?= $d['persen']; ?>%)</span></span>
                                    </div>
                                    <div class="progress bg-white" style="height: 4px;">
                                        <div class="progress-bar" style="width: <?= $d['persen']; ?>%; background: <?= $d['warna']; ?>;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if(empty($distribusi)): ?>
                                    <div class="text-center text-muted fs-7 py-3">Belum ada data antrean.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Antrean Table -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">
                                        Tabel Antrean Terkini 
                                        <span class="badge bg-lifora-blue-soft text-muted fs-9 align-middle ms-1">Real-Time</span>
                                    </h5>
                                    <p class="text-muted fs-8 mb-0">Monitoring alur pasien dari ruang tunggu ke meja dokter</p>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm bg-lifora-blue-soft text-lifora-primary rounded"><i class="fa-solid fa-filter"></i></button>
                                    <button class="btn btn-sm bg-lifora-blue-soft text-lifora-primary rounded"><i class="fa-solid fa-arrows-rotate"></i></button>
                                </div>
                            </div>

                            <div class="table-responsive flex-grow-1">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="bg-lifora-blue-soft" style="border-radius: 8px;">
                                        <tr>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold rounded-start py-2">No. Antrean</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold py-2">Pasien & No. RM</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold py-2">Poli Tujuan</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold py-2">Dokter</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold rounded-end py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($antreanList as $a): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $a['status_layanan'] === 'Selesai' ? 'bg-lifora-primary-soft text-lifora-primary-dark' : 'bg-lifora-blue-badge text-dark'; ?> fs-6 rounded px-2 py-1">
                                                    <?= htmlspecialchars($a['no_antrean']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark fs-7"><?= htmlspecialchars($a['nama_pasien']); ?></div>
                                                <small class="text-muted fs-8"><?= htmlspecialchars($a['no_rm']); ?></small>
                                            </td>
                                            <td class="text-muted fs-7"><?= htmlspecialchars($a['poli_tujuan']); ?></td>
                                            <td class="text-muted fs-7"><?= htmlspecialchars($a['dokter']); ?></td>
                                            <td>
                                                <?php if ($a['status_layanan'] === 'Dilayani'): ?>
                                                    <span class="badge bg-lifora-blue-badge text-lifora-blue rounded-pill px-2 py-1 fs-8"><i class="fa-solid fa-circle fs-9 me-1"></i> Dilayani</span>
                                                <?php elseif ($a['status_layanan'] === 'Menunggu'): ?>
                                                    <span class="badge bg-danger text-white rounded-pill px-2 py-1 fs-8"><i class="fa-solid fa-circle fs-9 me-1"></i> Menunggu</span>
                                                <?php else: ?>
                                                    <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 28px; height: 28px;"><i class="fa-solid fa-check"></i></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if(empty($antreanList)): ?>
                                            <tr><td colspan="5" class="text-center text-muted fs-7 py-3">Belum ada antrean hari ini.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top text-muted fs-8 fw-medium">
                                <span>Menampilkan <?= count($antreanList); ?> antrean terbaru</span>
                                <a href="kelola_antrean.php" class="text-lifora-primary text-decoration-none fw-bold fs-7">Buka Antrean Penuh <i class="fa-solid fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tenaga Medis & Log Row -->
            <div class="row g-3">
                <!-- Tenaga Medis -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">Status Bilik & Tenaga Medis Aktif</h5>
                                    <p class="text-muted fs-8 mb-0">Jadwal jaga Shift Pagi (08:00 - 14:00 WIB)</p>
                                </div>
                                <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-3 py-2 fs-8">Data dari Database</span>
                            </div>

                            <div class="row g-2">
                                <?php foreach ($tenagaMedis as $t): ?>
                                <div class="col-md-6">
                                    <div class="bg-lifora-blue-soft rounded-3 p-3 d-flex align-items-center gap-3 h-100">
                                        <div class="position-relative flex-shrink-0">
                                            <?= foto($t['foto_avatar'], $t['nama_dokter']); ?>
                                            <span class="position-absolute bottom-0 end-0 border border-2 border-white rounded-circle" 
                                                  style="width: 12px; height: 12px; background: <?= $t['status_tugas'] === 'Aktif Bertugas' ? 'var(--lifora-primary)' : ($t['status_tugas'] === 'Sedang Cuti' ? '#f59e0b' : '#94a3b8'); ?>;">
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-7 text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($t['nama_dokter']); ?>"><?= htmlspecialchars($t['nama_dokter']); ?></div>
                                            <div class="fs-8 fw-semibold mb-1 text-lifora-primary"><?= htmlspecialchars($t['poli']); ?></div>
                                            <div class="d-flex align-items-center gap-2 text-muted fs-8">
                                                <i class="fa-solid fa-user-group"></i> <?= $t['kuota_terisi']; ?>/<?= $t['kuota_harian']; ?> Kuota
                                                <span class="bg-secondary rounded-circle d-inline-block" style="width: 3px; height: 3px;"></span>
                                                <span class="fw-semibold" style="color: <?= $t['status_tugas'] === 'Aktif Bertugas' ? 'var(--lifora-primary-dark)' : '#b45309'; ?>;">
                                                    <?= htmlspecialchars($t['status_tugas']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if(empty($tenagaMedis)): ?>
                                    <div class="col-12 text-center text-muted fs-7 py-3">Belum ada data dokter.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log Aktivitas -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-lifora-primary me-2"></i> Log Aktivitas RM</h5>
                                <span class="text-muted fs-8 fw-medium">Realtime Stream</span>
                            </div>

                            <div class="timeline flex-grow-1">
                                <?php 
                                $logColors = [
                                    'soap' => 'var(--lifora-primary-dark)',
                                    'registrasi' => 'var(--lifora-blue-text)',
                                    'resep' => 'var(--lifora-primary-dark)',
                                    'klaim' => '#94a3b8'
                                ];
                                foreach ($logs as $log): 
                                    $dotColor = $logColors[$log['tipe_log']] ?? '#cbd5e1';
                                ?>
                                <div class="timeline-item" style="--dot-color: <?= $dotColor; ?>;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark fs-7"><?= htmlspecialchars($log['judul']); ?></span>
                                        <span class="text-muted fs-8"><?= htmlspecialchars($log['waktu']); ?></span>
                                    </div>
                                    <p class="text-muted fs-8 mb-0 lh-sm"><?= htmlspecialchars($log['deskripsi']); ?></p>
                                </div>
                                <?php endforeach; ?>
                                <?php if(empty($logs)): ?>
                                    <div class="text-center text-muted fs-7 py-3">Belum ada log aktivitas.</div>
                                <?php endif; ?>
                            </div>

                            <a href="log_aktivitas.php" class="btn bg-lifora-blue-soft text-dark fw-bold w-100 mt-3 py-2 rounded-3 fs-7">
                                Lihat Seluruh Riwayat Audit Trail <i class="fa-solid fa-angle-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- End Container -->
    </main>
</div>

<?php include '../layout/components/admin/footer.php'; ?>

<style>
    .timeline-item::before { background-color: var(--dot-color, #cbd5e1); }
</style>
</body>
</html>