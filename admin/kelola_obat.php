<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

// Proses Tambah Obat
if(isset($_POST['tambah_obat'])) {
    $kode     = trim($_POST['kode_obat']);
    $nama     = trim($_POST['nama_obat']);
    $kategori = trim($_POST['kategori']);
    $stok     = (int)$_POST['stok'];
    $harga    = (float)$_POST['harga'];

    try {
        $stmt = $pdo->prepare("INSERT INTO obat (kode_obat, nama_obat, kategori, stok, harga) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$kode, $nama, $kategori, $stok, $harga]);
        echo "<script>alert('Obat berhasil ditambahkan!'); window.location='kelola_obat.php';</script>";
    } catch (PDOException $e) {
        // Tangkap error jika kode_obat duplikat (UNIQUE KEY)
        $errorMsg = "Gagal menambahkan obat: " . $e->getMessage();
        echo "<script>alert('" . addslashes($errorMsg) . "');</script>";
    }
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
            
            <!-- Header Page -->
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Kelola Stok Obat</h2>
                    <p class="text-muted fs-7 mb-0">Manajemen persediaan obat dan alkes di Gudang Farmasi Puskesmas.</p>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 fs-7">
                        <li class="breadcrumb-item"><a href="dashboard_admin.php" class="text-decoration-none text-muted">Dashboard</a></li>
                        <li class="breadcrumb-item active text-lifora-primary fw-semibold" aria-current="page">Kelola Obat</li>
                    </ol>
                </nav>
            </div>

            <div class="row g-4">
                <!-- Form Tambah Obat -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-4">
                                <div class="bg-lifora-primary-soft text-lifora-primary rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-0">Tambah Data Obat</h5>
                            </div>
                            
                            <form method="POST" class="d-flex flex-column gap-3">
                                <div>
                                    <label class="form-label fs-8 fw-semibold text-muted">Kode Obat</label>
                                    <input type="text" name="kode_obat" placeholder="Cth: OB-001" class="form-control form-control-sm bg-light border-0 py-2" required>
                                </div>
                                <div>
                                    <label class="form-label fs-8 fw-semibold text-muted">Nama Obat</label>
                                    <input type="text" name="nama_obat" placeholder="Nama lengkap obat" class="form-control form-control-sm bg-light border-0 py-2" required>
                                </div>
                                <div>
                                    <label class="form-label fs-8 fw-semibold text-muted">Kategori</label>
                                    <select name="kategori" class="form-select form-select-sm bg-light border-0 py-2" required>
                                        <option value="" disabled selected>Pilih Kategori</option>
                                        <option value="Tablet">Tablet / Kapsul</option>
                                        <option value="Sirup">Sirup / Cair</option>
                                        <option value="Salep">Salep / Krim</option>
                                        <option value="Injeksi">Injeksi / Suntik</option>
                                        <option value="Alkes">Alat Kesehatan (Alkes)</option>
                                    </select>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fs-8 fw-semibold text-muted">Jumlah Stok</label>
                                        <input type="number" name="stok" min="0" placeholder="0" class="form-control form-control-sm bg-light border-0 py-2" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-8 fw-semibold text-muted">Harga (Rp)</label>
                                        <input type="number" name="harga" min="0" placeholder="0" class="form-control form-control-sm bg-light border-0 py-2" required>
                                    </div>
                                </div>
                                <button type="submit" name="tambah_obat" class="btn bg-lifora-primary text-white fw-semibold mt-2 py-2">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Obat
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tabel Daftar Obat -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-pills text-lifora-primary me-2"></i> Daftar Stok Obat</h5>
                                <span class="badge bg-lifora-blue-soft text-lifora-blue rounded-pill px-3 py-2 fs-8">Data Gudang Farmasi</span>
                            </div>

                            <div class="table-responsive flex-grow-1">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-lifora-blue-soft" style="border-radius: 8px;">
                                        <tr>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold rounded-start py-3 ps-3">Kode</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Nama Obat</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold py-3">Kategori</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold py-3 text-center">Stok</th>
                                            <th class="fs-9 text-muted text-uppercase fw-semibold rounded-end py-3 text-end pe-3">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM obat ORDER BY id DESC");
                                        $obatList = $stmt->fetchAll();
                                        
                                        if(empty($obatList)):
                                        ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted fs-7 py-5">
                                                    <i class="fa-solid fa-box-open fs-2 mb-3 text-slate-300 d-block"></i>
                                                    Belum ada data obat yang tersimpan.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($obatList as $row): ?>
                                            <tr>
                                                <td class="ps-3 py-3 fw-medium text-muted fs-7"><?= htmlspecialchars($row['kode_obat']); ?></td>
                                                <td class="py-3 fw-bold text-dark fs-7"><?= htmlspecialchars($row['nama_obat']); ?></td>
                                                <td class="py-3 fs-7">
                                                    <span class="badge bg-light text-dark border fw-medium"><?= htmlspecialchars($row['kategori']); ?></span>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <?php if($row['stok'] < 10): ?>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold fs-7 px-2 py-1">
                                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= $row['stok']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold fs-7 px-2 py-1">
                                                            <?= $row['stok']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end pe-3 fw-semibold text-dark fs-7">
                                                    Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top text-muted fs-8 fw-medium">
                                <span>Total <b><?= count($obatList); ?></b> item obat terdaftar</span>
                                <span>Data realtime database</span>
                            </div>
                        </div>
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