<?php

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

// Fitur Pencarian & Filter
$search          = isset($_GET['search'])   ? trim($_GET['search']) : '';
$jaminan_filter  = isset($_GET['jaminan'])  ? trim($_GET['jaminan']) : '';
$kategori_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

// Perbaikan: Gunakan placeholder berbeda untuk setiap LIKE
$query = "SELECT * FROM pasien WHERE (nama_lengkap LIKE :search1 OR no_rm LIKE :search2 OR nik_identitas LIKE :search3)";
$params = [
    ':search1' => "%$search%",
    ':search2' => "%$search%",
    ':search3' => "%$search%"
];

if (!empty($jaminan_filter)) {
    $query .= " AND jenis_jaminan = :jaminan";
    $params[':jaminan'] = $jaminan_filter;
}
if (!empty($kategori_filter)) {
    $query .= " AND kategori_usia = :kategori";
    $params[':kategori'] = $kategori_filter;
}

$query .= " ORDER BY id ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pasienList = $stmt->fetchAll();
$jumlahTampil = count($pasienList);


// Hitung Statistik Dinamis
$totalPasien = $pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
$totalBpjs = $pdo->query("SELECT COUNT(*) FROM pasien WHERE jenis_jaminan = 'BPJS Kesehatan'")->fetchColumn();
$totalLansia = $pdo->query("SELECT COUNT(*) FROM pasien WHERE kategori_usia = 'Lansia'")->fetchColumn();
$totalAntrean = $pdo->query("SELECT COUNT(*) FROM antrean WHERE DATE(waktu_daftar) = CURDATE()")->fetchColumn();
// Fallback untuk demo jika data hari ini kosong
if ($totalAntrean == 0) { $totalAntrean = $pdo->query("SELECT COUNT(*) FROM antrean")->fetchColumn(); }

$persenBpjs = $totalPasien > 0 ? round(($totalBpjs / $totalPasien) * 100, 1) : 0;

