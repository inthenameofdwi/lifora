<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

// Filter Tanggal (Default: Bulan Ini)
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

// 1. Statistik Ringkasan (Pendapatan, Pasien, Antrean, RM)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_biaya), 0) FROM pembayaran WHERE status_pembayaran = 'Lunas' AND DATE(waktu_bayar) BETWEEN ? AND ?");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$totalPendapatan = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pasien WHERE DATE(kunjungan_terakhir) BETWEEN ? AND ?");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$totalPasien = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM antrean WHERE DATE(waktu_daftar) BETWEEN ? AND ?");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$totalAntrean = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis WHERE DATE(waktu_kunjungan) BETWEEN ? AND ?");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$totalRM = $stmt->fetchColumn();

// 2. Data Kunjungan per Poli (Untuk Progress Bar)
$stmt = $pdo->prepare("SELECT poli_tujuan AS poli, COUNT(*) AS jumlah FROM antrean WHERE DATE(waktu_daftar) BETWEEN ? AND ? GROUP BY poli_tujuan ORDER BY jumlah DESC");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$kunjunganPoli = $stmt->fetchAll();
$maxPoli = count($kunjunganPoli) > 0 ? $kunjunganPoli[0]['jumlah'] : 1; // Untuk skala bar

// 3. Top 5 Diagnosis (ICD-10)
$stmt = $pdo->prepare("SELECT kode_icd, nama_diagnosis, COUNT(*) AS jumlah FROM rekam_medis WHERE DATE(waktu_kunjungan) BETWEEN ? AND ? GROUP BY kode_icd, nama_diagnosis ORDER BY jumlah DESC LIMIT 5");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$topDiagnosis = $stmt->fetchAll();

// 4. Riwayat Transaksi Terakhir
$stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE DATE(waktu_bayar) BETWEEN ? AND ? ORDER BY waktu_bayar DESC LIMIT 5");
$stmt->execute([$tgl_mulai, $tgl_akhir]);
$transaksiTerakhir = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/admin/head.php'; ?>
</head>
<body>

