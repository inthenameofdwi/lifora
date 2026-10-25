<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

// Ambil Profil Dokter
$stmt = $pdo->prepare("SELECT * FROM dokter WHERE username = ? LIMIT 1");
$stmt->execute([$_SESSION['username']]);
$profilDokter = $stmt->fetch();
$nama_dokter = $profilDokter['nama_dokter'] ?? 'Dokter';

// Fitur Pencarian
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM rekam_medis WHERE dokter = ?";
$params = [$nama_dokter];

if ($search !== '') {
    $sql .= " AND (nama_pasien LIKE ? OR no_rm LIKE ? OR kode_icd LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
$sql .= " ORDER BY waktu_kunjungan DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$arsipList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include '../layout/components/dokter/head.php'; ?>
</head>
<body>
<div class="d-flex min-vh-100">
    <?php include '../layout/components/dokter/sidebar.php'; ?>

    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <?php include '../layout/components/dokter/topbar.php'; ?>

        <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column gap-4">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Arsip Rekam Medis</h2>
                    <p class="text-muted fs-7 mb-0">Riwayat pasien yang pernah Anda tangani.</p>
                </div>
                <form method="GET" class="d-flex gap-2">
                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="left: 14px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" class="form-control bg-white border py-2 ps-5" placeholder="Cari pasien, RM, atau ICD...">
                    </div>
                    <button type="submit" class="btn bg-lifora-primary text-white px-3"><i class="fa-solid fa-search"></i></button>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-lifora-blue-soft">
                            <tr>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3 ps-4">Tgl Kunjungan</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3">No. RM / Pasien</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3">Diagnosis (ICD-10)</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3">Status Berkas</th>
                                <th class="fs-9 text-muted text-uppercase fw-bold py-3 text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($arsipList) > 0): foreach ($arsipList as $row): ?>
                            <tr>
                                <td class="ps-4 fs-7 fw-medium text-dark">
                                    <?= date('d/m/Y', strtotime($row['waktu_kunjungan'])); ?> <br>
                                    <span class="fs-8 text-muted fw-normal"><?= date('H:i', strtotime($row['waktu_kunjungan'])); ?> WIB</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-7"><?= htmlspecialchars($row['nama_pasien']); ?></div>
                                    <div class="fs-8 text-muted"><?= htmlspecialchars($row['no_rm']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-lifora-blue-badge text-dark rounded px-2 py-1 fs-8"><?= htmlspecialchars($row['kode_icd']); ?></span>
                                    <div class="fs-8 text-dark mt-1"><?= htmlspecialchars($row['nama_diagnosis']); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $row['status_berkas'] === 'Lengkap & Terkunci' ? 'bg-lifora-primary-soft text-lifora-primary-dark' : 'bg-warning bg-opacity-25 text-warning-dark'; ?> rounded-pill px-2 py-1 fs-8">
                                        <?= htmlspecialchars($row['status_berkas']); ?>
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm bg-light border text-dark fw-semibold rounded-pill px-3" onclick='bukaDetail(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fa-regular fa-eye me-1"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted fs-7">Belum ada arsip rekam medis.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Detail RM -->
<div class="modal fade" id="mdlDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title fw-bold" id="m_judul">Detail Rekam Medis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6"><div class="fs-9 fw-bold text-muted text-uppercase">No. Registrasi</div><div class="fs-7 fw-semibold text-dark" id="m_reg"></div></div>
                    <div class="col-md-6"><div class="fs-9 fw-bold text-muted text-uppercase">Waktu Kunjungan</div><div class="fs-7 fw-semibold text-dark" id="m_waktu"></div></div>
                    <div class="col-md-6"><div class="fs-9 fw-bold text-muted text-uppercase">Diagnosis (ICD-10)</div><div class="fs-7 fw-semibold text-dark" id="m_diag"></div></div>
                    <div class="col-md-6"><div class="fs-9 fw-bold text-muted text-uppercase">Jaminan</div><div class="fs-7 fw-semibold text-dark" id="m_jaminan"></div></div>
                    <div class="col-12 mt-3">
                        <div class="fs-9 fw-bold text-muted text-uppercase mb-1">Catatan Klinis (SOAP)</div>
                        <div class="bg-light p-3 rounded-3 fs-7 text-dark" id="m_soap" style="white-space: pre-wrap; line-height: 1.6;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layout/components/dokter/footer.php'; ?>
<script>
    function bukaDetail(data) {
        document.getElementById('m_judul').textContent = 'RM: ' + data.nama_pasien;
        document.getElementById('m_reg').textContent = data.no_registrasi;
        document.getElementById('m_waktu').textContent = data.waktu_kunjungan;
        document.getElementById('m_diag').textContent = data.kode_icd + ' - ' + data.nama_diagnosis;
        document.getElementById('m_jaminan').textContent = data.jaminan;
        document.getElementById('m_soap').textContent = data.keterangan_klinis || '-';
        new bootstrap.Modal(document.getElementById('mdlDetail')).show();
    }
</script>
</body>
</html>