// Fungsi Foto Profil Topbar
function foto($file, $nama, $warna = '0d6b5f') {
    $fallback = 'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=' . $warna . '&color=fff&bold=true';
    return '<img src="../assets/img/dokter/' . htmlspecialchars($file) . '" alt="' . htmlspecialchars($nama) . '" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;" onerror="this.onerror=null;this.src=\'' . $fallback . '\'">';
}
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
            
            <!-- Hero Section -->
            <div class="card border-0 shadow-sm hero-card rounded-4">
                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2 fs-8 fw-bold text-lifora-primary text-uppercase" style="letter-spacing: 0.03em;">
                            <i class="fa-regular fa-id-card"></i> Master Registrasi Pasien
                        </div>
                        <h1 class="fw-bold text-dark mb-2" style="letter-spacing: -0.02em;">Kelola Data Pasien Puskesmas</h1>
                        <p class="text-muted mb-0 fs-6" style="max-width: 560px; line-height: 1.5;">
                            Daftar seluruh pasien terdaftar, riwayat demografis, dan status kepesertaan jaminan kesehatan nasional maupun mandiri.
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="pendaftaran_pasien.php" class="btn bg-lifora-primary text-white fw-semibold shadow-sm py-2 px-3">
                            <i class="fa-solid fa-user-plus me-2"></i> Tambah Pasien Baru
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                <!-- Stat 1 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">Total Basis Pasien</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= number_format($totalPasien, 0, ',', '.'); ?></div>
                                <div class="text-lifora-primary fs-8 fw-semibold"><i class="fa-solid fa-arrow-trend-up me-1"></i> Terdaftar</div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-regular fa-id-card fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 2 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">Peserta BPJS Aktif</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= number_format($totalBpjs, 0, ',', '.'); ?></div>
                                <div class="text-lifora-blue fs-8 fw-semibold"><?= $persenBpjs; ?>% cakupan faskes</div>
                            </div>
                            <div class="bg-lifora-blue-badge text-lifora-blue rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-shield-halved fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 3 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">Pasien Kategori Lansia</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= number_format($totalLansia, 0, ',', '.'); ?></div>
                                <div class="text-lifora-primary fs-8 fw-semibold">Prioritas Triage P2/P3</div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-person-walking fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Stat 4 -->
                <div class="col">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body p-3 d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted fs-8 fw-semibold mb-1">Antrean Kunjungan Hari Ini</div>
                                <div class="fs-2 fw-bold text-dark mb-1"><?= number_format($totalAntrean, 0, ',', '.'); ?></div>
                                <div class="text-lifora-blue fs-8 fw-semibold">Poli Umum & KIA</div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-stethoscope fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar Pencarian & Filter -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="position-relative">
                                <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                                <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Cari berdasarkan NIK, No. RM, atau Nama..." class="form-control bg-lifora-blue-soft border-0 py-2 ps-5">
                                <?php if (!empty($search)): ?>
                                    <a href="kelola_pasien.php" class="position-absolute text-muted" style="right: 12px; top: 50%; transform: translateY(-50%);"><i class="fa-solid fa-xmark"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="jaminan" class="form-select bg-lifora-blue-soft border-0 py-2 fw-medium" onchange="this.form.submit()">
                                <option value="">Semua Jenis Jaminan</option>
                                <option value="BPJS Kesehatan" <?= $jaminan_filter == 'BPJS Kesehatan' ? 'selected' : ''; ?>>BPJS Kesehatan</option>
                                <option value="Umum / Mandiri" <?= $jaminan_filter == 'Umum / Mandiri' ? 'selected' : ''; ?>>Umum / Mandiri</option>
                                <option value="Asuransi Lain" <?= $jaminan_filter == 'Asuransi Lain' ? 'selected' : ''; ?>>Asuransi Lain</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="kategori" class="form-select bg-lifora-blue-soft border-0 py-2 fw-medium" onchange="this.form.submit()">
                                <option value="">Semua Kategori Usia</option>
                                <option value="Dewasa" <?= $kategori_filter == 'Dewasa' ? 'selected' : ''; ?>>Dewasa</option>
                                <option value="Lansia" <?= $kategori_filter == 'Lansia' ? 'selected' : ''; ?>>Lansia</option>
                                <option value="Anak" <?= $kategori_filter == 'Anak' ? 'selected' : ''; ?>>Anak</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <a href="kelola_pasien.php" class="btn bg-lifora-blue-soft w-100 py-2" title="Reset filter"><i class="fa-solid fa-arrows-rotate"></i></a>
                        </div>
                    </form>
                    
                    <div class="d-flex align-items-center gap-2 mt-3 fs-8 text-muted">
                        <span>Filter Aktif:</span>
                        <span class="badge bg-lifora-blue-soft text-dark fw-semibold rounded-pill px-3">Faskes: Melati Sejahtera</span>
                        <span class="badge bg-lifora-primary-soft text-lifora-primary-dark fw-semibold rounded-pill px-3">Status: Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Tabel Master Data Pasien -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-lifora-blue-soft">
                            <tr>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-4">No. RM</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3">NIK & Identitas</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Nama Lengkap Pasien</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Kelamin / Usia</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Jaminan & No. Kartu</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Domisili / Wilayah</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Kunjungan Terakhir</th>
                                <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center pe-4">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jumlahTampil > 0): ?>
                                <?php foreach ($pasienList as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-lifora-primary text-nowrap"><?= htmlspecialchars($row['no_rm']); ?></td>

                                    <td>
                                        <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($row['nik_identitas']); ?></div>
                                        <div class="text-muted fs-8"><?= htmlspecialchars($row['jenis_identitas']); ?></div>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-lifora-primary-soft text-lifora-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 40px; height: 40px;">
                                                <?= htmlspecialchars($row['inisial']); ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark fs-7"><?= htmlspecialchars($row['nama_lengkap']); ?></div>
                                                <div class="fs-8 text-muted">
                                                    <?php if (!empty($row['gol_darah']) && $row['gol_darah'] != '-'): ?>
                                                        Gol. Darah: <?= htmlspecialchars($row['gol_darah']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['catatan_khusus'])): ?>
                                                        <span class="text-danger fw-semibold ms-1"><?= htmlspecialchars($row['catatan_khusus']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-medium text-dark fs-7"><?= htmlspecialchars($row['jenis_kelamin']); ?></div>
                                        <div class="fs-8">
                                            <?php if ($row['kategori_usia'] == 'Lansia'): ?>
                                                <span class="text-lifora-primary fw-bold"><?= $row['usia']; ?> Th (Lansia)</span>
                                            <?php elseif ($row['kategori_usia'] == 'Anak'): ?>
                                                <span class="text-lifora-blue fw-bold"><?= $row['usia']; ?> Th (Anak)</span>
                                            <?php else: ?>
                                                <span class="text-muted"><?= $row['usia']; ?> Th (Dewasa)</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($row['jenis_jaminan'] == 'BPJS Kesehatan'): ?>
                                            <span class="badge bg-lifora-blue-badge text-lifora-blue rounded-pill px-2 py-1 fs-8 mb-1">BPJS Kesehatan</span>
                                        <?php elseif ($row['jenis_jaminan'] == 'Umum / Mandiri'): ?>
                                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-8 mb-1">Umum / Mandiri</span>
                                        <?php else: ?>
                                            <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-2 py-1 fs-8 mb-1">Asuransi Lain</span>
                                        <?php endif; ?>
                                        <div class="fs-8 text-muted"><?= htmlspecialchars($row['nomor_kartu']); ?></div>
                                    </td>

                                    <td class="fs-8 text-muted"><?= $row['domisili_wilayah']; ?></td>

                                    <td>
                                        <div class="fw-semibold text-dark fs-7"><?= $row['kunjungan_terakhir'] ? date('d M Y', strtotime($row['kunjungan_terakhir'])) : '-'; ?></div>
                                        <div class="fs-8 fw-semibold text-lifora-primary"><?= htmlspecialchars($row['poli_terakhir']); ?></div>
                                    </td>

                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-3 text-muted fs-6">
                                            <a href="#" class="text-muted text-decoration-none" title="Lihat"><i class="fa-regular fa-eye"></i></a>
                                            <a href="#" class="text-muted text-decoration-none" title="Ubah"><i class="fa-regular fa-pen-to-square"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fs-1 mb-3 text-slate-300 d-block"></i>
                                        <span class="fs-7">Data pasien tidak ditemukan.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div class="bg-lifora-blue-soft p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div class="fs-8 text-muted">
                        Menampilkan <strong><?= $jumlahTampil; ?></strong> dari <strong><?= number_format($totalPasien, 0, ',', '.'); ?></strong> pasien terdaftar
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-light fw-semibold text-muted disabled" type="button"><i class="fa-solid fa-angle-left me-1"></i> Sebelumnya</button>
                        <button class="btn btn-sm bg-lifora-primary-dark text-white fw-bold px-3" type="button">1</button>
                        <button class="btn btn-sm btn-light fw-semibold text-dark px-3" type="button">2</button>
                        <button class="btn btn-sm btn-light fw-semibold text-dark px-3" type="button">3</button>
                        <span class="text-muted mx-1">...</span>
                        <button class="btn btn-sm btn-light fw-semibold text-dark px-3" type="button">245</button>
                        <button class="btn btn-sm btn-light fw-semibold text-dark" type="button">Selanjutnya <i class="fa-solid fa-angle-right ms-1"></i></button>
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