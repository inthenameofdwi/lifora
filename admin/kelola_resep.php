<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../koneksi.php';

$pesan = '';

// ==========================================
// PROSES CRUD
// ==========================================

// 1. CREATE (Tambah Resep Baru)
if (isset($_POST['tambah_resep'])) {
    $no_registrasi = trim($_POST['no_registrasi']);
    $no_rm         = trim($_POST['no_rm']);
    $dokter        = trim($_POST['dokter']);
    $status_resep  = 'Menunggu Apotek';
    
    // Array detail obat (dikirim sebagai array dari form)
    $obat_ids     = $_POST['obat_id'] ?? [];
    $aturan_pakai = $_POST['aturan_pakai'] ?? [];
    $jumlahs      = $_POST['jumlah'] ?? [];

    try {
        $pdo->beginTransaction();

        // Insert ke tabel resep
        $stmt = $pdo->prepare("INSERT INTO resep (no_registrasi, no_rm, dokter, status_resep) VALUES (?, ?, ?, ?)");
        $stmt->execute([$no_registrasi, $no_rm, $dokter, $status_resep]);
        $resep_id = $pdo->lastInsertId();

        // Insert ke tabel resep_detail
        if (!empty($obat_ids)) {
            $stmtDetail = $pdo->prepare("INSERT INTO resep_detail (resep_id, obat_id, aturan_pakai, jumlah) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($obat_ids); $i++) {
                if (!empty($obat_ids[$i]) && !empty($jumlahs[$i])) {
                    $stmtDetail->execute([$resep_id, $obat_ids[$i], $aturan_pakai[$i], $jumlahs[$i]]);
                }
            }
        }

        $pdo->commit();
        $pesan = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Resep berhasil ditambahkan!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Gagal menambah resep: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 2. UPDATE (Proses & Serahkan Obat)
if (isset($_GET['selesai'])) {
    $id_resep = $_GET['selesai'];
    try {
        $stmt = $pdo->prepare("UPDATE resep SET status_resep = 'Selesai & Diserahkan' WHERE id = ?");
        $stmt->execute([$id_resep]);
        $pesan = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Obat telah diserahkan ke pasien!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Gagal update status: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 3. DELETE (Hapus Resep)
if (isset($_GET['hapus'])) {
    $id_resep = $_GET['hapus'];
    try {
        $pdo->beginTransaction();
        // Hapus detail dulu (karena foreign key jika ada)
        $stmt = $pdo->prepare("DELETE FROM resep_detail WHERE resep_id = ?");
        $stmt->execute([$id_resep]);
        // Hapus header resep
        $stmt = $pdo->prepare("DELETE FROM resep WHERE id = ?");
        $stmt->execute([$id_resep]);
        $pdo->commit();
        $pesan = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Resep berhasil dihapus!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $pesan = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Gagal menghapus resep: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// AMBIL DATA UNTUK TAMPILAN
// ==========================================

// Data Resep Menunggu Apotek
$stmt = $pdo->query("SELECT * FROM resep WHERE status_resep = 'Menunggu Apotek' ORDER BY created_at ASC");
$resepMenunggu = $stmt->fetchAll();

// Data Resep Selesai (Riwayat)
$stmt = $pdo->query("SELECT * FROM resep WHERE status_resep = 'Selesai & Diserahkan' ORDER BY created_at DESC LIMIT 10");
$resepSelesai = $stmt->fetchAll();

// Data Master Obat untuk Form Tambah
$stmt = $pdo->query("SELECT id, kode_obat, nama_obat, stok FROM obat ORDER BY nama_obat ASC");
$masterObat = $stmt->fetchAll();
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Antrean Resep Elektronik</h2>
                    <p class="text-muted fs-7 mb-0">Manajemen E-Prescribing dan penyerahan obat dari Apotek Puskesmas.</p>
                </div>
                <button class="btn bg-lifora-primary text-white fw-semibold px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahResep">
                    <i class="fa-solid fa-plus me-2"></i> Buat Resep Baru
                </button>
            </div>

            <!-- Notifikasi -->
            <?= $pesan; ?>

            <!-- Tabel Antrean Resep Menunggu -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-hourglass-half text-warning me-2"></i> Menunggu Diproses Apotek</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3 rounded-start">No Registrasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">No RM Pasien</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Dokter Perujuk</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Waktu Resep</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Status</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center rounded-end pe-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($resepMenunggu) > 0): ?>
                                    <?php foreach ($resepMenunggu as $row): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark fs-7"><?= htmlspecialchars($row['no_registrasi']); ?></td>
                                        <td class="fs-7"><?= htmlspecialchars($row['no_rm']); ?></td>
                                        <td class="fs-7"><?= htmlspecialchars($row['dokter']); ?></td>
                                        <td class="fs-7 text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?> WIB</td>
                                        <td>
                                            <span class="badge bg-warning bg-opacity-25 text-warning-dark fw-semibold rounded-pill px-2 py-1 fs-8">
                                                <i class="fa-solid fa-clock me-1"></i> <?= htmlspecialchars($row['status_resep']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center pe-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="kelola_resep.php?selesai=<?= $row['id']; ?>" class="btn btn-sm bg-lifora-primary text-white fw-semibold rounded-pill px-3" onclick="return confirm('Tandai obat sudah diserahkan ke pasien?')">
                                                    <i class="fa-solid fa-check me-1"></i> Serahkan
                                                </a>
                                                <a href="kelola_resep.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-light border text-danger rounded-pill px-2" onclick="return confirm('Yakin ingin menghapus resep ini?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted fs-7">
                                            <i class="fa-solid fa-check-circle text-success fs-3 mb-2 d-block"></i>
                                            Tidak ada antrean resep saat ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Resep Selesai -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-box-archive text-lifora-primary me-2"></i> Riwayat Resep Terakhir</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-lifora-blue-soft">
                                <tr>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 ps-3 rounded-start">No Registrasi</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">No RM Pasien</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Dokter Perujuk</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Waktu</th>
                                    <th class="fs-9 text-muted text-uppercase fw-semibold py-3 rounded-end pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($resepSelesai) > 0): ?>
                                    <?php foreach ($resepSelesai as $row): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark fs-7"><?= htmlspecialchars($row['no_registrasi']); ?></td>
                                        <td class="fs-7"><?= htmlspecialchars($row['no_rm']); ?></td>
                                        <td class="fs-7"><?= htmlspecialchars($row['dokter']); ?></td>
                                        <td class="fs-7 text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                        <td class="pe-3">
                                            <span class="badge bg-success bg-opacity-25 text-success fw-semibold rounded-pill px-2 py-1 fs-8">
                                                <i class="fa-solid fa-check-double me-1"></i> <?= htmlspecialchars($row['status_resep']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted fs-7">Belum ada riwayat resep selesai.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- End Container -->
    </main>
</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH RESEP (CRUD - CREATE)         -->
<!-- ========================================== -->
<div class="modal fade" id="modalTambahResep" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-lifora-primary text-white border-bottom-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-prescription me-2"></i> Buat Resep Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fs-8 fw-semibold text-muted">No. Registrasi</label>
                            <input type="text" name="no_registrasi" class="form-control bg-light border-0" placeholder="Cth: REG-2026..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-8 fw-semibold text-muted">No. RM Pasien</label>
                            <input type="text" name="no_rm" class="form-control bg-light border-0" placeholder="Cth: RM-2024..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-8 fw-semibold text-muted">Dokter Perujuk</label>
                            <input type="text" name="dokter" class="form-control bg-light border-0" placeholder="Nama Dokter" required>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0">Detail Obat Diresepkan</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahObat"><i class="fa-solid fa-plus me-1"></i> Tambah Obat</button>
                    </div>
                    
                    <div id="containerObat">
                        <!-- Baris obat pertama -->
                        <div class="row g-2 mb-2 obat-row">
                            <div class="col-md-5">
                                <select name="obat_id[]" class="form-select form-select-sm bg-light border-0" required>
                                    <option value="">Pilih Obat...</option>
                                    <?php foreach ($masterObat as $obat): ?>
                                        <option value="<?= $obat['id']; ?>"><?= htmlspecialchars($obat['kode_obat'] . ' - ' . $obat['nama_obat'] . ' (Stok: ' . $obat['stok'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="aturan_pakai[]" class="form-control form-control-sm bg-light border-0" placeholder="Aturan Pakai (3x1)" required>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="jumlah[]" class="form-control form-control-sm bg-light border-0" placeholder="Qty" min="1" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 btn-hapus-obat" disabled><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_resep" class="btn bg-lifora-primary text-white fw-semibold"><i class="fa-solid fa-save me-1"></i> Simpan Resep</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Footer & Scripts -->
<?php include '../layout/components/admin/footer.php'; ?>

<script>
    // Script untuk menambah/menghapus baris form obat secara dinamis
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('containerObat');
        const btnTambah = document.getElementById('btnTambahObat');
        
        // Template baris obat baru
        const templateRow = `
            <div class="row g-2 mb-2 obat-row">
                <div class="col-md-5">
                    <select name="obat_id[]" class="form-select form-select-sm bg-light border-0" required>
                        <option value="">Pilih Obat...</option>
                        <?php foreach ($masterObat as $obat): ?>
                            <option value="<?= $obat['id']; ?>"><?= htmlspecialchars($obat['kode_obat'] . ' - ' . $obat['nama_obat'] . ' (Stok: ' . $obat['stok'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="aturan_pakai[]" class="form-control form-control-sm bg-light border-0" placeholder="Aturan Pakai (3x1)" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="jumlah[]" class="form-control form-control-sm bg-light border-0" placeholder="Qty" min="1" required>
                </div>
                <div class="col-md-2 d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 btn-hapus-obat"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;

        // Event listener untuk tombol Tambah Obat
        btnTambah.addEventListener('click', function() {
            container.insertAdjacentHTML('beforeend', templateRow);
            updateHapusButtons();
        });

        // Event delegation untuk tombol Hapus Obat
        container.addEventListener('click', function(e) {
            if (e.target.closest('.btn-hapus-obat')) {
                const row = e.target.closest('.obat-row');
                if (container.querySelectorAll('.obat-row').length > 1) {
                    row.remove();
                    updateHapusButtons();
                }
            }
        });

        // Update status tombol hapus (disable jika hanya tersisa 1 baris)
        function updateHapusButtons() {
            const rows = container.querySelectorAll('.obat-row');
            rows.forEach((row, index) => {
                const btn = row.querySelector('.btn-hapus-obat');
                if (rows.length === 1) {
                    btn.disabled = true;
                } else {
                    btn.disabled = false;
                }
            });
        }
        
        // Panggil inisialisasi awal
        updateHapusButtons();
    });
</script>

</body>
</html>