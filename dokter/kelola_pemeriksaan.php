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
$nama_dokter = $profil['nama'];

// Ambil data antrean untuk dokter ini yang statusnya masih Menunggu
$stmt = $pdo->prepare("SELECT * FROM antrean WHERE dokter = ? AND status_layanan = 'Menunggu' ORDER BY id ASC");
$stmt->execute([$nama_dokter]);
$antreanList = $stmt->fetchAll();

// Hitung statistik sederhana
$totalAntrean = count($antreanList);
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
        <?php include '../layout/components/dokter/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <!-- Header Page -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Daftar Antrean Pasien</h2>
                    <p class="text-muted fs-7 mb-0">Kelola pasien yang menunggu untuk diperiksa di poli Anda hari ini.</p>
                </div>
                <div class="badge bg-white border text-dark rounded-pill px-3 py-2 shadow-sm fs-7 fw-semibold">
                    <i class="fa-solid fa-user-doctor text-lifora-primary me-2"></i> Dokter: <?= htmlspecialchars($nama_dokter); ?>
                </div>
            </div>

            <!-- Card Statistik Ringkas -->
            <div class="row row-cols-1 row-cols-md-3 g-3">
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Total Antrean Menunggu</div>
                                <div class="fs-2 fw-bold text-lifora-primary-dark"><?= $totalAntrean; ?> <span class="fs-6 fw-normal text-muted">Pasien</span></div>
                            </div>
                            <div class="bg-lifora-primary-soft text-lifora-primary rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-hourglass-half fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Poli Tujuan</div>
                                <div class="fs-2 fw-bold text-dark"><?= htmlspecialchars($profil['poli'] ?? '-'); ?></div>
                            </div>
                            <div class="bg-lifora-blue-badge text-lifora-blue rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-hospital fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-8 fw-bold text-muted text-uppercase mb-1">Sesi Layanan</div>
                                <div class="fs-5 fw-bold text-dark mt-1">Shift Pagi</div>
                                <div class="fs-8 text-lifora-primary fw-semibold mt-1"><i class="fa-solid fa-circle-check me-1"></i> Aktif</div>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fa-regular fa-clock fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Antrean -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-bold py-3 ps-4">No. Antrean</th>
                                    <th class="fs-9 text-muted text-uppercase fw-bold py-3">Nama Pasien</th>
                                    <th class="fs-9 text-muted text-uppercase fw-bold py-3">No. RM</th>
                                    <th class="fs-9 text-muted text-uppercase fw-bold py-3">Poli Tujuan</th>
                                    <th class="fs-9 text-muted text-uppercase fw-bold py-3">Status</th>
                                    <th class="fs-9 text-muted text-uppercase fw-bold py-3 text-center pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($antreanList) > 0): ?>
                                    <?php foreach ($antreanList as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-lifora-blue-badge text-dark fs-6 fw-bold rounded px-3 py-2">
                                                <?= htmlspecialchars($row['no_antrean']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark fs-7"><?= htmlspecialchars($row['nama_pasien']); ?></div>
                                        </td>
                                        <td class="fs-7 text-muted fw-medium"><?= htmlspecialchars($row['no_rm']); ?></td>
                                        <td>
                                            <span class="badge bg-lifora-primary-soft text-lifora-primary-dark rounded-pill px-2 py-1 fs-8">
                                                <?= htmlspecialchars($row['poli_tujuan']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning bg-opacity-25 text-warning-dark rounded-pill px-2 py-1 fs-8">
                                                <i class="fa-solid fa-clock me-1"></i> <?= htmlspecialchars($row['status_layanan']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center pe-4">
                                            <a href="input_rekammedis.php?id_antrean=<?= $row['id']; ?>" class="btn btn-sm bg-lifora-primary text-white fw-semibold rounded-pill px-3 shadow-sm">
                                                <i class="fa-solid fa-stethoscope me-1"></i> Periksa
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-check-circle text-success fs-1 mb-3 d-block opacity-50"></i>
                                            <span class="fs-7 fw-medium">Tidak ada antrean pasien yang menunggu saat ini.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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