<div class="d-flex min-vh-100">
    <!-- Include Sidebar -->
    <?php include '../layout/components/admin/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        
        <!-- Include Topbar -->
        <?php include '../layout/components/admin/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Header Page -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Laporan & Statistik</h2>
                    <p class="text-muted fs-7 mb-0">Ringkasan performa faskes, kunjungan, dan pendapatan berdasarkan periode.</p>
                </div>
                <!-- Form Filter Tanggal -->
                <form method="GET" class="d-flex flex-wrap align-items-center gap-2 bg-white p-2 rounded-3 border shadow-sm">
                    <div class="d-flex align-items-center gap-1">
                        <span class="fs-8 fw-semibold text-muted ms-1">Dari</span>
                        <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tgl_mulai); ?>" class="form-control form-control-sm border-0 bg-light">
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="fs-8 fw-semibold text-muted">s/d</span>
                        <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir); ?>" class="form-control form-control-sm border-0 bg-light">
                    </div>
                    <button type="submit" class="btn btn-sm bg-lifora-primary text-white fw-semibold px-3"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
                </form>
            </div>

            <!-- Stats Row -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <!-- Stat 1: Pendapatan -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-1">Total Pendapatan</div>
                                <div class="fs-2 fw-bold text-lifora-primary-dark mb-1">Rp <?= number_format($totalPendapatan, 0, ',', '.'); ?></div>
                                <div class="fs-9 text-muted fw-semibold">Dari pembayaran lunas</div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-money-bill-wave fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 2: Pasien -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-1">Kunjungan Pasien</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= $totalPasien; ?></div>
                                <div class="fs-9 text-muted fw-semibold">Pasien terdaftar baru</div>
                            </div>
                            <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-users fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 3: Antrean -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-1">Total Antrean</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= $totalAntrean; ?></div>
                                <div class="fs-9 text-muted fw-semibold">Pendaftaran poli</div>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-ticket fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 4: RM -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-8 fw-semibold text-muted text-uppercase mb-1">Rekam Medis Selesai</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= $totalRM; ?></div>
                                <div class="fs-9 text-muted fw-semibold">Berkas RM diinput</div>
                            </div>
                            <div class="bg-info bg-opacity-10 text-info rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-regular fa-file-lines fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Laporan -->
            <div class="row g-4">
                <!-- Kolom Kiri: Kunjungan per Poli -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-hospital text-lifora-primary me-2"></i> Distribusi Kunjungan per Poli</h5>
                            
                            <div class="d-flex flex-column gap-3">
                                <?php if (count($kunjunganPoli) > 0): foreach ($kunjunganPoli as $kp): 
                                    $persen = round(($kp['jumlah'] / $totalAntrean) * 100, 1);
                                    $width = ($kp['jumlah'] / $maxPoli) * 100;
                                ?>
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1 fs-7 fw-semibold">
                                        <span class="text-dark"><?= htmlspecialchars($kp['poli']); ?></span>
                                        <span class="text-muted"><?= $kp['jumlah']; ?> Pasien (<?= $persen; ?>%)</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-lifora-primary" role="progressbar" style="width: <?= $width; ?>%; border-radius: 4px;"></div>
                                    </div>
                                </div>
                                <?php endforeach; else: ?>
                                    <div class="text-center py-4 text-muted fs-7">Belum ada data kunjungan pada periode ini.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Top Diagnosis -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-stethoscope text-lifora-blue me-2"></i> 5 Diagnosis Terbanyak (ICD-10)</h5>
                            
                            <div class="d-flex flex-column gap-2">
                                <?php if (count($topDiagnosis) > 0): foreach ($topDiagnosis as $idx => $td): ?>
                                <div class="d-flex align-items-center gap-3 p-2 rounded-3 bg-light border">
                                    <div class="bg-white text-lifora-primary-dark fw-bold rounded-circle d-flex align-items-center justify-content-center border" style="width: 32px; height: 32px; font-size: 12px;">
                                        #<?= $idx + 1; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark fs-7"><?= htmlspecialchars($td['nama_diagnosis']); ?></div>
                                        <div class="fs-8 text-muted"><span class="badge bg-lifora-blue-badge text-dark me-1"><?= htmlspecialchars($td['kode_icd']); ?></span> Kode ICD-10</div>
                                    </div>
                                    <div class="fw-bold text-lifora-primary-dark fs-6"><?= $td['jumlah']; ?> <span class="fs-8 fw-normal text-muted">kasus</span></div>
                                </div>
                                <?php endforeach; else: ?>
                                    <div class="text-center py-4 text-muted fs-7">Belum ada data diagnosis pada periode ini.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Transaksi Terakhir -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt text-lifora-primary me-2"></i> Riwayat Transaksi Pembayaran Terakhir</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3 rounded-start">No Registrasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Total Biaya</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Metode</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 rounded-end pe-3">Waktu Bayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($transaksiTerakhir) > 0): foreach ($transaksiTerakhir as $tr): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-dark fs-7"><?= htmlspecialchars($tr['no_registrasi']); ?></td>
                                    <td class="fw-semibold text-lifora-primary-dark fs-7">Rp <?= number_format($tr['total_biaya'], 0, ',', '.'); ?></td>
                                    <td><span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill px-2 py-1 fs-8"><?= htmlspecialchars($tr['metode_pembayaran']); ?></span></td>
                                    <td class="fs-8 text-muted"><?= date('d/m/Y H:i', strtotime($tr['waktu_bayar'])); ?> WIB</td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted fs-7">Belum ada transaksi lunas pada periode ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- End Container -->
    </main>
</div>

<!-- Include Footer & Scripts -->
<?php include '../layout/components/admin/footer.php'; ?>

</body>
</html>